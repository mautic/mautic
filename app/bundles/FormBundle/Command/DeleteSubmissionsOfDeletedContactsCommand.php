<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\CoreBundle\Helper\FileUploader;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Mautic\FormBundle\Helper\FormUploader;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME,
    description: 'Deletes form submissions and their form results records of contacts that were deleted',
    help: <<<'TXT'
When Mautic deletes a contact, it records the contact in the form submissions of the contact. The submissions stay in the database
without a contact, and the submitted values stay in the form results tables (form_results_<form ID>_<form alias>).
This command deletes these submissions, their form results records and their uploaded files, for all forms.

Submissions without this record are not deleted: submissions that never had a contact, submissions of contacts
that were deleted before Mautic recorded deleted contacts, and submissions of contacts that mautic:maintenance:cleanup deleted.

<comment>This also applies to mautic:maintenance:cleanup --gdpr: the submissions of the contacts that it deletes are kept.</comment>

<info>php %command.full_name%</info>

Count the submissions of deleted contacts for each form. Nothing is deleted:
<info>php %command.full_name% --dry-run</info>

Only delete the submissions of one form:
<info>php %command.full_name% --form-id=12</info>

Delete the submissions of all forms except the forms 3 and 7:
<info>php %command.full_name% --exclude=3 --exclude=7</info>
TXT
)]
final class DeleteSubmissionsOfDeletedContactsCommand extends Command
{
    public const COMMAND_NAME = 'mautic:forms:delete-submissions-of-deleted-contacts';

    private const DEFAULT_BATCH_LIMIT = 1000;

    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly SubmissionRepository $submissionRepository,
        private readonly FormUploader $formUploader,
        private readonly FileUploader $fileUploader,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                '--form-id',
                '-i',
                InputOption::VALUE_REQUIRED,
                'Delete the submissions of a specific form. Otherwise, the submissions of all forms will be deleted.'
            )
            ->addOption(
                '--exclude',
                '-d',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Exclude a specific form from the deletion. Otherwise, the submissions of all forms will be deleted.',
                []
            )
            ->addOption(
                '--batch-limit',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'Set batch size of submissions to delete per round. Defaults to 1000.',
                self::DEFAULT_BATCH_LIMIT
            )
            ->addOption(
                '--dry-run',
                null,
                InputOption::VALUE_NONE,
                'Count the submissions without deleting them.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $formId          = InputHelper::positiveIntegerOption($input, 'form-id');
        $excludedFormIds = InputHelper::positiveIntegerArrayOption($input, 'exclude');
        $batchLimit      = InputHelper::positiveIntegerOption($input, 'batch-limit') ?? self::DEFAULT_BATCH_LIMIT;
        $dryRun          = (bool) $input->getOption('dry-run');

        if (null !== $formId && [] !== $excludedFormIds) {
            throw new InvalidOptionException('The "--form-id" and "--exclude" options cannot be used together.');
        }

        $form = null === $formId ? null : $this->formRepository->find($formId);

        if (null !== $formId && !$form instanceof Form) {
            $output->writeln('<error>'.$this->translator->trans('mautic.forms.command.deleted_contact_submissions.form_not_found', ['%form_id%' => $formId]).'</error>');

            return ExitCode::FAILURE;
        }

        $counts = $this->submissionRepository->countSubmissionsOfDeletedContactsByForm($formId, $excludedFormIds);
        $forms  = match (true) {
            $form instanceof Form => [$form],
            [] === $counts        => [],
            default               => $this->formRepository->findBy(['id' => array_keys($counts)], ['id' => 'ASC']),
        };

        $existingResultsTables = $dryRun || [] === $forms
            ? []
            : array_flip($this->submissionRepository->getExistingResultsTableNames());
        $total = 0;

        foreach ($forms as $form) {
            try {
                $count = $dryRun
                    ? ($counts[(int) $form->getId()] ?? 0)
                    : $this->deleteSubmissionsOfForm($form, $batchLimit, $existingResultsTables, $output);
            } catch (\Exception $e) {
                $this->logger->error('An exception occurred while deleting the submissions of deleted contacts.', ['formId' => $form->getId(), 'exception' => $e]);
                $output->writeln('<error>'.$e->getMessage().'</error>');

                return ExitCode::FAILURE;
            }

            if ($count > 0 && ($dryRun || $output->isVerbose())) {
                $output->writeln($this->translator->trans('mautic.forms.command.deleted_contact_submissions.form', [
                    '%form_id%'          => $form->getId(),
                    '%form_name%'        => $form->getName(),
                    '%submission_count%' => $count,
                ]));
            }

            $total += $count;
        }

        $output->writeln($this->translator->trans(
            $dryRun ? 'mautic.forms.command.deleted_contact_submissions.dry_run' : 'mautic.forms.command.deleted_contact_submissions.deleted',
            ['%submission_count%' => $total]
        ));

        return ExitCode::SUCCESS;
    }

    /**
     * @param array<string, int> $existingResultsTables
     */
    private function deleteSubmissionsOfForm(Form $form, int $batchLimit, array $existingResultsTables, OutputInterface $output): int
    {
        $formId             = (int) $form->getId();
        $resultsTableExists = isset($existingResultsTables[$this->submissionRepository->getResultsTableName($formId, $form->getAlias())]);
        $fileFields         = $resultsTableExists ? $this->getFileFields($form) : [];
        $deleted            = 0;
        $kept               = 0;
        $lastId             = 0;

        do {
            $submissionIds = $this->submissionRepository->getSubmissionIdsOfDeletedContacts($formId, $batchLimit, $lastId);

            if ([] === $submissionIds) {
                break;
            }

            $lastId           = max($submissionIds);
            $deletableIds     = $this->deleteUploadedFiles($form, $fileFields, $submissionIds);
            $kept += count($submissionIds) - count($deletableIds);
            $deleted += $this->submissionRepository->deleteSubmissionsOfDeletedContacts($form, $deletableIds, $resultsTableExists);
        } while (count($submissionIds) === $batchLimit);

        if ($deleted > 0) {
            $this->logger->info(sprintf('Deleted %d submissions of deleted contacts of form %d.', $deleted, $formId));
        }

        if ($kept > 0) {
            $output->writeln('<comment>'.$this->translator->trans('mautic.forms.command.deleted_contact_submissions.files_not_deleted', [
                '%form_id%'          => $formId,
                '%form_name%'        => $form->getName(),
                '%submission_count%' => $kept,
            ]).'</comment>');
        }

        return $deleted;
    }

    /**
     * @return array<string, Field> Field alias => field
     */
    private function getFileFields(Form $form): array
    {
        $fileFields = [];

        foreach ($form->getFields() as $field) {
            if ($field->isFileType()) {
                $fileFields[$field->getAlias()] = $field;
            }
        }

        return $fileFields;
    }

    /**
     * Deletes the uploaded files of the submissions, as FormUploader::deleteUploadedFiles() does for a single submission.
     * A submission whose file could not be deleted is kept, so that the next run still knows the file.
     *
     * @param array<string, Field> $fileFields
     * @param int[]                $submissionIds
     *
     * @return int[] The IDs of the submissions that have no uploaded files anymore
     */
    private function deleteUploadedFiles(Form $form, array $fileFields, array $submissionIds): array
    {
        $fileNames    = $this->submissionRepository->getUploadedFileNames($form, array_keys($fileFields), $submissionIds);
        $deletableIds = [];

        foreach ($submissionIds as $submissionId) {
            $filesDeleted = true;

            foreach ($fileNames[$submissionId] ?? [] as $alias => $fileName) {
                if (basename($fileName) !== $fileName) {
                    $this->logger->warning('A form submission of a deleted contact refers to an invalid file name. The file name is ignored.', ['submissionId' => $submissionId, 'fileName' => $fileName]);

                    continue;
                }

                $filePath = $this->formUploader->getCompleteFilePath($fileFields[$alias], $fileName);
                $this->fileUploader->delete($filePath);

                if (file_exists($filePath)) {
                    $filesDeleted = false;
                    $this->logger->error('The uploaded file of a form submission of a deleted contact could not be deleted.', ['submissionId' => $submissionId, 'filePath' => $filePath]);
                }
            }

            if ($filesDeleted) {
                $deletableIds[] = $submissionId;
            }
        }

        return $deletableIds;
    }
}
