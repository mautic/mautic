<?php

declare(strict_types=1);

namespace Acceptance;

final class MauticScriptSplitCest
{
    public function _before(\AcceptanceTester $I): void
    {
        $I->amOnPage('/tests/_data/mautic-script-split.html');
        $I->executeJS(<<<'JS'
localStorage.clear();
sessionStorage.clear();
document.cookie.split(';').forEach(function (cookie) {
    document.cookie = cookie.split('=')[0].trim()+'=; Max-Age=0; path=/; Secure';
});
JS);
    }

    public function loadsEssentialScriptFromDistinctOrigin(\AcceptanceTester $I): void
    {
        $scriptUrl = $this->loadScript($I, '/mautic-essential.js');
        $I->waitForJS('return window.MauticJS && window.MauticJS.runtimeReady === true', 10);

        $state = $this->grabBrowserState($I);

        $I->assertNotSame($state['pageOrigin'], $state['scriptOrigin']);
        $I->assertContains($scriptUrl, $state['resourceUrls']);
        $I->assertTrue($state['runtimeReady']);
        $I->assertFalse($state['trackingEnabled']);
        $I->assertSame([], $state['errors']);
        $this->assertOnlyScriptRequested($I, $state['networkRequests'], $scriptUrl);
        $I->assertSame([], $state['trackingRequests']);
        $I->assertSame([], $this->filterTrackingIdentifierAccess($state['storageAccess']));
        $I->assertTrue($state['cookieInstrumentationSupported']);
        $I->assertTrue($state['imageInstrumentationSupported']);
        $I->assertSame([], $state['cookieAccess']);
        $I->assertNull($state['mtcId']);
        $I->assertNull($state['deviceId']);
        $I->assertStringNotContainsString('mtc_id=', $state['cookies']);
        $I->assertStringNotContainsString('mautic_device_id=', $state['cookies']);
    }

    public function essentialScriptDoesNotUseExistingTrackingIdentifiers(\AcceptanceTester $I): void
    {
        $I->executeJS(<<<'JS'
localStorage.setItem('mtc_id', 'existing-contact-id');
localStorage.setItem('mautic_device_id', 'existing-device-id');
document.cookie = 'mtc_id=existing-contact-cookie; path=/; Secure; SameSite=Lax';
document.cookie = 'mautic_device_id=existing-device-cookie; path=/; Secure; SameSite=Lax';
JS);
        $seededState = $I->executeJS(<<<'JS'
return {
    mtcId: localStorage.getItem('mtc_id'),
    deviceId: localStorage.getItem('mautic_device_id'),
    cookies: document.cookie
};
JS);
        $I->assertSame('existing-contact-id', $seededState['mtcId']);
        $I->assertSame('existing-device-id', $seededState['deviceId']);
        $I->assertStringContainsString('mtc_id=existing-contact-cookie', $seededState['cookies']);
        $I->assertStringContainsString('mautic_device_id=existing-device-cookie', $seededState['cookies']);

        $scriptUrl = $this->loadScript($I, '/mautic-essential.js');
        $I->waitForJS('return window.MauticJS && window.MauticJS.runtimeReady === true', 10);

        $state = $this->grabBrowserState($I);

        $this->assertOnlyScriptRequested($I, $state['networkRequests'], $scriptUrl);
        $I->assertSame([], $this->filterTrackingIdentifierAccess($state['storageAccess']));
        $I->assertTrue($state['cookieInstrumentationSupported']);
        $I->assertTrue($state['imageInstrumentationSupported']);
        $I->assertSame([], $state['cookieAccess']);
        $I->assertSame([], $state['trackingRequests']);
        $I->assertSame('existing-contact-id', $state['mtcId']);
        $I->assertSame('existing-device-id', $state['deviceId']);
        $I->assertStringContainsString('mtc_id=existing-contact-cookie', $state['cookies']);
        $I->assertStringContainsString('mautic_device_id=existing-device-cookie', $state['cookies']);
        $I->assertStringNotContainsString('existing-contact-id', implode(' ', $state['resourceUrls']));
        $I->assertStringNotContainsString('existing-device-id', implode(' ', $state['resourceUrls']));
    }

    public function trackingScriptWithoutEssentialStopsSafely(\AcceptanceTester $I): void
    {
        $scriptUrl = $this->loadScript($I, '/mautic-tracking.js');
        $state = $this->grabBrowserState($I);

        $I->assertNotSame($state['pageOrigin'], $state['scriptOrigin']);
        $I->assertContains($scriptUrl, $state['resourceUrls']);
        $I->assertNotContains($this->getMauticUrl($I).'/mautic-essential.js', $state['resourceUrls']);
        $I->assertFalse($state['runtimeReady']);
        $I->assertFalse($state['trackingEnabled']);
        $I->assertSame([], $state['errors']);
        $this->assertOnlyScriptRequested($I, $state['networkRequests'], $scriptUrl);
        $I->assertSame([], $state['trackingRequests']);
        $I->assertSame([], $this->filterTrackingIdentifierAccess($state['storageAccess']));
        $I->assertTrue($state['cookieInstrumentationSupported']);
        $I->assertTrue($state['imageInstrumentationSupported']);
        $I->assertSame([], $state['cookieAccess']);
        $I->assertNull($state['mtcId']);
        $I->assertNull($state['deviceId']);
        $I->assertStringNotContainsString('mtc_id=', $state['cookies']);
        $I->assertStringNotContainsString('mautic_device_id=', $state['cookies']);
    }

    private function loadScript(\AcceptanceTester $I, string $endpoint): string
    {
        $scriptUrl = $this->getMauticUrl($I).$endpoint;
        $fixtureUrl = '/tests/_data/mautic-script-split.html?script='.rawurlencode($scriptUrl);

        $I->amOnPage($fixtureUrl);
        $I->waitForJS('return window.mauticScriptFinished === true', 10);
        $I->assertSame([], $I->executeJS('return window.mauticScriptErrors;'));
        $I->waitForJS('return Date.now() - window.mauticLastNetworkActivity >= window.mauticNetworkQuietPeriod', 10);

        return $scriptUrl;
    }

    /**
     * @return array<string, mixed>
     */
    private function grabBrowserState(\AcceptanceTester $I): array
    {
        return $I->executeJS(<<<'JS'
var storageAccess = window.mauticStorageAccess.slice();
var cookieAccess = window.mauticCookieAccess.slice();
var resourceUrls = performance.getEntriesByType('resource').map(function (entry) {
    return entry.name;
});
var trackingRequests = resourceUrls.filter(function (url) {
    var path = new URL(url).pathname;

    return path === '/mtc/event'
        || path === '/mtracking.gif'
        || path === '/mtc'
        || path === '/dwc'
        || path.indexOf('/dwc/') === 0;
});

return {
    pageOrigin: window.location.origin,
    scriptOrigin: new URL(document.querySelector('script[src]').src).origin,
    runtimeReady: Boolean(window.MauticJS && window.MauticJS.runtimeReady),
    trackingEnabled: Boolean(window.MauticJS && window.MauticJS.trackingEnabled),
    errors: window.mauticScriptErrors,
    storageAccess: storageAccess,
    cookieAccess: cookieAccess,
    cookieInstrumentationSupported: window.mauticCookieInstrumentationSupported,
    imageInstrumentationSupported: window.mauticImageInstrumentationSupported,
    networkRequests: window.mauticNetworkRequests,
    resourceUrls: resourceUrls,
    trackingRequests: trackingRequests,
    mtcId: localStorage.getItem('mtc_id'),
    deviceId: localStorage.getItem('mautic_device_id'),
    cookies: document.cookie
};
JS);
    }

    /**
     * @param array<int, array{type: string, url: string}> $networkRequests
     */
    private function assertOnlyScriptRequested(\AcceptanceTester $I, array $networkRequests, string $scriptUrl): void
    {
        $I->assertSame([$scriptUrl], array_column($networkRequests, 'url'));
    }

    /**
     * @param array<int, array{key: string, method: string}> $storageAccess
     *
     * @return array<int, array{key: string, method: string}>
     */
    private function filterTrackingIdentifierAccess(array $storageAccess): array
    {
        return array_values(array_filter(
            $storageAccess,
            fn (array $access): bool => in_array($access['key'], ['mtc_id', 'mautic_device_id'], true),
        ));
    }

    private function getMauticUrl(\AcceptanceTester $I): string
    {
        $mauticUrl = rtrim((string) getenv('BROWSERTEST_OUTPUT_BASE_URL'), '/');
        $I->assertNotSame('', $mauticUrl, 'BROWSERTEST_OUTPUT_BASE_URL must contain the canonical DDEV URL.');

        return $mauticUrl;
    }
}
