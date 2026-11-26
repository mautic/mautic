<?php

declare(strict_types=1);

namespace Mautic\StageBundle\EventListener;

use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\StageBundle\Entity\Stage;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

final readonly class StageSubscriber
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
    public function onStagePostSave(EntityPostSave $event): void
    {
        $stage = $event->getEntity();
        if (!$stage instanceof Stage) {
            return;
        }

        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'stage',
                'object'    => 'stage',
                'objectId'  => $stage->getId(),
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
    public function onStageDelete(EntityPostDelete $event): void
    {
        $stage = $event->getEntity();
        if (!$stage instanceof Stage) {
            return;
        }

        $log = [
            'bundle'    => 'stage',
            'object'    => 'stage',
            'objectId'  => $stage->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $stage->getName()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);
    }
}
