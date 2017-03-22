<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCloudStorageBundle\EventListener;

use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Event as Events;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class RemoteAssetBrowseSubscriber.
 */
class RemoteAssetBrowseSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            AssetEvents::ASSET_ON_REMOTE_BROWSE => ['onAssetRemoteBrowse', 0],
        ];
    }

    /**
     * Fetches the connector for an event's integration.
     *
     * @param Events\RemoteAssetBrowseEvent $event
     */
    public function onAssetRemoteBrowse(Events\RemoteAssetBrowseEvent $event)
    {
        /** @var \MauticPlugin\MauticCloudStorageBundle\Integration\CloudStorageIntegration $integration */
        $integration = $event->getIntegration();

        $event->setAdapter($integration->getAdapter());
    }
}
