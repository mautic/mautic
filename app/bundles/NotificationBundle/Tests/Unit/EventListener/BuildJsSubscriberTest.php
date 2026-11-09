<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Event\BuildJsScope;
use Mautic\NotificationBundle\EventListener\BuildJsSubscriber;
use Mautic\NotificationBundle\Helper\NotificationHelper;
use Mautic\NotificationBundle\Helper\OneSignalIntegrationHelper;
use Mautic\NotificationBundle\Integration\OneSignalIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

final class BuildJsSubscriberTest extends TestCase
{
    public function testEssentialBuildSkipsOneSignalLookup(): void
    {
        $integrationHelper = $this->createMock(OneSignalIntegrationHelper::class);
        $integrationHelper->expects($this->never())->method('getPublishedIntegration');
        $subscriber = new BuildJsSubscriber(
            $this->createStub(NotificationHelper::class),
            $integrationHelper,
            $this->createStub(RouterInterface::class),
        );
        $event = new BuildJsEvent('', true, [BuildJsScope::ESSENTIAL]);

        $subscriber->onBuildJs($event);

        $this->assertSame('', $event->getJs());
    }

    public function testTrackingBuildContainsGuardedOneSignalContribution(): void
    {
        $integrationHelper = $this->createStub(OneSignalIntegrationHelper::class);
        $integrationHelper->method('getPublishedIntegration')->willReturn(new OneSignalIntegration());

        $notificationHelper = $this->createStub(NotificationHelper::class);
        $notificationHelper->method('getHeaderScript')->willReturn("MauticJS.insertScript('https://cdn.onesignal.com/OneSignalSDK.js');");
        $notificationHelper->method('getScript')->willReturn("MauticJS.makeCORSRequest('GET', '/notification/subscribe', []);");

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturn('https://mautic.example/notification');

        $subscriber = new BuildJsSubscriber($notificationHelper, $integrationHelper, $router);
        $event      = new BuildJsEvent('', true, [BuildJsScope::TRACKING]);

        $subscriber->onBuildJs($event);

        $js = $event->getJs();
        $this->assertStringContainsString('window.MauticJS.runtimeReady === true', $js);
        $this->assertStringContainsString('https://cdn.onesignal.com/OneSignalSDK.js', $js);
        $this->assertStringContainsString("MauticJS.makeCORSRequest('GET', '/notification/subscribe', []);", $js);
        $this->assertStringContainsString('MauticJS.notification = {', $js);
    }
}
