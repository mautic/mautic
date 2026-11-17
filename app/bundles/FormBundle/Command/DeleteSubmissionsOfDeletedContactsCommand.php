<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: DeleteSubmissionsOfDeletedContactsCommand::COMMAND_NAME,
    description: 'Deletes form submissions and their form results records of contacts that were deleted',
    help: <<<'TXT'
When Mautic deletes a contact, the form submissions of the contact stay in the database without a contact.
The submitted values also stay in the form results tables (form_results_<form ID>_<form alias>).
This command deletes these submissions and their form results records for all forms.

<info>php %command.full_name%</info>

Count the submissions of deleted contacts for each form. Nothing is deleted:
<info>php %command.full_name% --dry-run</info>

Only delete the submissions of one form:
<info>php %command.full_name% --form-id=12</info>
TXT
)]
final class DeleteSubmissionsOfDeletedContactsCommand extends Command
{
    public const COMMAND_NAME = 'mautic:forms:delete-submissions-of-deleted-contacts';

    private const DEFAULT_BATCH_LIMIT = 1000;

    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly SubmissionRepository $submissionRepository,
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
        $formId = $this->getPositiveIntegerOption($input, 'form-id');
        if (false === $formId) {
            return $this->writeInvalidOption($output, 'form-id');
        }

        $batchLimit = $this->getPositiveIntegerOption($input, 'batch-limit') ?? self::DEFAULT_BATCH_LIMIT;
        if (false === $batchLimit) {
            return $this->writeInvalidOption($output, 'batch-limit');
        }

        $dryRun = (bool) $input->getOption('dry-run');

        $form = null === $formId ? null : $this->formRepository->find($formId);

        if (null !== $formId && !$form instanceof Form) {
            $output->writeln('<error>'.$this->translator->trans('mautic.forms.command.deleted_contact_submissions.form_not_found', ['%form_id%' => $formId]).'</error>');

            return ExitCode::FAILURE;
        }

        $counts = $this->submissionRepository->countSubmissionsOfDeletedContactsByForm($formId);
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
                    : $this->deleteSubmissionsOfForm($form, $batchLimit, $existingResultsTables);
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
    private function deleteSubmissionsOfForm(Form $form, int $batchLimit, array $existingResultsTables): int
    {
        $formId             = (int) $form->getId();
        $resultsTableExists = isset($existingResultsTables[$this->submissionRepository->getResultsTableName($formId, $form->getAlias())]);
        $deleted            = 0;
        $lastId             = 0;

        do {
            $submissionIds = $this->submissionRepository->getSubmissionIdsOfDeletedContacts($formId, $batchLimit, $lastId);

            if ([] === $submissionIds) {
                break;
            }

            $deleted += $this->submissionRepository->deleteSubmissionsOfDeletedContacts($form, $submissionIds, $resultsTableExists);
            $lastId = max($submissionIds);
        } while (count($submissionIds) === $batchLimit);

        if ($deleted > 0) {
            $this->logger->info(sprintf('Deleted %d submissions of deleted contacts of form %d.', $deleted, $formId));
        }

        return $deleted;
    }

    private function writeInvalidOption(OutputInterface $output, string $option): int
    {
        $output->writeln('<error>'.$this->translator->trans('mautic.forms.command.deleted_contact_submissions.invalid_option', ['%option%' => $option]).'</error>');

        return ExitCode::FAILURE;
    }

    /**
     * @return int|false|null The value, false if the value is not a positive integer, or null if the option is not set
     */
    private function getPositiveIntegerOption(InputInterface $input, string $name): int|false|null
    {
        $value = $input->getOption($name);

        if (null === $value) {
            return null;
        }

        $value = trim((string) $value);

        if (!ctype_digit($value) || 0 === (int) $value) {
            return false;
        }

        return (int) $value;
    }
}
