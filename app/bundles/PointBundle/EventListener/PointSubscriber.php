<?php

declare(strict_types=1);

namespace Mautic\PointBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\PointBundle\Entity\Point;
use Mautic\PointBundle\Entity\Trigger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class PointSubscriber
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }

    /**
     * Add an entry to the audit log.
     */
    #[AsEventListener]
    public function onPointPostSave(EntityPostSave $event): void
    {
        $point = $event->getEntity();
        if (!$point instanceof Point) {
            return;
        }

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
    #[AsEventListener]
    public function onPointDelete(EntityPostDelete $event): void
    {
        $point = $event->getEntity();
        if (!$point instanceof Point) {
            return;
        }

        $log = [
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
    #[AsEventListener]
    public function onTriggerPostSave(EntityPostSave $event): void
    {
        $trigger = $event->getEntity();
        if (!$trigger instanceof Trigger) {
            return;
        }

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
    #[AsEventListener]
    public function onTriggerDelete(EntityPostDelete $event): void
    {
        $trigger = $event->getEntity();
        if (!$trigger instanceof Trigger) {
            return;
        }

        $log = [
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
