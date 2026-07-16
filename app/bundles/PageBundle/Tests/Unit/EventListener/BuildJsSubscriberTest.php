<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Event\BuildJsScope;
use Mautic\PageBundle\EventListener\BuildJsSubscriber;
use Mautic\PageBundle\Helper\TrackingHelper;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;

final class BuildJsSubscriberTest extends TestCase
{
    public function testEssentialBuildSkipsAllPageTrackingContributions(): void
    {
        $trackingHelper = $this->createMock(TrackingHelper::class);
        $trackingHelper->expects(self::never())->method('getLead');
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::never())->method('generate');
        $subscriber = new BuildJsSubscriber($trackingHelper, $router);
        $event      = new BuildJsEvent('', true, [BuildJsScope::ESSENTIAL]);

        $subscriber->onBuildJsForTrackingEvent($event);
        $subscriber->onBuildJs($event);

        Assert::assertSame('', $event->getJs());
    }

    public function testTrackingBuildContainsGuardedPageAndPixelContributions(): void
    {
        $trackingHelper = $this->createStub(TrackingHelper::class);
        $trackingHelper->method('getLead')->willReturn(null);
        $trackingHelper->method('displayInitCode')->willReturnCallback(
            static fn (string $service): string => match ($service) {
                'google_analytics' => 'G-TEST',
                'facebook_pixel'   => 'FB-TEST',
                default            => throw new \UnexpectedValueException($service),
            },
        );
        $trackingHelper->method('getAnonymizeIp')->willReturn(false);

        $router = $this->createStub(RouterInterface::class);
        $router->method('generate')->willReturnCallback(
            static fn (string $route): string => match ($route) {
                'mautic_page_tracker'            => 'https://mautic.example/mtracking.gif',
                'mautic_page_tracker_cors'       => 'https://mautic.example/mtc/event',
                'mautic_page_tracker_getcontact' => 'https://mautic.example/mtc/contact',
                default                          => throw new \UnexpectedValueException($route),
            },
        );

        $subscriber = new BuildJsSubscriber($trackingHelper, $router);
        $event      = new BuildJsEvent('', true, [BuildJsScope::TRACKING]);

        $subscriber->onBuildJsForTrackingEvent($event);
        $subscriber->onBuildJs($event);

        $js = $event->getJs();
        Assert::assertStringContainsString('window.MauticJS.runtimeReady === true', $js);
        Assert::assertStringContainsString('m.runtimeReady !== true', $js);
        Assert::assertStringContainsString('https://www.googletagmanager.com/gtag/js?id=G-TEST', $js);
        Assert::assertStringContainsString('window.gtag', $js);
        Assert::assertStringContainsString('dataLayer.push(arguments)', $js);
        Assert::assertStringContainsString('https://connect.facebook.net/en_US/fbevents.js', $js);
        Assert::assertStringContainsString("typeof events.focus_item !== 'undefined'", $js);
        Assert::assertStringContainsString('m.deliverPageEvent = function', $js);
        Assert::assertStringNotContainsString('MauticJS.serialize = function', $js);
    }
}
