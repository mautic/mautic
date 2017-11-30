<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChannelBundle\Command;

use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessMarketingMessagesQueueCommand.
 */
class ProcessMarketingMessagesQueueCommand extends ModeratedCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:messages:send')
            ->setAliases(
                [
                    'mautic:campaigns:messagequeue',
                    'mautic:campaigns:messages',
                ]
            )
            ->setDescription('Process sending of messages queue.')
            ->addOption(
                '--channel',
                '-c',
                InputOption::VALUE_OPTIONAL,
                'Channel to use for sending messages i.e. email, sms.',
                null
            )
            ->addOption('--channel-id', '-i', InputOption::VALUE_REQUIRED, 'The ID of the message i.e. email ID, sms ID.')
            ->addOption('--message-id', '-m', InputOption::VALUE_REQUIRED, 'ID of a specific queued message');

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processed  = 0;
        $container  = $this->getContainer();
        $translator = $container->get('translator');
        $channel    = $input->getOption('channel');
        $channelId  = $input->getOption('channel-id');
        $messageId  = $input->getOption('message-id');
        $key        = $channel.$channelId.$messageId;

        if (!$this->checkRunStatus($input, $output, $key)) {
            return 0;
        }

        /** @var \Mautic\ChannelBundle\Model\MessageQueueModel $model */
        $model = $container->get('mautic.channel.model.queue');

        $output->writeln('<info>'.$translator->trans('mautic.campaign.command.process.messages').'</info>');

        if ($messageId) {
            if ($message = $model->getEntity($messageId)) {
                $processed = intval($model->processMessageQueue($message));
            }
        } else {
            $processed = intval($model->sendMessages($channel, $channelId));
        }

        $output->writeln('<comment>'.$translator->trans('mautic.campaign.command.messages.sent', ['%events%' => $processed]).'</comment>'."\n");

        $this->completeRun();

        return 0;
    }
}
