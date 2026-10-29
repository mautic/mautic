<?php

declare(strict_types=1);

namespace Mautic\StageBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\StageBundle\Event as Events;
use Mautic\StageBundle\StageEvents;

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
    #[AsEventListener(event: StageEvents::STAGE_POST_SAVE)]
    public function onStagePostSave(Events\StageEvent $event): void
    {
        $stage = $event->getStage();
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
    #[AsEventListener(event: StageEvents::STAGE_POST_DELETE)]
    public function onStageDelete(Events\StageEvent $event): void
    {
        $stage = $event->getStage();
        $log   = [
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
