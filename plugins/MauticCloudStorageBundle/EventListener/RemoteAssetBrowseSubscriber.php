<?php

namespace MauticPlugin\MauticCloudStorageBundle\EventListener;

use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Event as Events;
use MauticPlugin\MauticCloudStorageBundle\Integration\CloudStorageIntegration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RemoteAssetBrowseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AssetEvents::ASSET_ON_REMOTE_BROWSE => ['onAssetRemoteBrowse', 0],
        ];
    }

    /**
     * Fetches the connector for an event's integration.
     */
    public function onAssetRemoteBrowse(Events\RemoteAssetBrowseEvent $event): void
    {
        /** @var CloudStorageIntegration $integration */
        $integration = $event->getIntegration();

        $event->setAdapter($integration->getAdapter());
    }
}
