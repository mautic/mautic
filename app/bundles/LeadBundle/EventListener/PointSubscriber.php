<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\PointEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PointSubscriber implements EventSubscriberInterface
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
            'group'    => 'mautic.lead.point.trigger',
            'label'    => 'mautic.lead.point.trigger.changelists',
            'callback' => ['\\Mautic\\LeadBundle\\Helper\\PointEventHelper', 'changeLists'],
            'formType' => 'leadlist_action',
        ];

        $event->addEvent('lead.changelists', $changeLists);

        // modify tags
        $action = [
            'group'    => 'mautic.lead.point.trigger',
            'label'    => 'mautic.lead.lead.events.changetags',
            'formType' => 'modify_lead_tags',
            'callback' => '\Mautic\LeadBundle\Helper\EventHelper::updateTags',
        ];
        $event->addEvent('lead.changetags', $action);
    }
}
