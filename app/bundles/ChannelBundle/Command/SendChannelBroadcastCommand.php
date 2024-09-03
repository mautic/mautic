<?php

namespace Mautic\ChannelBundle\Command;

use Mautic\ChannelBundle\ChannelEvents;
use Mautic\ChannelBundle\Event\ChannelBroadcastEvent;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CLI Command to send a scheduled broadcast.
 */
class SendChannelBroadcastCommand extends ModeratedCommand
{
    public function __construct(
        private TranslatorInterface $translator,
        private EventDispatcherInterface $dispatcher,
        PathsHelper $pathsHelper,
        CoreParametersHelper $coreParametersHelper
    ) {
        parent::__construct($pathsHelper, $coreParametersHelper);
    }

    protected function configure()
    {
        $this->setName('mautic:broadcasts:send')
            ->setHelp(
                <<<'EOT'
                The <info>%command.name%</info> command is send a channel broadcast to pending contacts.

<info>php %command.full_name% --channel=email --id=3</info>
EOT
            )
            ->setDefinition(
                [
                    new InputOption(
                        'channel', 'c', InputOption::VALUE_OPTIONAL,
                        'A specific channel to process broadcasts for pending contacts.'
                    ),
                    new InputOption(
                        'id', 'i', InputOption::VALUE_OPTIONAL,
                        'The ID for a specifc channel to process broadcasts for pending contacts.'
                    ),
                    new InputOption(
                        'min-contact-id', null, InputOption::VALUE_OPTIONAL,
                        'Min contact ID to filter recipients.'
                    ),
                    new InputOption(
                        'max-contact-id', null, InputOption::VALUE_OPTIONAL,
                        'Max contact ID to filter recipients.'
                    ),
                    new InputOption(
                        'limit', 'l', InputOption::VALUE_OPTIONAL,
                        'Limit how many contacts to load from database to process.'
                    ),
                    new InputOption(
                        'batch', 'b', InputOption::VALUE_OPTIONAL,
                        'Limit how many messages to send at once.'
                    ),
                ]
            )->addOption(
                '--thread-id',
                null,
                InputOption::VALUE_OPTIONAL,
                'The number of this current process if running multiple in parallel.'
            )
            ->addOption(
                '--max-threads',
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum number of processes you intend to run in parallel.'
            );

        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channel       = $input->getOption('channel');
        $channelId     = $input->getOption('id');
        $limit         = $input->getOption('limit');
        $batch         = $input->getOption('batch');
        $minContactId  = $input->getOption('min-contact-id');
        $maxContactId  = $input->getOption('max-contact-id');
        $threadId      = $input->getOption('thread-id');
        $maxThreads    = $input->getOption('max-threads');
        $key           = sprintf('%s-%s-%s-%s', $channel, $channelId, $threadId, $maxThreads);

        if ($threadId && $maxThreads) {
            if ((int) $threadId > (int) $maxThreads) {
                $output->writeln('--thread-id cannot be larger than --max-thread');

                return \Symfony\Component\Console\Command\Command::FAILURE;
            }
        }

        if (!$this->checkRunStatus($input, $output, $key)) {
            return \Symfony\Component\Console\Command\Command::SUCCESS;
        }

        $event = new ChannelBroadcastEvent($channel, $channelId, $output);

        if ($limit) {
            $event->setLimit((int) $limit);
        }

        if ($batch) {
            $event->setBatch((int) $batch);
        }

        if ($minContactId) {
            $event->setMinContactIdFilter((int) $minContactId);
        }

        if ($maxContactId) {
            $event->setMaxContactIdFilter((int) $maxContactId);
        }

        if ($threadId) {
            $event->setThreadId((int) $threadId);
        }

        if ($maxThreads) {
            $event->setMaxThreads((int) $maxThreads);
        }

        $this->dispatcher->dispatch($event, ChannelEvents::CHANNEL_BROADCAST);

        $results = $event->getResults();

        $rows = [];
        foreach ($results as $channel => $counts) {
            $rows[] = [$channel, $counts['success'], $counts['failed']];
        }

        // Put a blank line after anything the event spits out
        $output->writeln('');
        $output->writeln('');

        $table = new Table($output);
        $table
            ->setHeaders([$this->translator->trans('mautic.core.channel'), $this->translator->trans('mautic.core.channel.broadcast_success_count'), $this->translator->trans('mautic.core.channel.broadcast_failed_count')])
            ->setRows($rows);
        $table->render();

        $this->completeRun();

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }

    protected static $defaultDescription = 'Process contacts pending to receive a channel broadcast.';
}
