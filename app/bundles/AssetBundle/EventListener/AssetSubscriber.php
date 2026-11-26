<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\Entity\Asset;
use Mautic\CoreBundle\Event\EntityPostDelete;
use Mautic\CoreBundle\Event\EntityPostSave;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

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
    #[AsEventListener]
    public function onAssetPostSave(EntityPostSave $event): void
    {
        $asset = $event->getEntity();
        if (!$asset instanceof Asset) {
            return;
        }

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
    #[AsEventListener]
    public function onAssetDelete(EntityPostDelete $event): void
    {
        $asset = $event->getEntity();
        if (!$asset instanceof Asset) {
            return;
        }

        $log = [
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
