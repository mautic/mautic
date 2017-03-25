<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Event\CampaignTriggerEvent;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * Class TriggerConsumerCampaignCommand.
 */
class TriggerConsumerCampaignCommand extends ModeratedCommand
{
    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:trigger:consume')
            ->setDescription('Trigger timed events for published campaigns.')
            ->addOption(
                '--campaign-id',
                '-i',
                InputOption::VALUE_OPTIONAL,
                'Trigger events for a specific campaign.  Otherwise, all campaigns will be triggered.',
                null
            )
            ->addOption('--scheduled-only', null, InputOption::VALUE_NONE, 'Trigger only scheduled events')
            ->addOption('--negative-only', null, InputOption::VALUE_NONE, 'Trigger only negative events, i.e. with a "no" decision path.')
            ->addOption('--batch-limit', '-l', InputOption::VALUE_OPTIONAL, 'Set batch size of contacts to process per round. Defaults to 100.', 100)
            ->addOption(
                '--max-events',
                '-m',
                InputOption::VALUE_OPTIONAL,
                'Set max number of events to process per campaign for this script execution. Defaults to all.',
                0
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        /** @var \Mautic\CampaignBundle\Model\EventModel $model */
        $model = $container->get('mautic.campaign.model.event');
        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel    = $container->get('mautic.campaign.model.campaign');
        $this->dispatcher = $container->get('event_dispatcher');
        $translator       = $container->get('translator');
        $em               = $container->get('doctrine')->getManager();
        $id               = $input->getOption('campaign-id');
        $scheduleOnly     = $input->getOption('scheduled-only');
        $negativeOnly     = $input->getOption('negative-only');
        $batch            = $input->getOption('batch-limit');
        $max              = $input->getOption('max-events');

        if (!$this->checkRunStatus($input, $output, $id)) {
            return 0;
        }

        $this->connection = new AMQPStreamConnection('rabbitmq', 5672, 'guest', 'guest');
        $channel = $this->connection->channel();
        $channel->queue_declare('trigger_start', false, false, false, false);

        if ($id) {
            /** @var \Mautic\CampaignBundle\Entity\Campaign $campaign */
            $campaign = $campaignModel->getEntity($id);

            if ($campaign !== null && $campaign->isPublished()) {
                if (!$this->dispatchTriggerEvent($campaign)) {
                    return 0;
                }

                $totalProcessed = 0;
                $output->writeln('<info>'.$translator->trans('mautic.campaign.trigger.triggering', ['%id%' => $id]).'</info>');

                    //trigger starting action events for newly added contacts
                    $output->writeln('<comment>'.$translator->trans('mautic.campaign.trigger.starting').'</comment>');
                    $processed = $model->consumeStartingEvents($channel,$campaign, $totalProcessed, $batch, $max, $output);
                    $output->writeln(
                        '<comment>'.$translator->trans('mautic.campaign.trigger.events_executed', ['%events%' => $processed]).'</comment>'."\n"
                    );

                }
        } else {
            $campaigns = $campaignModel->getEntities(
                [
                    'iterator_mode' => true,
                ]
            );

            while (($c = $campaigns->next()) !== false) {
                $totalProcessed = 0;

                // Key is ID and not 0
                $c = reset($c);

                if ($c->isPublished()) {
                    if (!$this->dispatchTriggerEvent($c)) {
                        continue;
                    }

                    $output->writeln('<info>'.$translator->trans('mautic.campaign.trigger.triggering', ['%id%' => $c->getId()]).'</info>');
                        //trigger starting action events for newly added contacts
                        $output->writeln('<comment>'.$translator->trans('mautic.campaign.trigger.starting').'</comment>');
                        $processed = $model->consumeStartingEvents($channel,$c, $totalProcessed, $batch, $max, $output);
                        $output->writeln(
                            '<comment>'.$translator->trans('mautic.campaign.trigger.events_executed', ['%events%' => $processed]).'</comment>'
                            ."\n"
                        );


                }

                $em->detach($c);
                unset($c);
            }

            unset($campaigns);
        }

        $this->completeRun();

        return 0;
    }

    /**
     * @param Campaign $campaign
     *
     * @return bool
     */
    protected function dispatchTriggerEvent(Campaign $campaign)
    {
        if ($this->dispatcher->hasListeners(CampaignEvents::CAMPAIGN_ON_TRIGGER)) {
            /** @var CampaignTriggerEvent $event */
            $event = $this->dispatcher->dispatch(
                CampaignEvents::CAMPAIGN_ON_TRIGGER,
                new CampaignTriggerEvent($campaign)
            );

            return $event->shouldTrigger();
        }

        return true;
    }
}
