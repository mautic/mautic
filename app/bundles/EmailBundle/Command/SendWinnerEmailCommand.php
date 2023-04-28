<?php

namespace Mautic\EmailBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Exception\RecordNotFoundException;
use Mautic\CoreBundle\Helper\ExitCode;
use Mautic\EmailBundle\Model\AbTest\SendWinnerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends email to winner variant after predetermined amount of time.
 */
class SendWinnerEmailCommand extends ModeratedCommand
{
    public const COMMAND_NAME = 'mautic:email:sendwinner';

    private $sendWinnerService;

    public function __construct(SendWinnerService $sendWinnerService)
    {
        parent::__construct();

        $this->sendWinnerService = $sendWinnerService;
    }

    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
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
     * @return int
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $emailId       = $input->getOption('id');
        $moderationKey = sprintf('%s-%s', self::COMMAND_NAME, $emailId);

        if (!$this->checkRunStatus($input, $output, $moderationKey)) {
            return ExitCode::SUCCESS;
        }

        try {
            $this->sendWinnerService->processWinnerEmails($emailId);
            $output->writeln($this->sendWinnerService->getOutputMessages());
        } catch (RecordNotFoundException $e) {
            $output->writeln($e->getMessage());
        }

        if (true === $this->sendWinnerService->shouldTryAgain()) {
            return ExitCode::TEMPORARY_FAILURE;
        }

        $this->completeRun();

        return ExitCode::SUCCESS;
    }
}
