<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Mautic\AssetBundle\Event\AssetPostDeleteEvent;
use Mautic\AssetBundle\Event\AssetPostSaveEvent;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;

final readonly class AssetSubscriber
{
    public function __construct(
        private IpLookupHelper $ipLookupHelper,
        private AuditLogModel $auditLogModel,
    ) {
    }

    /**
     * Add an entry to the audit log.
     */
    #[AsEventListener(event: AssetPostSaveEvent::class)]
    public function onAssetPostSave(AssetPostSaveEvent $event): void
    {
        $asset = $event->getAsset();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'    => 'asset',
                'object'    => 'asset',
                'objectId'  => $asset->getId(),
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
    #[AsEventListener(event: AssetPostDeleteEvent::class)]
    public function onAssetDelete(AssetPostDeleteEvent $event): void
    {
        $asset = $event->getAsset();
        $log   = [
            'bundle'    => 'asset',
            'object'    => 'asset',
            'objectId'  => $asset->deletedId,
            'action'    => 'delete',
            'details'   => ['name' => $asset->getTitle()],
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ];
        $this->auditLogModel->writeToLog($log);

        // In case of batch delete, this method call remove the uploaded file
        $asset->removeUpload();
    }
}
