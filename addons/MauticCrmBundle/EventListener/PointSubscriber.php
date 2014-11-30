<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticCrmBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\FormBundle\Event\SubmissionEvent;
use Mautic\FormBundle\FormEvents;
use MauticAddon\MauticCrmBundle\MapperEvents;
use Mautic\PointBundle\Event\PointBuilderEvent;
use Mautic\PointBundle\Event\TriggerBuilderEvent;
use Mautic\PointBundle\PointEvents;

/**
 * Class PointSubscriber
 */
class PointSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    static public function getSubscribedEvents()
    {
        return array(
            PointEvents::POINT_ON_BUILD     => array('onPointBuild', 0),
            PointEvents::TRIGGER_ON_BUILD   => array('onTriggerBuild', 0),
            MapperEvents::SYNC_DATA         => array('onSyncData', 0)
        );
    }

    /**
     * @param PointBuilderEvent $event
     */
    public function onPointBuild(PointBuilderEvent $event)
    {

    }

    /**
     * @param TriggerBuilderEvent $event
     */
    public function onTriggerBuild(TriggerBuilderEvent $event)
    {
        $sendEvent = array(
            'label'       => 'mautic.crm.point.trigger.syncdata',
            'callback'    => array('\\Mautic\\MauticCrmBundle\\Helper\\PointEventHelper', 'syncData')
        );

        $event->addEvent('mapper.sync', $sendEvent);
    }

    /**
     * Trigger point actions for email send
     *
     * @param EmailSendEvent $event
     */
    public function onSyncData( $event)
    {
        $this->factory->getModel('point')->triggerAction('mapper.sync', $event->getEmail());
    }
}
