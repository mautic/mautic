<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCloudStorageBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\AssetBundle\Event as Events;
use Mautic\AssetBundle\AssetEvents;

/**
 * Class RemoteAssetBrowseSubscriber
 */
class RemoteAssetBrowseSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            AssetEvents::ASSET_ON_REMOTE_BROWSE => array('onAssetRemoteBrowse', 0)
        );
    }

    /**
     * Fetches the connector for an event's integration
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
