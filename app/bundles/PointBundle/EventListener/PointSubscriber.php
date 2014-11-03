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
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

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
            PointEvents::POINT_POST_SAVE     => array('onPointPostSave', 0),
            PointEvents::POINT_POST_DELETE   => array('onPointDelete', 0),
            PointEvents::TRIGGER_POST_SAVE   => array('onTriggerPostSave', 0),
            PointEvents::TRIGGER_POST_DELETE => array('onTriggerDelete', 0),
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0)
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

    /**
     * Compile events for the lead timeline
     *
     * @param LeadTimelineEvent $event
     */
    public function onTimelineGenerate(LeadTimelineEvent $event)
    {
        // Set available event types
        $eventTypeKey = 'point.gained';
        $eventTypeName = $this->translator->trans('mautic.point.event.gained');
        $event->addEventType($eventTypeKey, $eventTypeName);

        // Decide if those events are filtered
        $filter = $event->getEventFilter();
        $loadAllEvents = !isset($filter[0]);
        $eventFilterExists = in_array($eventTypeKey, $filter);

        if (!$loadAllEvents && !$eventFilterExists) {
            return;
        }

        $lead    = $event->getLead();
        $options = array('ipIds' => array(), 'filters' => $filter);

        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        foreach ($lead->getIpAddresses() as $ip) {
            $options['ipIds'][] = $ip->getId();
        }

        /** @var \Mautic\PageBundle\Entity\HitRepository $hitRepository */
        $logRepository = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:PointsChangeLog');

        $logs = $logRepository->getLeadTimelineEvents($lead->getId(), $options);

        // Add the logs to the event array
        foreach ($logs as $log) {
            $event->addEvent(array(
                'event'     => $eventTypeKey,
                'eventLabel' => $eventTypeName,
                'timestamp' => $log['dateAdded'],
                'extra'     => array(
                    'log' => $log
                ),
                'contentTemplate' => 'MauticPointBundle:Timeline:index.html.php'
            ));
        }
    }
}
