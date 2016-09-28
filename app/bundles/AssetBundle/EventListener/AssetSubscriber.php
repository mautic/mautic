<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\AssetBundle\Event as Events;
use Mautic\AssetBundle\AssetEvents;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;

/**
 * Class AssetSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class AssetSubscriber extends CommonSubscriber
{
    /**
     * @var IpLookupHelper
     */
    protected $ipLookupHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * AssetSubscriber constructor.
     *
     * @param IpLookupHelper $ipLookupHelper
     * @param AuditLogModel  $auditLogModel
     */
    public function __construct(IpLookupHelper $ipLookupHelper, AuditLogModel $auditLogModel)
    {
        $this->ipLookupHelper = $ipLookupHelper;
        $this->auditLogModel = $auditLogModel;
    }

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            AssetEvents::ASSET_POST_SAVE   => array('onAssetPostSave', 0),
            AssetEvents::ASSET_POST_DELETE => array('onAssetDelete', 0)
        );
    }

    /**
     * Add an entry to the audit log
     *
     * @param Events\AssetEvent $event
     */
    public function onAssetPostSave(Events\AssetEvent $event)
    {
        $asset = $event->getAsset();
        if ($details = $event->getChanges()) {
            $log = array(
                "bundle"    => "asset",
                "object"    => "asset",
                "objectId"  => $asset->getId(),
                "action"    => ($event->isNew()) ? "create" : "update",
                "details"   => $details,
                "ipAddress" => $this->ipLookupHelper->getIpAddressFromRequest()
            );
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log
     *
     * @param Events\AssetEvent $event
     */
    public function onAssetDelete(Events\AssetEvent $event)
    {
        $asset = $event->getAsset();
        $log = array(
            "bundle"     => "asset",
            "object"     => "asset",
            "objectId"   => $asset->deletedId,
            "action"     => "delete",
            "details"    => array('name' => $asset->getTitle()),
            "ipAddress"  => $this->ipLookupHelper->getIpAddressFromRequest()
        );
        $this->auditLogModel->writeToLog($log);

        //In case of batch delete, this method call remove the uploaded file
        $asset->removeUpload();
    }
}
