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
        $import   = $model->processNext($progress);

        // No import waiting in the queue
        if ($import === null) {
            return 0;
        }

        // Import failed
        if ($import->getStatus() === $import::FAILED) {
            $output->writeln('<error>'.$translator->trans(
                'mautic.lead.import.failed',
                [
                    '%reason%' => $import->getStatusInfo(),
                ]
            ).'</error>');

            return 1;
        }

        // Import was delayed
        if ($import->getStatus() === $import::DELAYED) {
            $output->writeln('<info>'.$translator->trans(
                'mautic.lead.import.delayed',
                [
                    '%reason%' => $import->getStatusInfo(),
                ]
            ).'</info>');
        }

        // Success
        $output->writeln('<info>'.$translator->trans(
            'mautic.lead.import.result',
            [
                '%lines%'   => $import->getLineCount(),
                '%created%' => $import->getInsertedCount(),
                '%updated%' => $import->getUpdatedCount(),
                '%ignored%' => $import->getIgnoredCount(),
                '%time%'    => round(microtime(true) - $start, 2),
            ]
        ).'</info>');

        return 0;
    }
}
