<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class TriggerCampaignCommand extends ContainerAwareCommand
{
    protected function configure ()
    {
        $this
            ->setName('mautic:campaign:trigger')
            ->setDescription('Trigger timed events for published campaigns.')
            ->addOption('--campaign-id', null, InputOption::VALUE_OPTIONAL, 'Trigger events for a specific campaign.  Otherwise, all campaigns will be triggered.', null);
    }

    protected function execute (InputInterface $input, OutputInterface $output)
    {
        $campaignId = $input->getOption('campaign-id');
        $container  = $this->getContainer();
        /** @var \Mautic\CoreBundle\Factory\MauticFactory $factory */
        $factory    = $container->get('mautic.factory');
        /** @var \Mautic\CampaignBundle\Model\EventModel $model */
        $model      = $factory->getModel('campaign.event');
        $model->triggerScheduledEvents($campaignId);

        return true;
    }
}