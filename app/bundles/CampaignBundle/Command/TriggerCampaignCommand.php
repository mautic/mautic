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
use Mautic\CampaignBundle\Executioner\KickoffExecutioner;
use Mautic\CampaignBundle\Executioner\ScheduledExecutioner;
use Mautic\CoreBundle\Command\ModeratedCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class TriggerCampaignCommand.
 */
class TriggerCampaignCommand extends ModeratedCommand
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
            ->setName('mautic:campaigns:trigger')
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

        /** @var \Mautic\CampaignBundle\Model\CampaignModel $campaignModel */
        $campaignModel          = $container->get('mautic.campaign.model.campaign');
        $this->dispatcher       = $container->get('event_dispatcher');
        $this->translator       = $container->get('translator');
        $this->em               = $container->get('doctrine')->getManager();
        $this->output           = $output;
        $id                     = $input->getOption('campaign-id');
        $scheduleOnly           = $input->getOption('scheduled-only');
        $negativeOnly           = $input->getOption('negative-only');
        $batchLimit             = $input->getOption('batch-limit');

        /* @var KickoffExecutioner $kickoff */
        $this->kickoff = $container->get('mautic.campaign.executioner.kickoff');
        /* @var ScheduledExecutioner $scheduled */
        $this->scheduled = $container->get('mautic.campaign.executioner.scheduled');

        defined('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED') or define('MAUTIC_CAMPAIGN_SYSTEM_TRIGGERED', 1);

        if (!$this->checkRunStatus($input, $output, $id)) {
            return 0;
        }

        if ($id) {
            /** @var \Mautic\CampaignBundle\Entity\Campaign $campaign */
            if (!$campaign = $campaignModel->getEntity($id)) {
                $output->writeln('<error>'.$this->translator->trans('mautic.campaign.rebuild.not_found', ['%id%' => $id]).'</error>');

                return 0;
            }

            $this->triggerCampaign($campaign, $negativeOnly, $scheduleOnly, $batchLimit);
        } else {
            $campaigns = $campaignModel->getEntities(
                [
                    'iterator_mode' => true,
                ]
            );

            while (($next = $campaigns->next()) !== false) {
                // Key is ID and not 0
                $campaign = reset($next);
                $this->triggerCampaign($campaign, $negativeOnly, $scheduleOnly, $batchLimit);
            }
        }

        $this->completeRun();

        return 0;
    }

    private function triggerCampaign(Campaign $campaign, $negativeOnly, $scheduleOnly, $batchLimit)
    {
        if (!$campaign->isPublished()) {
            return;
        }

        if (!$this->dispatchTriggerEvent($campaign)) {
            return;
        }

        $this->output->writeln('<info>'.$this->translator->trans('mautic.campaign.trigger.triggering', ['%id%' => $campaign->getId()]).'</info>');
        if (!$negativeOnly && !$scheduleOnly) {
            //trigger starting action events for newly added contacts
            $this->output->writeln('<comment>'.$this->translator->trans('mautic.campaign.trigger.starting').'</comment>');
            $counter = $this->kickoff->executeForCampaign($campaign, $batchLimit, $this->output);
            $this->output->writeln(
                '<comment>'.$this->translator->trans('mautic.campaign.trigger.events_executed', ['%events%' => $counter->getExecuted()]).'</comment>'
                ."\n"
            );
        }

        if (!$negativeOnly) {
            $this->output->writeln('<comment>'.$this->translator->trans('mautic.campaign.trigger.scheduled').'</comment>');
            $counter = $this->scheduled->executeForCampaign($campaign, $batchLimit, $this->output);
            $this->output->writeln(
                '<comment>'.$this->translator->trans('mautic.campaign.trigger.events_executed', ['%events%' => $counter->getExecuted()]).'</comment>'
                ."\n"
            );
        }

        /*
        if (!$scheduleOnly) {
            //find and trigger "no" path events
            $output->writeln('<comment>'.$translator->trans('mautic.campaign.trigger.negative').'</comment>');
            $processed = $model->triggerNegativeEvents($c, $totalProcessed, $batch, $max, $output);
            $output->writeln(
                '<comment>'.$translator->trans('mautic.campaign.trigger.events_executed', ['%events%' => $processed]).'</comment>'
                ."\n"
            );
        }
        */

        $this->em->detach($campaign);
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
