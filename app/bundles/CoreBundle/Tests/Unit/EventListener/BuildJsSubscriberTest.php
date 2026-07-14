<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Event\BuildJsScope;
use Mautic\CoreBundle\EventListener\BuildJsSubscriber;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class BuildJsSubscriberTest extends TestCase
{
    public function testRuntimeIsAnonymousAndExposesThePublicRuntimeApi(): void
    {
        $event = new BuildJsEvent('', true, [BuildJsScope::RUNTIME]);

        (new BuildJsSubscriber())->onBuildJs($event);

        $js = $event->getJs();
        Assert::assertStringContainsString('MauticJS.makeCORSRequest = function', $js);
        Assert::assertStringContainsString('MauticJS.appendTrackedContact = function(data)', $js);
        Assert::assertStringContainsString('MauticJS.requestWithCredentials = false', $js);
        Assert::assertStringContainsString('MauticJS.trackingEnabled = false', $js);
        Assert::assertStringContainsString('MauticJS.runtimeReady = true', $js);
        Assert::assertStringContainsString('MauticJS.beforeFirstEventDelivery = function', $js);
        Assert::assertStringNotContainsString("localStorage.getItem('mtc_id')", $js);
        Assert::assertStringNotContainsString('MauticJS.getTrackedContact = function', $js);
        Assert::assertStringNotContainsString('MauticJS.checkForTrackingPixel = function', $js);
    }

    public function testTrackingRestoresIdentityAndCredentialedRequests(): void
    {
        $event = new BuildJsEvent('', true, [BuildJsScope::TRACKING]);

        (new BuildJsSubscriber())->onBuildJs($event);

        $js = $event->getJs();
        Assert::assertStringContainsString('MauticJS.runtimeReady !== true', $js);
        Assert::assertStringContainsString('MauticJS.trackingEnabled = true', $js);
        Assert::assertStringContainsString('MauticJS.requestWithCredentials = true', $js);
        Assert::assertStringContainsString("localStorage.getItem('mtc_id')", $js);
        Assert::assertStringContainsString('MauticJS.setTrackedContact = function', $js);
        Assert::assertStringContainsString('MauticJS.checkForTrackingPixel = function', $js);
        Assert::assertStringNotContainsString('MauticJS.serialize = function', $js);
        Assert::assertStringNotContainsString('MauticJS.setCookie = function', $js);
    }

    public function testLegacyBuildContainsRuntimeBeforeTracking(): void
    {
        $event = new BuildJsEvent('', true);

        (new BuildJsSubscriber())->onBuildJs($event);

        $js = $event->getJs();
        Assert::assertLessThan(
            strpos($js, 'MauticJS.trackingEnabled = true'),
            strpos($js, 'MauticJS.runtimeReady = true'),
        );
    }
}
