<?php

namespace Mautic\PointBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\PointBundle\Event as Events;
use Mautic\PointBundle\PointEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PointSubscriber implements EventSubscriberInterface
{
    /**
     * @var IpLookupHelper
     */
    private $ipLookupHelper;

    /**
     * @var AuditLogModel
     */
    private $auditLogModel;

    public function __construct(IpLookupHelper $ipLookupHelper, AuditLogModel $auditLogModel)
    {
        $this->ipLookupHelper = $ipLookupHelper;
        $this->auditLogModel  = $auditLogModel;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            PointEvents::POINT_POST_SAVE     => ['onPointPostSave', 0],
            PointEvents::POINT_POST_DELETE   => ['onPointDelete', 0],
            PointEvents::TRIGGER_POST_SAVE   => ['onTriggerPostSave', 0],
            PointEvents::TRIGGER_POST_DELETE => ['onTriggerDelete', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onPointPostSave(Events\PointEvent $event)
    {
        $point = $event->getPoint();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'point',
                'object'    => 'point',
                'objectId'  => $point->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onPointDelete(Events\PointEvent $event)
    {
        $point = $event->getPoint();
        $log   = [
            'bundle'    => 'point',
            'object'    => 'point',
            'objectId'  => $point->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $point->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }

    /**
     * Add an entry to the audit log.
     */
    public function onTriggerPostSave(Events\TriggerEvent $event)
    {
        $trigger = $event->getTrigger();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'point',
                'object'    => 'trigger',
                'objectId'  => $trigger->getId(),
                'action'    => ($event->isNew()) ? 'create' : 'update',
                'details'   => $details,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onTriggerDelete(Events\TriggerEvent $event)
    {
        $trigger = $event->getTrigger();
        $log     = [
            'bundle'    => 'point',
            'object'    => 'trigger',
            'objectId'  => $trigger->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $trigger->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }
}
