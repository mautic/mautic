<?php

namespace Mautic\AssetBundle\EventListener;

use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Event as Events;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AssetSubscriber implements EventSubscriberInterface
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
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            AssetEvents::ASSET_POST_SAVE   => ['onAssetPostSave', 0],
            AssetEvents::ASSET_POST_DELETE => ['onAssetDelete', 0],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onAssetPostSave(Events\AssetEvent $event)
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
    public function onAssetDelete(Events\AssetEvent $event)
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

        //In case of batch delete, this method call remove the uploaded file
        $asset->removeUpload();
    }
}
