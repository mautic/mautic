<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\FormBundle\Entity\FormRepository;
use Mautic\FormBundle\Entity\SubmissionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: DeleteOrphanSubmissionRecordsFromFormResultsTableCommand::COMMAND_NAME,
    description: 'Deletes records from form results table for whom associated form submission records is deleted'
)]
class DeleteOrphanSubmissionRecordsFromFormResultsTableCommand extends Command
{
    private const SUBMISSION_RESULTS_LIMIT = 5000;

    public const COMMAND_NAME = 'mautic:forms:delete-orphan-form-submission-records-from-form-results-table';

    public function __construct(
        private readonly FormRepository $formRepository,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
        private readonly SubmissionRepository $submissionRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $totalDeletedRecords = 0;
        $forms               = $this->formRepository->findAll();

        foreach ($forms as $form) {
            try {
                $tableName = MAUTIC_TABLE_PREFIX.'form_results_'.$form->getId().'_'.$form->getAlias();

                $qbSelect = $this->submissionRepository->getOrphanSubmissionRecords($tableName, self::SUBMISSION_RESULTS_LIMIT);

                while ($results = $qbSelect->executeQuery()->fetchAllAssociative()) {
                    $inValidSubmissionIds = [];

                    foreach ($results as $result) {
                        $inValidSubmissionIds[] = $result['submission_id'];
                    }

                    $this->submissionRepository->deleteOrphanSubmissionRecords($tableName, $inValidSubmissionIds);

                    $totalDeletedRecords += count($inValidSubmissionIds);
                }
            } catch (\Exception $e) {
                $this->logger->error('Exception occurred in deleting records from table '.$tableName);
                $this->logger->error($e->getMessage());

                return ExitCode::FAILURE;
            }
        }

        $output->writeln($this->translator->trans('mautic.forms.command.orphan_submission_records_deleted', ['%record_count%' => $totalDeletedRecords]));

        return ExitCode::SUCCESS;
    }
}
