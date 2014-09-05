<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\EventListener;

use Mautic\ApiBundle\Event\RouteEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\PointBundle\Event as Events;
use Mautic\PointBundle\PointEvents;

/**
 * Class PointSubscriber
 *
 * @package Mautic\PointBundle\EventListener
 */
class PointSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            PointEvents::POINT_POST_SAVE     => array('onPointPostSave', 0),
            PointEvents::POINT_POST_DELETE   => array('onPointDelete', 0),
            PointEvents::TRIGGER_POST_SAVE     => array('onTriggerPostSave', 0),
            PointEvents::TRIGGER_POST_DELETE   => array('onTriggerDelete', 0)
        );
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\PointEvent $event
     */
    public function onPointPostSave(Events\PointEvent $event)
    {
        $point = $event->getPoint();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "point",
                "object"    => "point",
                "objectId"  => $point->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\PointEvent $event
     */
    public function onPointDelete(Events\PointEvent $event)
    {
        $point = $event->getPoint();
        $log = array(
            "bundle"     => "point",
            "object"     => "point",
            "objectId"   => $point->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $point->getName()),
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\TriggerEvent $event
     */
    public function onTriggerPostSave(Events\TriggerEvent $event)
    {
        $trigger = $event->getTrigger();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "point",
                "object"    => "trigger",
                "objectId"  => $trigger->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\TriggerEvent $event
     */
    public function onTriggerDelete(Events\TriggerEvent $event)
    {
        $trigger = $event->getTrigger();
        $log = array(
            "bundle"     => "point",
            "object"     => "trigger",
            "objectId"   => $trigger->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $trigger->getName()),
            "ipAddress"  => $this->request->server->get('REMOTE_ADDR')
        );
        $this->factory->getModel('core.auditLog')->writeToLog($log);
    }
}