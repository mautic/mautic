<?php

namespace Mautic\CoreBundle\Command;

use Doctrine\DBAL\DBALException;
use Mautic\LeadBundle\Model\IpAddressModel;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to delete unused IP addresses.
 */
class UnusedIpDeleteCommand extends ContainerAwareCommand
{
    private const DEFAULT_LIMIT = 10000;

    protected function configure(): void
    {
        $this->setName('mautic:unusedip:delete')
            ->setDescription('Deletes IP addresses that are not used in any other database table')
            ->addOption(
                '--limit',
                '-l',
                InputOption::VALUE_OPTIONAL,
                'LIMIT for deleted rows',
                self::DEFAULT_LIMIT
            )
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command is used to delete IP addresses that are not used in any other database table.

<info>php %command.full_name%</info>
EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $container = $this->getContainer();
        /** @var IpAddressModel $ipAddressModel */
        $ipAddressModel = $container->get('mautic.lead.model.ipaddress');

        try {
            $limit       = $input->getOption('limit');
            $deletedRows = $ipAddressModel->deleteUnusedIpAddresses((int) $limit);
            $output->writeln(sprintf('<info>%s unused IP addresses have been deleted</info>', $deletedRows));
        } catch (DBALException $e) {
            $output->writeln(sprintf('<error>Deletion of unused IP addresses failed because of database error: %s</error>', $e->getMessage()));

            return 1;
        }

        return 0;
    }
}
