<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Event\BuildJsScope;
use Mautic\NotificationBundle\EventListener\BuildJsSubscriber;
use Mautic\NotificationBundle\Helper\NotificationHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

final class BuildJsSubscriberTest extends TestCase
{
    public function testEssentialBuildSkipsOneSignalLookup(): void
    {
        $integrationHelper = $this->createMock(IntegrationHelper::class);
        $integrationHelper->expects(self::never())->method('getIntegrationObject');
        $subscriber = new BuildJsSubscriber(
            $this->createStub(NotificationHelper::class),
            $integrationHelper,
            $this->createStub(RouterInterface::class),
        );
        $event = new BuildJsEvent('', true, [BuildJsScope::ESSENTIAL]);

        $subscriber->onBuildJs($event);

        Assert::assertSame('', $event->getJs());
    }

    public function testTrackingBuildContainsGuardedOneSignalContribution(): void
    {
        $settings = $this->createStub(Integration::class);
        $settings->method('getIsPublished')->willReturn(true);
        $integration = $this->createStub(AbstractIntegration::class);
        $integration->method('getIntegrationSettings')->willReturn($settings);

        $integrationHelper = $this->createStub(IntegrationHelper::class);
        $integrationHelper->method('getIntegrationObject')->willReturn($integration);

        $notificationHelper = $this->createStub(NotificationHelper::class);
        $notificationHelper->method('getHeaderScript')->willReturn("MauticJS.insertScript('https://cdn.onesignal.com/OneSignalSDK.js');");
        $notificationHelper->method('getScript')->willReturn("MauticJS.makeCORSRequest('GET', '/notification/subscribe', []);");

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('https://mautic.example/notification');

        $subscriber = new BuildJsSubscriber($notificationHelper, $integrationHelper, $router);
        $event      = new BuildJsEvent('', true, [BuildJsScope::TRACKING]);

        $subscriber->onBuildJs($event);

        $js = $event->getJs();
        Assert::assertStringContainsString('window.MauticJS.runtimeReady === true', $js);
        Assert::assertStringContainsString('https://cdn.onesignal.com/OneSignalSDK.js', $js);
        Assert::assertStringContainsString("MauticJS.makeCORSRequest('GET', '/notification/subscribe', []);", $js);
        Assert::assertStringContainsString('MauticJS.notification = {', $js);
    }
}
