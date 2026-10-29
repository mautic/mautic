<?php

namespace MauticPlugin\MauticCloudStorageBundle\EventListener;

use Mautic\AssetBundle\Event as Events;
use MauticPlugin\MauticCloudStorageBundle\Exception\InvalidCredentialConfigurationException;
use MauticPlugin\MauticCloudStorageBundle\Integration\CloudStorageIntegration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RemoteAssetBrowseSubscriber
{
    /**
     * Fetches the connector for an event's integration.
     */
    #[\Symfony\Component\EventDispatcher\Attribute\AsEventListener(event: Events\RemoteAssetBrowseEvent::class)]
    public function onAssetRemoteBrowse(Events\RemoteAssetBrowseEvent $event): void
    {
        /** @var CloudStorageIntegration $integration */
        $integration = $event->getIntegration();

        try {
            $event->setAdapter($integration->getAdapter());
        } catch (InvalidCredentialConfigurationException $e) {
            $event->setFailed($e->getMessage());
        }
    }
}
