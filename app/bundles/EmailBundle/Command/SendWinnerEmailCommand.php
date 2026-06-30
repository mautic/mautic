<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Exception\RecordNotFoundException;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\EmailBundle\Model\AbTest\SendWinnerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Sends email to winner variant after predetermined amount of time.
 */
final class SendWinnerEmailCommand extends ModeratedCommand
{
    protected static string $defaultDescription = 'Send winner email variant to remaining contacts';

    public const COMMAND_NAME                   = 'mautic:email:sendwinner';

    public function __construct(private SendWinnerService $sendWinnerService, PathsHelper $pathsHelper, CoreParametersHelper $coreParametersHelper)
    {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure(): void
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->addOption('--id', null, InputOption::VALUE_OPTIONAL, 'Parent variant email id.')
            ->setHelp(<<<'EOT'
The <info>%command.name%</info> command is used to send winner email variant to remaining contacts after predetermined amount of time

<info>php %command.full_name%</info>
EOT
            );

        parent::configure();
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $emailId       = null !== $input->getOption('id') ? (int) $input->getOption('id') : null;
        $moderationKey = sprintf('%s-%s', self::COMMAND_NAME, $emailId);

        if (!$this->checkRunStatus($input, $output, $moderationKey)) {
            return Command::SUCCESS;
        }

        try {
            $this->sendWinnerService->processWinnerEmails($emailId);
            $output->writeln($this->sendWinnerService->getOutputMessages());
        } catch (RecordNotFoundException $e) {
            $output->writeln($e->getMessage());
        }

        if (true === $this->sendWinnerService->shouldTryAgain()) {
            return Command::FAILURE;
        }

        $this->completeRun();

        return Command::SUCCESS;
    }
}
