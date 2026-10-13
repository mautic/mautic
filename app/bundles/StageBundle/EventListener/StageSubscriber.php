<?php

declare(strict_types=1);

namespace Mautic\StageBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\StageBundle\Event as Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class StageSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events\StagePostSaveEvent::class   => ['onStagePostSave', 0],
            Events\StagePostDeleteEvent::class => ['onStageDelete', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onStagePostSave(Events\StagePostSaveEvent $event): void
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
    public function onStageDelete(Events\StagePostDeleteEvent $event): void
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
