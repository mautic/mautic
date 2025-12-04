<?php

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Exception\ImportDelayedException;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Helper\Progress;
use Mautic\LeadBundle\Model\ImportModel;
use Mautic\UserBundle\Security\UserTokenSetter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to import data.
 */
#[AsCommand(name: ImportCommand::COMMAND_NAME, description: 'Imports data to Mautic', help: <<<'TXT'
The <info>%command.name%</info> command starts to import CSV files when some are created.

<info>php %command.full_name%</info>
TXT)]
class ImportCommand
{
    public const COMMAND_NAME = 'mautic:import';

    public function __construct(
        private TranslatorInterface $translator,
        private ImportModel $importModel,
        private ProcessSignalService $processSignalService,
        private UserTokenSetter $userTokenSetter,
        private LoggerInterface $logger,
        private NotificationModel $notificationModel,
    ) {
    }

    public function __invoke(
        OutputInterface $output,
        #[\Symfony\Component\Console\Attribute\Option(name: '--id', shortcut: '-i', description: 'Specific ID to import. Defaults to next in the queue.')]
        $id = false,
        #[\Symfony\Component\Console\Attribute\Option(name: '--limit', shortcut: '-l', description: 'Maximum number of records to import for this script execution.')]
        ?int $limit = 0,
    ): int {
        $start    = microtime(true);
        $progress = new Progress($output);
        $id       = (int) $id;
        $limit    = (int) $limit;

        $this->processSignalService->registerSignalHandler(fn (int $signal) => $output->writeln(sprintf('Signal %d caught.', $signal)));

        if ($id) {
            $import = $this->importModel->getEntity($id);

            // This specific import was not found
            if (!$import) {
                $output->writeln('<error>'.$this->translator->trans('mautic.core.error.notfound', [], 'flashes').'</error>');

                return Command::FAILURE;
            }
        } else {
            $import = $this->importModel->getImportToProcess();

            // No import waiting in the queue. Finish silently.
            if (null === $import) {
                return Command::SUCCESS;
            }
        }

        $user = $import->getModifiedBy();

        if (!$user) {
            throw new \RuntimeException('Import does not have "modifiedBy" property set.');
        }

        $this->userTokenSetter->setUser($user);

        $output->writeln('<info>'.$this->translator->trans(
            'mautic.lead.import.is.starting',
            [
                '%id%'    => $import->getId(),
                '%lines%' => $import->getLineCount(),
            ]
        ).'</info>');

        try {
            $this->importModel->beginImport($import, $progress, $limit, $start);
        } catch (ImportFailedException $e) {
            $output->writeln('<error>'.$this->translator->trans(
                'mautic.lead.import.failed',
                [
                    '%reason%' => $import->getStatusInfo(),
                ]
            ).'</error>');

            $this->logError($import, $e);

            $this->notify(
                $import,
                $start,
                $this->translator->trans('mautic.lead.import.failed', ['%reason%' => $import->getStatusInfo()]),
                'error'
            );

            return Command::FAILURE;
        } catch (ImportDelayedException $e) {
            $output->writeln('<info>'.$this->translator->trans(
                'mautic.lead.import.delayed',
                [
                    '%reason%' => $import->getStatusInfo(),
                ]
            ).'</info>');

            $this->logError($import, $e);

            $this->notify(
                $import,
                $start,
                $this->translator->trans('mautic.lead.import.delayed', ['%reason%' => $import->getStatusInfo()]),
                'warning'
            );

            return Command::FAILURE;
        }

        // Success
        $output->writeln('<info>'.$this->translator->trans(
            'mautic.lead.import.result',
            [
                '%lines%'   => $import->getProcessedRows(),
                '%created%' => $import->getInsertedCount(),
                '%updated%' => $import->getUpdatedCount(),
                '%ignored%' => $import->getIgnoredCount(),
                '%time%'    => round(microtime(true) - $start, 2),
            ]
        ).'</info>');

        // Notification is now handled in ImportModel::beginImport to avoid duplicates
        // and to include the link to the imported file

        return Command::SUCCESS;
    }

    private function logError(Import $import, \Exception $exception): void
    {
        $message = ' Import id: '.$import->getId();
        $message .= ' Import Status: '.$import->getStatus();
        $message .= ' Reason: '.$import->getStatusInfo();
        $message .= ' Exception: '.$exception;

        $this->logger->warning($message);
    }

    private function notify(Import $import, float $start, string $header, string $type = 'info'): void
    {
        $this->notificationModel->addNotification(
            $this->translator->trans(
                'mautic.lead.import.result',
                [
                    '%lines%'   => $import->getProcessedRows(),
                    '%created%' => $import->getInsertedCount(),
                    '%updated%' => $import->getUpdatedCount(),
                    '%ignored%' => $import->getIgnoredCount(),
                    '%time%'    => round(microtime(true) - $start, 2),
                ]
            ),
            $type,
            false,
            $header,
            'ri-download-line'
        );
    }
}
