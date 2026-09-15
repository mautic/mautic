<?php

declare(strict_types=1);

namespace Acceptance;

final class MauticScriptSplitCest
{
    private ?string $corsConfigBackup = null;

    private bool $corsConfigExisted = false;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $corsConfigParameters = null;

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

    public function _after(\AcceptanceTester $I): void
    {
        if (null === $this->corsConfigBackup) {
            return;
        }

        $configPath = $this->getCorsConfigPath();
        if ($this->corsConfigExisted) {
            file_put_contents($configPath, $this->corsConfigBackup);
        } else {
            unlink($configPath);
        }

        $this->corsConfigBackup = null;
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

    public function essentialThenTrackingInitializesOnce(\AcceptanceTester $I): void
    {
        $this->allowCorsFromFixtureOrigin();

        $essentialUrl = $this->getMauticUrl($I).'/mautic-essential.js';
        $trackingUrl  = $this->getMauticUrl($I).'/mautic-tracking.js';
        $this->loadScripts($I, [$essentialUrl, $trackingUrl]);
        $I->waitForJS('return window.MauticJS && window.MauticJS.firstDeliveryMade === true', 10);
        $I->waitForJS('return Date.now() - window.mauticLastNetworkActivity >= window.mauticNetworkQuietPeriod', 10);

        $state = $this->grabBrowserState($I);
        $eventRequests = array_values(array_filter(
            $state['networkRequests'],
            fn (array $request): bool => '/mtc/event' === parse_url($request['url'], PHP_URL_PATH),
        ));
        $trackingRequestPaths = array_map(
            fn (string $url): string => (string) parse_url($url, PHP_URL_PATH),
            $state['trackingRequests'],
        );

        $I->assertTrue($state['runtimeReady']);
        $I->assertTrue($state['trackingEnabled']);
        $I->assertSame([], $state['errors']);
        $I->assertSame([$essentialUrl, $trackingUrl], array_slice(array_column($state['networkRequests'], 'url'), 0, 2));
        $I->assertSame(1, count(array_filter($state['resourceUrls'], fn (string $url): bool => $essentialUrl === $url)));
        $I->assertSame(1, count(array_filter($state['resourceUrls'], fn (string $url): bool => $trackingUrl === $url)));
        $I->assertCount(1, $eventRequests);
        $I->assertSame(['/mtc/event'], $trackingRequestPaths);
        $I->assertSame(1, $state['pageViewCounter']);
        $I->assertSame(1, $state['pageEventDeliveryCount']);
    }

    public function legacyMtcScriptInitializesAndTracksOnce(\AcceptanceTester $I): void
    {
        $this->allowCorsFromFixtureOrigin();

        $scriptUrl = $this->loadScript($I, '/mtc.js');
        $I->waitForJS('return window.MauticJS && window.MauticJS.firstDeliveryMade === true', 10);
        $I->waitForJS('return Date.now() - window.mauticLastNetworkActivity >= window.mauticNetworkQuietPeriod', 10);

        $state = $this->grabBrowserState($I);
        $eventRequests = array_values(array_filter(
            $state['networkRequests'],
            fn (array $request): bool => '/mtc/event' === parse_url($request['url'], PHP_URL_PATH),
        ));
        $trackingRequestPaths = array_map(
            fn (string $url): string => (string) parse_url($url, PHP_URL_PATH),
            $state['trackingRequests'],
        );

        $I->assertTrue($state['runtimeReady']);
        $I->assertTrue($state['trackingEnabled']);
        $I->assertSame([], $state['errors']);
        $I->assertSame(1, count(array_filter($state['resourceUrls'], fn (string $url): bool => $scriptUrl === $url)));
        $I->assertCount(1, $eventRequests);
        $I->assertSame(['/mtc/event'], $trackingRequestPaths);
        $I->assertSame(1, $state['pageViewCounter']);
        $I->assertSame(1, $state['pageEventDeliveryCount']);
    }

    public function essentialScriptKeepsDwcFallbackVisible(\AcceptanceTester $I): void
    {
        // DWC slots wait for first event delivery to be replaced. Before consent
        // (tracking disabled), fallback content must stay visible and no DWC/tracking
        // requests should fire. The fixture provides a synthetic .mautic-slot div to
        // test the JS behavior independently of a published DWC item in Mautic.
        $scriptUrl = $this->getMauticUrl($I).'/mautic-essential.js';
        $this->loadScripts($I, [$scriptUrl], ['dwc' => '1']);
        $I->waitForJS('return window.MauticJS && window.MauticJS.runtimeReady === true', 10);

        $state = $this->grabBrowserState($I);
        $fallback = $I->executeJS(<<<'JS'
var element = document.getElementById('dwc-fallback');

return {
    visible: Boolean(element && !element.hidden),
    text: element ? element.textContent : null
};
JS);
        $dwcRequests = array_values(array_filter(
            $state['networkRequests'],
            fn (array $request): bool => str_starts_with((string) parse_url($request['url'], PHP_URL_PATH), '/dwc/'),
        ));

        $I->assertTrue($state['runtimeReady']);
        $I->assertFalse($state['trackingEnabled']);
        $I->assertSame([], $state['errors']);
        $I->assertSame('function', $I->executeJS('return typeof MauticJS.replaceDynamicContent;'));
        $I->assertTrue($fallback['visible']);
        $I->assertSame('DWC fallback content', $fallback['text']);
        $I->assertSame([], $dwcRequests);
        $I->assertSame([], $state['trackingRequests']);
    }

    public function essentialScriptInitializesDwcFallbackFormWithoutTrackingIdentifiers(\AcceptanceTester $I): void
    {
        // Mautic supports forms inside DWC slots. Before consent, the fallback form
        // (inside a .mautic-slot) must be initialized with submit handler, iframe
        // target, and messenger field -- without reading/writing tracking IDs or
        // cookies. The synthetic fixture markup tests this JS behavior in isolation.
        $scriptUrl = $this->getMauticUrl($I).'/mautic-essential.js';
        $this->loadScripts($I, [$scriptUrl], ['dwc' => '1', 'form' => '1']);
        $I->waitForJS("return typeof MauticSDK !== 'undefined' && document.getElementById('mauticiframe_acceptance') !== null", 10);

        $formState = $I->executeJS(<<<'JS'
var form = document.getElementById('mauticform_acceptance');
form.querySelector('[name="mauticform[email]"]').value = 'visitor@example.test';

return {
    fields: Array.from(new FormData(form).entries()),
    hasSubmitHandler: typeof form.onsubmit === 'function',
    messengerCount: form.querySelectorAll('[name="mauticform[messenger]"]').length,
    target: form.target
};
JS);
        $state = $this->grabBrowserState($I);

        $I->assertTrue($state['runtimeReady']);
        $I->assertFalse($state['trackingEnabled']);
        $I->assertSame([], $state['errors']);
        $I->assertTrue($formState['hasSubmitHandler']);
        $I->assertSame(1, $formState['messengerCount']);
        $I->assertSame('mauticiframe_acceptance', $formState['target']);
        $I->assertSame([
            ['mauticform[email]', 'visitor@example.test'],
            ['mauticform[formId]', 'acceptance'],
            ['mauticform[messenger]', '1'],
        ], $formState['fields']);
        $I->assertSame([], $state['trackingRequests']);
        $I->assertSame([], $this->filterTrackingIdentifierAccess($state['storageAccess']));
        $I->assertSame([], $state['cookieAccess']);
    }

    public function essentialScriptLoadsFormSdkOnceForRepeatedInitialization(\AcceptanceTester $I): void
    {
        $scriptUrl = $this->getMauticUrl($I).'/mautic-essential.js';
        $this->loadScripts($I, [$scriptUrl]);

        $result = $I->executeJS(<<<'JS'
var sdkWasUndefined = typeof MauticSDK === 'undefined';
var markerWasUndefined = typeof MauticSDKLoaded === 'undefined';
MauticJS.initializeForms('mauticform_wrapper');
MauticJS.initializeForms('mauticform_wrapper');

return {
    sdkWasUndefined: sdkWasUndefined,
    markerWasUndefined: markerWasUndefined,
    loadingMarker: window.MauticSDKLoaded === true,
    scriptCount: Array.from(document.scripts).filter(function (script) {
        return script.src && new URL(script.src).pathname.endsWith('/media/js/mautic-form.js');
    }).length
};
JS);

        $I->assertTrue($result['sdkWasUndefined']);
        $I->assertTrue($result['markerWasUndefined']);
        $I->assertTrue($result['loadingMarker']);
        $I->assertSame(1, $result['scriptCount']);
        $I->waitForJS("return typeof MauticSDK !== 'undefined'", 10);
        $I->assertSame([], $I->executeJS('return window.mauticScriptErrors;'));
    }

    public function dwcEnhancementLoadsOnlyAllowedFocusEndpoints(\AcceptanceTester $I): void
    {
        $scriptUrl = $this->getMauticUrl($I).'/mautic-essential.js';
        $this->loadScripts($I, [$scriptUrl]);

        $loadedScripts = $I->executeJS(<<<'JS'
var loadedScripts = [];
var essentialScript = Array.from(document.scripts).filter(function (script) {
    return script.src && new URL(script.src).pathname.endsWith('/mautic-essential.js');
})[0];
var essentialUrl = new URL(essentialScript.src);
var mauticBaseUrl = essentialUrl.origin + essentialUrl.pathname.slice(0, -'/mautic-essential.js'.length);
var content = [
    '<script src="' + mauticBaseUrl + '/focus/1/display.js"></script>',
    "<script src='" + mauticBaseUrl + "/focus/2.js'></script>",
    '<script src="focus/4/display.js"></script>',
    '<script src="' + mauticBaseUrl + '/focus/3/tracking.js"></script>',
    '<script src="' + mauticBaseUrl + '/focus/0/display.js"></script>',
    '<script src="' + mauticBaseUrl + '/load?next=/focus/5/display.js"></script>',
    '<script src="https://tracker.example/focus/6/display.js"></script>',
    '<script src="https://tracker.example/load?next=/focus/7/display.js"></script>',
    '<script data-src="' + mauticBaseUrl + '/focus/8/display.js"></script>',
    '<script src="http://["></script>',
    '<script src="' + mauticBaseUrl + '/other.js"></script>'
].join('');
MauticJS.initializeForms = function () {};
MauticJS.insertScript = function (url) {
    loadedScripts.push(url);
};

MauticJS.enhanceDynamicContent(content);
var beforeTracking = loadedScripts.slice();
MauticJS.trackingEnabled = true;
MauticJS.enhanceDynamicContent(content);

return {
    beforeTracking: beforeTracking,
    afterTracking: loadedScripts.slice(beforeTracking.length)
};
JS);

        $mauticBaseUrl = $this->getMauticUrl($I);
        $I->assertSame([
            $mauticBaseUrl.'/focus/1/display.js',
            $mauticBaseUrl.'/focus/4/display.js',
        ], $loadedScripts['beforeTracking']);
        $I->assertSame([
            $mauticBaseUrl.'/focus/1/display.js',
            $mauticBaseUrl.'/focus/2.js',
            $mauticBaseUrl.'/focus/4/display.js',
        ], $loadedScripts['afterTracking']);
    }

    public function essentialThenTrackingRequestsDwcOnce(\AcceptanceTester $I): void
    {
        // After consent (essential + tracking loaded together), DWC fallback should
        // be replaced via a single DWC API request, triggered before the first event
        // delivery. Test verifies exactly 1 DWC request + 1 event request fire.
        $this->allowCorsFromFixtureOrigin();

        $essentialUrl = $this->getMauticUrl($I).'/mautic-essential.js';
        $trackingUrl  = $this->getMauticUrl($I).'/mautic-tracking.js';
        $this->loadScripts($I, [$essentialUrl, $trackingUrl], ['dwc' => '1']);
        $I->waitForJS('return window.MauticJS && window.MauticJS.firstDeliveryMade === true', 10);
        $I->waitForJS('return Date.now() - window.mauticLastNetworkActivity >= window.mauticNetworkQuietPeriod', 10);

        $state = $this->grabBrowserState($I);
        $eventRequests = array_values(array_filter(
            $state['networkRequests'],
            fn (array $request): bool => '/mtc/event' === parse_url($request['url'], PHP_URL_PATH),
        ));
        $dwcRequests = array_values(array_filter(
            $state['networkRequests'],
            fn (array $request): bool => '/dwc/acceptance-test' === parse_url($request['url'], PHP_URL_PATH),
        ));

        $I->assertTrue($state['runtimeReady']);
        $I->assertTrue($state['trackingEnabled']);
        $I->assertSame([], $state['errors']);
        $I->assertSame([$essentialUrl, $trackingUrl], array_slice(array_column($state['networkRequests'], 'url'), 0, 2));
        $I->assertCount(1, $eventRequests);
        $I->assertCount(1, $dwcRequests);
        $I->assertSame(1, $state['pageViewCounter']);
        $I->assertSame(1, $state['pageEventDeliveryCount']);
    }

    private function loadScript(\AcceptanceTester $I, string $endpoint): string
    {
        $scriptUrl = $this->getMauticUrl($I).$endpoint;

        $this->loadScripts($I, [$scriptUrl]);

        return $scriptUrl;
    }

    /**
     * @param string[]              $scriptUrls
     * @param array<string, string> $fixtureParameters
     */
    private function loadScripts(\AcceptanceTester $I, array $scriptUrls, array $fixtureParameters = []): void
    {
        $queryParts = array_map(fn (string $url): string => 'script='.rawurlencode($url), $scriptUrls);
        foreach ($fixtureParameters as $name => $value) {
            $queryParts[] = rawurlencode($name).'='.rawurlencode($value);
        }

        $query      = implode('&', $queryParts);
        $fixtureUrl = '/tests/_data/mautic-script-split.html?'.$query;

        $I->amOnPage($fixtureUrl);
        $I->waitForJS('return window.mauticScriptFinished === true', 10);
        $I->assertSame([], $I->executeJS('return window.mauticScriptErrors;'));
        $I->waitForJS('return Date.now() - window.mauticLastNetworkActivity >= window.mauticNetworkQuietPeriod', 10);
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
    pageViewCounter: window.MauticJS && window.MauticJS.pageViewCounter,
    pageEventDeliveryCount: window.mauticPageEventDeliveries.length,
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

    private function allowCorsFromFixtureOrigin(): void
    {
        $configPath                  = $this->getCorsConfigPath();
        $this->corsConfigExisted     = file_exists($configPath);
        $this->corsConfigBackup      = $this->corsConfigExisted ? (string) file_get_contents($configPath) : '';

        if (null === $this->corsConfigParameters) {
            $parameters = [];
            if ($this->corsConfigExisted) {
                include_once $configPath;
            }
            $this->corsConfigParameters = $parameters;
        }

        $parameters                          = $this->corsConfigParameters;
        $parameters['cors_restrict_domains'] = true;
        $parameters['cors_valid_domains']    = ['https://web'];

        file_put_contents($configPath, "<?php\n\n\$parameters = ".var_export($parameters, true).";\n");
    }

    private function getCorsConfigPath(): string
    {
        return dirname(__DIR__, 2).'/config/parameters_local.php';
    }
}
