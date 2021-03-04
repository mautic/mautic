<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Doctrine\DBAL\DBALException;
use Mautic\CoreBundle\Helper\ExitCode;
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
        $em             = $this->getContainer()->get('doctrine')->getManager();
        $ipAddressRepo  = $em->getRepository('MauticCoreBundle:IpAddress');

        try {
            $limit       = $input->getOption('limit');
            $deletedRows = $ipAddressRepo->deleteUnusedIpAddresses((int) $limit);
            $output->writeln(sprintf('<info>%s unused IP addresses have been deleted</info>', $deletedRows));
        } catch (DBALException $e) {
            $output->writeln(sprintf('<error>Deletion of unused IP addresses failed because of database error: %s</error>', $e->getMessage()));

            return ExitCode::FAILURE;
        }

        return ExitCode::SUCCESS;
    }
}
