<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\EventListener;

use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Event\BuildJsScope;
use Mautic\CoreBundle\EventListener\BuildJsSubscriber;
use PHPUnit\Framework\TestCase;

final class BuildJsSubscriberTest extends TestCase
{
    public function testRuntimeIsAnonymousAndExposesThePublicRuntimeApi(): void
    {
        $event = new BuildJsEvent('', true, [BuildJsScope::RUNTIME]);

        new BuildJsSubscriber()->onBuildJs($event);

        $js = $event->getJs();
        $this->assertStringContainsString('MauticJS.makeCORSRequest = function', $js);
        $this->assertStringContainsString('MauticJS.appendTrackedContact = function(data)', $js);
        $this->assertStringContainsString('MauticJS.requestWithCredentials = false', $js);
        $this->assertStringContainsString('MauticJS.trackingEnabled = false', $js);
        $this->assertStringContainsString('MauticJS.runtimeReady = true', $js);
        $this->assertStringContainsString('MauticJS.beforeFirstEventDelivery = function', $js);
        $this->assertStringNotContainsString("localStorage.getItem('mtc_id')", $js);
        $this->assertStringNotContainsString('MauticJS.getTrackedContact = function', $js);
        $this->assertStringNotContainsString('MauticJS.checkForTrackingPixel = function', $js);
    }

    public function testTrackingRestoresIdentityAndCredentialedRequests(): void
    {
        $event = new BuildJsEvent('', true, [BuildJsScope::TRACKING]);

        new BuildJsSubscriber()->onBuildJs($event);

        $js = $event->getJs();
        $this->assertStringContainsString('MauticJS.runtimeReady !== true', $js);
        $this->assertStringContainsString('MauticJS.trackingEnabled = true', $js);
        $this->assertStringContainsString('MauticJS.requestWithCredentials = true', $js);
        $this->assertStringContainsString("localStorage.getItem('mtc_id')", $js);
        $this->assertStringContainsString('MauticJS.setTrackedContact = function', $js);
        $this->assertStringContainsString('MauticJS.checkForTrackingPixel = function', $js);
        $this->assertStringNotContainsString('MauticJS.serialize = function', $js);
        $this->assertStringNotContainsString('MauticJS.setCookie = function', $js);
    }

    public function testLegacyBuildContainsRuntimeBeforeTracking(): void
    {
        $event = new BuildJsEvent('', true);

        new BuildJsSubscriber()->onBuildJs($event);

        $js = $event->getJs();
        $this->assertLessThan(strpos($js, 'MauticJS.trackingEnabled = true'), strpos($js, 'MauticJS.runtimeReady = true'));
    }
}
