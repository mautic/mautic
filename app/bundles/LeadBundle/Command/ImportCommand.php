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
            ->setDefinition(
                [
                    new InputOption(
                        'batch',
                        'b',
                        InputOption::VALUE_OPTIONAL,
                        'Batch limit for storing to database',
                        25
                    ),
                    new InputOption('dry-run', 'r', InputOption::VALUE_NONE, 'Do a dry run without actually saving anything.'),
                ]
            )
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command starts to import CSV files when some are submitted.

<info>php %command.full_name%</info>

Specify the batch number.

<info>php %command.full_name% --batch=30</info>

You can also optionally specify a dry run without saving any records:

<info>php %command.full_name% --batch=30 --dry-run</info>
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        /** @var \Mautic\LeadBundle\Model\ImportModel $model */
        $model = $container->get('mautic.lead.model.import');

        $batch    = $input->getOption('batch');
        $dryRun   = $input->getOption('dry-run');
        $progress = new Progress();

        $import = $model->processNext($progress);
        echo '<pre>';
        var_dump($progress, $import);
        die('</pre>');

        // if ('dev' == MAUTIC_ENV) {
        //     $output->writeln('<comment>Debug</comment>');
        //     $debug = $event->getDebug();

        //     foreach ($debug as $key => $query) {
        //         $output->writeln("<info>$key</info>");
        //         $output->writeln($query);
        //     }
        // }

        return 0;
    }
}
