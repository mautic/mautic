<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Command;

use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\EmailBundle\Model\AbTest\SendWinnerService;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends email to winner variant after predetermined amount of time.
 */
class SendWinnerEmailCommand extends ContainerAwareCommand
{
    private $sendWinnerService;

    public function __construct(SendWinnerService $sendWinnerService)
    {
        $this->sendWinnerService = $sendWinnerService;
    }

    protected function configure()
    {
        $this
            ->setName('mautic:email:sendwinner')
            ->setDescription('Send winner email variant to remaining contacts')
            ->addOption('--id', null, InputOption::VALUE_OPTIONAL, 'Parent variant email id.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used to send winner email variant to remaining contacts after predetermined amount of timeá

<info>php %command.full_name%</info>
EOT
            );

        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->sendWinnerService->processWinnerEmails($input->getOption('id'));

        $output->writeln($this->sendWinnerService->getOutputMessages());

        if (true === $this->sendWinnerService->shouldTryAgain()) {
            return ExitCode::TEMPORARY_FAILURE;
        }

        return ExitCode::SUCCESS;
    }
}
