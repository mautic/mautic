<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Command;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\ChannelBroadcastEvent;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * CLI Command to send a scheduled broadcast.
 */
class SendChannelBroadcastCommand extends ModeratedCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('mautic:broadcasts:send')
            ->setDescription('Process contacts pending to receive a channel broadcast.')
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
                ]
            );

        parent::configure();
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $channel   = $input->getOption('channel');
        $channelId = $input->getOption('id');
        $key       = $channel.$channelId;
        if (!$this->checkRunStatus($input, $output, $key)) {
            return 0;
        }

        $translator = $this->getContainer()->get('translator');
        $translator->setLocale($this->getContainer()->get('mautic.helper.core_parameters')->getParameter('locale'));

        $dispatcher = $this->getContainer()->get('event_dispatcher');
        $event      = $dispatcher->dispatch(
            CoreEvents::CHANNEL_BROADCAST,
            new ChannelBroadcastEvent($channel, $channelId, $output)
        );

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
            ->setHeaders([$translator->trans('mautic.core.channel'), $translator->trans('mautic.core.channel.broadcast_success_count'), $translator->trans('mautic.core.channel.broadcast_failed_count')])
            ->setRows($rows);
        $table->render();

        $this->completeRun();

        return 0;
    }
}
