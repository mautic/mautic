<?php
/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ProcessMarketingMessagesQueueCommand.
 */
class ProcessMarketingMessagesQueueCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:messagequeue')
            ->setAliases(
                [
                    'mautic:campaigns:messages',
                ]
            )
            ->setDescription('Process sending of messages queue.')
            ->addOption(
                '--channel',
                '-c',
                InputOption::VALUE_OPTIONAL,
                'Channel to use for sending messages ie. email, sms.',
                null
            )
            ->addOption('--channelid', '-i', InputOption::VALUE_REQUIRED, 'channel id, is the id of the message ie email id, sms id.')
            ->addOption(
                '--end-date',
                '-t',
                InputOption::VALUE_OPTIONAL,
                'Set end date for updated values.'
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $processed = 0;
        /** @var \Mautic\CoreBundle\Factory\MauticFactory $factory */
        $factory = $container->get('mautic.factory');

        $translator = $factory->getTranslator();
        $channel    = $input->getOption('channel');
        $channelId  = $input->getOption('channelid');

        /** @var \Mautic\CoreBundle\Model\MessageQueueModel $model */
        $model = $factory->getModel('core.messagequeue');

        $output->writeln('<info>'.$translator->trans('mautic.campaign.command.process.messages').'</info>');

        $processed = intval($model->sendMessages($channel, $channelId));

        $output->writeln('<comment>'.$translator->trans('mautic.campaign.command.messages.sent', ['%events%' => $processed]).'</comment>'."\n");

        return 0;
    }
}
