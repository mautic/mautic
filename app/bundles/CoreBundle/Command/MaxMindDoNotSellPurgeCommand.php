<?php

namespace Mautic\CoreBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\ValidatorBuilder;

/**
 * CLI Command to purge data from Mautic that appears on the
 * MaxMind Do Not Sell list.
 */
class MaxMindDoNotSellPurgeCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:max-mind:purge')
            ->setDescription('Purge data connected to MaxMind Do Not Sell list.')
            ->addArgument(
                'ips',
                InputArgument::IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'One or more specific IPs to purge.'
            )
            ->addOption(
                'dry-run',
                'd',
                InputOption::VALUE_NONE,
                'Get a list of data that will be purged.'
            )
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command will purge all data from Mautic which is related to any IP found on the MaxMind Do Not Sell List.

<info>php %command.full_name% --ip=x.x.x.x</info>

You may pass 1 or more IPs to be purged.

<info>php %command.full_name% --save-php-config</info>

Performs a dry-run which will not actually purge any data, but will produce a list of what would be purged.
EOT
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dryRun = $input->getOption('dry-run');
        $ips    = $input->getArgument('ips');

        if ($dryRun) {
            $output->writeln("Dry run successful.\n");
        }

        if ($ips) {
            $ipList = implode(', ', $ips);
            $output->writeln('Purging data for IPs: '.$ipList."\n");
            $validator = Validation::createValidator();
            foreach ($ips as $ip) {
                $violations = $validator->validate($ip, [new Ip()]);
            }
            if (count($violations)) {
                $output->writeln('Not IPs');
            }
        }

        // Retrieve max mind IP list
        // Get a list of contacts with this IP
        // Get all relevant data points
        // Check each point against audit log to see if it cam from Max Mind
        // remove data points that come from max mind
        // generate report of removed data

        // Warn before deleting data
        // Silent flag

        return 0;
    }
}
