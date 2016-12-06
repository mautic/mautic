<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\PointEvents;

/**
 * Class PointSubscriber.
 */
class PointSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PointEvents::TRIGGER_ON_BUILD => ['onTriggerBuild', 0],
        ];
    }

    /**
     * @param TriggerBuilderEvent $event
     */
    public function onTriggerBuild(TriggerBuilderEvent $event)
    {
        $changeLists = [
            'group'    => 'mautic.campaign.point.trigger',
            'label'    => 'mautic.campaign.point.trigger.changecampaigns',
            'callback' => ['\\Mautic\\CampaignBundle\\Helper\\CampaignEventHelper', 'addRemoveLead'],
            'formType' => 'campaignevent_addremovelead',
        ];

        $event->addEvent('campaign.changecampaign', $changeLists);
    }
}
