<?php

namespace Mautic\LeadBundle\Command;

use Mautic\LeadBundle\Exception\ImportDelayedException;
use Mautic\LeadBundle\Exception\ImportFailedException;
use Mautic\LeadBundle\Helper\Progress;
use Mautic\LeadBundle\Model\ImportModel;
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
        private ImportModel $importModel
    ) {
        parent::__construct();
    }

    protected function configure()
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

        if ($id) {
            $import = $this->importModel->getEntity($id);

            // This specific import was not found
            if (!$import) {
                $output->writeln('<error>'.$this->translator->trans('mautic.core.error.notfound', [], 'flashes').'</error>');

                return \Symfony\Component\Console\Command\Command::FAILURE;
            }
        } else {
            $import = $this->importModel->getImportToProcess();

            // No import waiting in the queue. Finish silently.
            if (null === $import) {
                return \Symfony\Component\Console\Command\Command::SUCCESS;
            }
        }

        $output->writeln('<info>'.$this->translator->trans(
            'mautic.lead.import.is.starting',
            [
                '%id%'    => $import->getId(),
                '%lines%' => $import->getLineCount(),
            ]
        ).'</info>');

        try {
            $this->importModel->beginImport($import, $progress, $limit);
        } catch (ImportFailedException) {
            $output->writeln('<error>'.$this->translator->trans(
                'mautic.lead.import.failed',
                [
                    '%reason%' => $import->getStatusInfo(),
                ]
            ).'</error>');

            return \Symfony\Component\Console\Command\Command::FAILURE;
        } catch (ImportDelayedException) {
            $output->writeln('<info>'.$this->translator->trans(
                'mautic.lead.import.delayed',
                [
                    '%reason%' => $import->getStatusInfo(),
                ]
            ).'</info>');

            return \Symfony\Component\Console\Command\Command::SUCCESS;
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

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected static $defaultDescription = 'Imports data to Mautic';
}
