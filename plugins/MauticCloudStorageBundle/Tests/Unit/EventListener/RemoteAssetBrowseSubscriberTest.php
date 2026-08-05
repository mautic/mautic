<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCloudStorageBundle\Tests\Unit\EventListener;

use Gaufrette\Adapter;
use Mautic\AssetBundle\AssetEvents;
use Mautic\AssetBundle\Event\RemoteAssetBrowseEvent;
use MauticPlugin\MauticCloudStorageBundle\EventListener\RemoteAssetBrowseSubscriber;
use MauticPlugin\MauticCloudStorageBundle\Exception\InvalidCredentialConfigurationException;
use MauticPlugin\MauticCloudStorageBundle\Integration\CloudStorageIntegration;
use PHPUnit\Framework\TestCase;

final class RemoteAssetBrowseSubscriberTest extends TestCase
{
    private RemoteAssetBrowseSubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subscriber = new RemoteAssetBrowseSubscriber();
    }

    public function testGetSubscribedEventsReturnsAssetOnRemoteBrowse(): void
    {
        $this->assertSame(
            [AssetEvents::ASSET_ON_REMOTE_BROWSE => ['onAssetRemoteBrowse', 0]],
            RemoteAssetBrowseSubscriber::getSubscribedEvents()
        );
    }

    public function testOnAssetRemoteBrowseSetsAdapterWhenIntegrationSucceeds(): void
    {
        $adapter     = $this->createStub(Adapter::class);
        $integration = $this->createMock(CloudStorageIntegration::class);
        $integration->method('getAdapter')->willReturn($adapter);

        $event = new RemoteAssetBrowseEvent($integration);

        $this->subscriber->onAssetRemoteBrowse($event);

        $this->assertSame($adapter, $event->getAdapter());
        $this->assertNull($event->getFailureMessage());
    }

    public function testOnAssetRemoteBrowseSetsFailureMessageWhenCredentialsAreInvalid(): void
    {
        $integration = $this->createMock(CloudStorageIntegration::class);
        $integration->method('getAdapter')->willThrowException(
            new InvalidCredentialConfigurationException('client_id or client_secret missing.')
        );

        $event = new RemoteAssetBrowseEvent($integration);

        $this->subscriber->onAssetRemoteBrowse($event);

        $this->assertNotInstanceOf(Adapter::class, $event->getAdapter());
        $this->assertSame('client_id or client_secret missing.', $event->getFailureMessage());
    }
}
