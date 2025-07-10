<?php

namespace Mautic\LeadBundle\Command;

use Mautic\CoreBundle\ProcessSignal\ProcessSignalService;
use Mautic\LeadBundle\Entity\Import;
use Mautic\LeadBundle\Exception\ImportDelayedException;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Helper\Progress;
use Mautic\LeadBundle\Model\ImportModel;
use Mautic\UserBundle\Security\UserTokenSetter;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to import data.
 */
class ImportCommand extends Command
{
    public const COMMAND_NAME = 'mautic:import';

    public function __construct(
        private TranslatorInterface $translator,
        private ImportModel $importModel,
        private ProcessSignalService $processSignalService,
        private UserTokenSetter $userTokenSetter,
        private LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->addOption('--id', '-i', InputOption::VALUE_OPTIONAL, 'Specific ID to import. Defaults to next in the queue.', false)
            ->addOption('--limit', '-l', InputOption::VALUE_OPTIONAL, 'Maximum number of records to import for this script execution.', 0)
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> command starts to import CSV files when some are created.

<info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $start    = microtime(true);
        $progress = new Progress($output);
        $id       = (int) $input->getOption('id');
        $limit    = (int) $input->getOption('limit');

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
            $this->importModel->beginImport($import, $progress, $limit);
        } catch (ImportFailedException $e) {
            $output->writeln('<error>'.$this->translator->trans(
                'mautic.lead.import.failed',
                [
                    '%reason%' => $import->getStatusInfo(),
                ]
            ).'</error>');

            $this->logError($import, $e);

            return Command::FAILURE;
        } catch (ImportDelayedException $e) {
            $output->writeln('<info>'.$this->translator->trans(
                'mautic.lead.import.delayed',
                [
                    '%reason%' => $import->getStatusInfo(),
                ]
            ).'</info>');

            $this->logError($import, $e);

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

        return Command::SUCCESS;
    }

    protected static $defaultDescription = 'Imports data to Mautic';

    private function logError(Import $import, \Exception $exception): void
    {
        $message = ' Import id: '.$import->getId();
        $message .= ' Import Status: '.$import->getStatus();
        $message .= ' Reason: '.$import->getStatusInfo();
        $message .= ' Exception: '.$exception;

        $this->logger->warning($message);
    }
}
