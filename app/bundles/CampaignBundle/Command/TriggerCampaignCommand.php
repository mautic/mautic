<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TriggerCampaignCommand
 */
class TriggerCampaignCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('mautic:campaigns:trigger')
            ->setAliases(array(
                'mautic:campaign:trigger',
                'mautic:trigger:campaigns',
                'mautic:trigger:campaign'
            ))
            ->setDescription('Trigger timed events for published campaigns.')
            ->addOption('--campaign-id', null, InputOption::VALUE_OPTIONAL, 'Trigger events for a specific campaign.  Otherwise, all campaigns will be triggered.', null)
            ->addOption('--scheduled-only', null, InputOption::VALUE_NONE, 'Trigger only scheduled events')
            ->addOption('--negative-only', null, InputOption::VALUE_NONE, 'Trigger only negative events, i.e. with a "no" decision path.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $campaignId = $input->getOption('campaign-id');
        $container  = $this->getContainer();
        /** @var \Mautic\CoreBundle\Factory\MauticFactory $factory */
        $factory    = $container->get('mautic.factory');
        /** @var \Mautic\CampaignBundle\Model\EventModel $model */
        $model      = $factory->getModel('campaign.event');

        $scheduleOnly = $input->getOption('scheduled-only');
        $negativeOnly = $input->getOption('negative-only');

        if (!$negativeOnly) {
            //trigger scheduled events
            $model->triggerScheduledEvents($campaignId);
        }

        if (!$scheduleOnly) {
            //find and trigger "no" path events
            $model->triggerNegativeEvents($campaignId);
        }

        return true;
    }
}
