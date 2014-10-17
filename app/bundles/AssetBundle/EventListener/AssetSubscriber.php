<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\AssetBundle\Event as Events;
use Mautic\AssetBundle\AssetEvents;
use Mautic\LeadBundle\Event\LeadTimelineEvent;
use Mautic\LeadBundle\LeadEvents;

/**
 * Class AssetSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class AssetSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            AssetEvents::ASSET_POST_SAVE   => array('onAssetPostSave', 0),
            AssetEvents::ASSET_POST_DELETE => array('onAssetDelete', 0),
            LeadEvents::TIMELINE_ON_GENERATE => array('onTimelineGenerate', 0)
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
                "ipAddress" => $this->request->server->get('REMOTE_ADDR')
            );
            $this->factory->getModel('core.auditLog')->writeToLog($log);
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
        $lead    = $event->getLead();
        $leadIps = array();

        /** @var \Mautic\CoreBundle\Entity\IpAddress $ip */
        foreach ($lead->getIpAddresses() as $ip) {
            $leadIps[] = $ip->getId();
        }

        /** @var \Mautic\AssetBundle\Entity\DownloadRepository $downloadRepository */
        $downloadRepository = $this->factory->getEntityManager()->getRepository('MauticAssetBundle:Download');

        $downloads = $downloadRepository->getLeadDownloads($lead->getId(), $leadIps);

        $model = $this->factory->getModel('asset.asset');

        // Add the downloads to the event array
        foreach ($downloads as $download) {
            $event->addEvent(array(
                'event'     => 'asset.download',
                'timestamp' => $download['dateDownload'],
                'extra'     => array(
                    'asset' => $model->getEntity($download['asset_id'])
                ),
                'contentTemplate' => 'MauticAssetBundle:Timeline:index.html.php'
            ));
        }
    }
}
