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

        (new BuildJsSubscriber())->onBuildJs($event);

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

    public function testPageTrackingPostsUseKeepaliveFetchWithoutPreflightHeader(): void
    {
        $event = new BuildJsEvent('', true, [BuildJsScope::RUNTIME]);

        (new BuildJsSubscriber())->onBuildJs($event);

        $js         = $event->getJs();
        $fetchStart = strpos($js, 'window.fetch(url');
        $xhrStart   = strpos($js, 'var xhr = MauticJS.createCORSRequest', $fetchStart);

        $this->assertIsInt($fetchStart);
        $this->assertIsInt($xhrStart);

        $fetchBlock = substr($js, $fetchStart, $xhrStart - $fetchStart);
        $xhrBlock   = substr($js, $xhrStart);

        $this->assertStringContainsString("method === 'POST'", $js);
        $this->assertStringContainsString('url === MauticJS.pageTrackingCORSUrl', $js);
        $this->assertStringContainsString("typeof window.fetch === 'function'", $js);
        $this->assertStringContainsString("typeof window.Blob === 'function'", $js);
        $this->assertStringContainsString('new window.Blob([query]).size <= 60 * 1024', $js);
        $this->assertStringContainsString('keepalive: true', $fetchBlock);
        $this->assertStringContainsString("credentials: MauticJS.requestWithCredentials ? 'include' : 'same-origin'", $fetchBlock);
        $this->assertStringContainsString("headers: { 'Content-Type': 'application/x-www-form-urlencoded' }", $fetchBlock);
        $this->assertStringContainsString('MauticJS.parseTextToJSON(responseText)', $fetchBlock);
        $this->assertStringContainsString('callbackSuccess(response, responseDetails)', $fetchBlock);
        $this->assertStringContainsString('callbackError(response, responseDetails)', $fetchBlock);
        $this->assertStringContainsString('callbackError({}, { status: 0 })', $fetchBlock);
        $this->assertStringNotContainsString('X-Requested-With', $fetchBlock);
        $this->assertStringContainsString("xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest')", $xhrBlock);
    }

    public function testTrackingRestoresIdentityAndCredentialedRequests(): void
    {
        $event = new BuildJsEvent('', true, [BuildJsScope::TRACKING]);

        (new BuildJsSubscriber())->onBuildJs($event);

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

        (new BuildJsSubscriber())->onBuildJs($event);

        $js = $event->getJs();
        $this->assertLessThan(strpos($js, 'MauticJS.trackingEnabled = true'), strpos($js, 'MauticJS.runtimeReady = true'));
    }
}
