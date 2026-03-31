<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Command;

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\EmailBundle\Service\NonOpenersService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'mautic:emails:resend-nonopeners',
    description: 'Resend a segment email to contacts who did not open it',
)]
class ResendNonOpenersCommand extends Command
{
    public function __construct(
        private NonOpenersService $nonOpenersService,
        private EmailModel $emailModel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email-id', InputArgument::REQUIRED, 'ID of the segment email to resend')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show info without executing the resend');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io      = new SymfonyStyle($input, $output);
        $emailId = (int) $input->getArgument('email-id');

        $email = $this->emailModel->getEntity($emailId);
        if (null === $email) {
            $io->error(sprintf('Email with ID %d not found.', $emailId));

            return Command::FAILURE;
        }

        if (!$this->nonOpenersService->canResend($email)) {
            $io->error(sprintf('Email "%s" (ID %d) cannot be resent. It must be a segment email that has finished sending, has not already been resent, and is not itself a resend.', $email->getName(), $emailId));

            return Command::FAILURE;
        }

        if ($input->getOption('dry-run')) {
            $io->success(sprintf('Dry run: Email "%s" (ID %d) is eligible for resend to non-openers.', $email->getName(), $emailId));

            return Command::SUCCESS;
        }

        $result = $this->nonOpenersService->resend($emailId);

        $io->success(sprintf(
            'Resend created: new email ID %d, %d segment(s) cloned. The broadcast cron will send it.',
            $result['emailId'],
            count($result['segmentIds']),
        ));

        return Command::SUCCESS;
    }
}
