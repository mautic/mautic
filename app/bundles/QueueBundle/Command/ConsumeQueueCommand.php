<?php

namespace Mautic\QueueBundle\Command;

use Mautic\QueueBundle\Queue\QueueService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to process orders that have been queued.
 */
class ConsumeQueueCommand extends Command
{
    private QueueService $queueService;

    public function __construct(QueueService $queueService)
    {
        parent::__construct();

        $this->queueService = $queueService;
    }

    protected function configure()
    {
        $this->setName('mautic:queue:process')
            ->setDescription('Process queues')
            ->addOption(
                '--queue-name',
                '-i',
                InputOption::VALUE_REQUIRED,
                'Process queues orders for a specific queue.',
                null
            )
            ->addOption(
                '--messages',
                '-m',
                InputOption::VALUE_OPTIONAL,
                'Number of messages from the queue to process. Default is infinite',
                null
            )
            ->addOption(
                '--timeout',
                '-t',
                InputOption::VALUE_REQUIRED,
                'Set a graceful execution time at this many seconds in the future.',
                null
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->queueService->isQueueEnabled()) {
            $output->writeLn('You have not configured mautic to use queue mode, nothing will be processed');

            return 0;
        }

        $queueName = $input->getOption('queue-name');
        if (empty($queueName)) {
            $output->writeLn('You did not provide a valid queue name');

            return 0;
        }

        $messages = $input->getOption('messages');
        if (0 > $messages) {
            $output->writeLn('You did not provide a valid number of messages. It should be null or greater than 0');

            return 0;
        }

        $timeout = $input->getOption('timeout');
        if (0 > $timeout) {
            $output->writeLn('You did not provide a valid number of seconds. It should be null or greater than 0');

            return 0;
        }

        $this->queueService->consumeFromQueue($queueName, $messages, $timeout);

        return 0;
    }
}
