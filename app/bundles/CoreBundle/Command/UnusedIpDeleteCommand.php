<?php

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\LeadBundle\Model\IpAddressModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to delete unused IP addresses.
 */
class UnusedIpDeleteCommand extends ModeratedCommand
{
    private const DEFAULT_LIMIT = 10000;

    public function __construct(
        private IpAddressModel $ipAddressModel,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure(): void
    {
        $this->setName('mautic:unusedip:delete')
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
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->checkRunStatus($input, $output)) {
            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        try {
            $limit       = $input->getOption('limit') ?? self::DEFAULT_LIMIT;
            $deletedRows = $this->ipAddressModel->deleteUnusedIpAddresses((int) $limit);
            $output->writeln(sprintf('<info>%s unused IP addresses have been deleted</info>', $deletedRows));
        } catch (\Doctrine\DBAL\Exception $e) {
            $output->writeln(sprintf('<error>Deletion of unused IP addresses failed because of database error: %s</error>', $e->getMessage()));
            $this->completeRun();

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }
        $this->completeRun();

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected static $defaultDescription = 'Deletes IP addresses that are not used in any other database table';
}
