<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Command;

use Mautic\LeadBundle\Helper\Progress;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to import data.
 */
class ImportCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:import')
            ->setDescription('Imports data to Mautic')
            ->addOption('--id', '-i', InputOption::VALUE_OPTIONAL, 'Specific ID to import. Defaults to next in the queue.', false)
            ->addOption('--limit', '-l', InputOption::VALUE_OPTIONAL, 'Maximum number of records to import for this script execution.', 0)
            ->setHelp(
                <<<'EOT'
The <info>%command.name%</info> command starts to import CSV files when some are created.

<info>php %command.full_name%</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(true);

        /** @var \Symfony\Bundle\FrameworkBundle\Translation\Translator $translator */
        $translator = $this->getContainer()->get('translator');

        /** @var \Mautic\LeadBundle\Model\ImportModel $model */
        $model = $this->getContainer()->get('mautic.lead.model.import');

        $progress = new Progress($output);
        $id       = (int) $input->getOption('id');
        $limit    = (int) $input->getOption('limit');

        if ($id) {
            $import = $model->getEntity($id);

            // This specific import was not found
            if (!$import) {
                $output->writeln('<error>'.$translator->trans('mautic.core.error.notfound', [], 'flashes').'</error>');

                return 1;
            }
        } else {
            $import = $model->getImportToProcess();

            // No import waiting in the queue. Finish silently.
            if ($import === null) {
                return 0;
            }
        }

        $output->writeln('<info>'.$translator->trans(
            'mautic.lead.import.is.starting',
            [
                '%id%'    => $import->getId(),
                '%lines%' => $import->getLineCount(),
            ]
        ).'</info>');

        try {
            $model->beginImport($import, $progress, $limit);
        } catch (ImportFailedException $e) {
            $model->logDebug($e->getMessage(), $import);

            $output->writeln('<error>'.$translator->trans(
                'mautic.lead.import.failed',
                [
                    '%reason%' => $import->getStatusInfo(),
                ]
            ).'</error>');

            return 1;
        } catch (ImportDelayedException $e) {
            $this->logDebug($e->getMessage());

            $output->writeln('<info>'.$translator->trans(
                'mautic.lead.import.delayed',
                [
                    '%reason%' => $import->getStatusInfo(),
                ]
            ).'</info>');

            return 0;
        }

        // Success
        $output->writeln('<info>'.$translator->trans(
            'mautic.lead.import.result',
            [
                '%lines%'   => $import->getProcessedRows(),
                '%created%' => $import->getInsertedCount(),
                '%updated%' => $import->getUpdatedCount(),
                '%ignored%' => $import->getIgnoredCount(),
                '%time%'    => round(microtime(true) - $start, 2),
            ]
        ).'</info>');

        return 0;
    }
}
