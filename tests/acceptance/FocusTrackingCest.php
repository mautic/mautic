<?php

declare(strict_types=1);

namespace Acceptance;

final class FocusTrackingCest
{
    private const BAR_FOCUS_ID = 999994;
    private const DESTINATION_URL = 'https://privacysafe.example/destination';
    private const DISPLAY_FOCUS_ID = 999991;
    private const EXPLICIT_FOCUS_ID = 999992;
    private const LEGACY_FOCUS_ID = 999995;
    private const QUEUED_FOCUS_ID = 999993;
    private const REMOVED_FOCUS_ID = 999996;

    private ?string $corsConfigBackup = null;

    private bool $corsConfigExisted = false;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $corsConfigParameters = null;

    public function _before(\AcceptanceTester $I): void
    {
        foreach ($this->focusFixtures() as $focus) {
            $id = $focus['id'];
            if (0 === $I->grabNumRecords('test_focus', ['id' => $id])) {
                $I->haveInDatabase('test_focus', $focus);
            } else {
                unset($focus['id']);
                $I->updateInDatabase('test_focus', $focus, ['id' => $id]);
            }
        }
    }

    public function _after(): void
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

    public function displayOnlyIsAnonymousAndPreservesFunctionalState(\AcceptanceTester $I): void
    {
        $displayPath = $this->focusPath(self::DISPLAY_FOCUS_ID, 'display');
        $this->openFixture($I, [$displayPath], ['reset' => '1', 'seed' => '1']);
        $this->waitForFocus($I, self::DISPLAY_FOCUS_ID, true);
        $this->waitForNetworkQuiet($I);

        $state = $this->focusState($I, self::DISPLAY_FOCUS_ID);
        $I->assertNotSame($state['pageOrigin'], $this->requestOrigin($state['requests'], $displayPath));
        $I->assertSame([], $state['errors']);
        $I->assertNotNull($state['item']);
        $I->assertSame(1, $state['iframeCount']);
        $I->assertSame(self::DESTINATION_URL, $this->linkUrl($I, self::DISPLAY_FOCUS_ID));
        $I->assertSame(1, $this->requestCount($state['requests'], $displayPath, 'script'));
        $I->assertSame(0, $this->requestCount($state['requests'], $this->focusPath(self::DISPLAY_FOCUS_ID, 'tracking')));
        $I->assertSame(0, $this->requestCount($state['requests'], $this->focusPath(self::DISPLAY_FOCUS_ID, 'view')));
        $I->seeNumRecords(0, 'test_focus_stats', ['focus_id' => self::DISPLAY_FOCUS_ID]);
        $I->assertSame([], $this->trackingStorageAccess($state['storageAccess']));
        $this->assertIdentityValues($I, $state['identity']);
        $this->assertIdentityValuesAbsentFromRequests($I, $state['requests']);
        $I->assertMatchesRegularExpression('/(?:^|; )mautic_focus_'.self::DISPLAY_FOCUS_ID.'=\d+(?:;|$)/', $state['identity']['cookies']);

        $formMarkerCount = $I->executeJS(sprintf(<<<'JS'
var focus = window.MauticFocusItems[%d];
focus.iframeDoc.body.insertAdjacentHTML('beforeend', '<div class="mauticform_wrapper"><form data-mautic-form="test"></form></div>');

return focus.iframeDoc.querySelectorAll('input[name="mauticform[focusId]"]').length;
JS, self::DISPLAY_FOCUS_ID));
        $I->assertSame(0, $formMarkerCount);

        $displayUrl = $this->getMauticUrl($I).$displayPath;
        $I->executeJS('window.originalFocusItem = window.MauticFocusItems['.self::DISPLAY_FOCUS_ID.']; window.focusFixture.loadScript('.json_encode($displayUrl, JSON_THROW_ON_ERROR).').then(function () { window.secondDisplayLoaded = true; });');
        $I->waitForJS('return window.secondDisplayLoaded === true', 10);
        $idempotentState = $I->executeJS('return {sameItem: window.originalFocusItem === window.MauticFocusItems['.self::DISPLAY_FOCUS_ID.'], iframeCount: document.querySelectorAll("iframe").length};');
        $I->assertTrue($idempotentState['sameItem']);
        $I->assertSame(1, $idempotentState['iframeCount']);

        $I->executeJS('window.MauticFocusItems['.self::DISPLAY_FOCUS_ID.'].iframeDoc.querySelector(".mf-modal-close a").click();');
        $I->waitForJS('return document.querySelectorAll("iframe").length === 0', 10);
        $I->executeJS('window.MauticFocusItems['.self::DISPLAY_FOCUS_ID.'].convertVisitor();');
        $modalState = $this->focusState($I, self::DISPLAY_FOCUS_ID);
        $I->assertStringContainsString('mautic_focus_'.self::DISPLAY_FOCUS_ID.'=-1', $modalState['identity']['cookies']);
        $I->assertStringContainsString('mautic_focus_'.self::DISPLAY_FOCUS_ID.'_closed=1', $modalState['identity']['cookies']);
        $this->assertAllowedFocusCookieWrites($I, $modalState['cookieWrites'], [
            'mautic_focus_'.self::DISPLAY_FOCUS_ID,
            'mautic_focus_'.self::DISPLAY_FOCUS_ID.'_closed',
        ]);

        $this->openFixture($I, [$displayPath]);
        $I->waitForJS('return window.focusFixture.finished && window.MauticFocusItems && window.MauticFocusItems['.self::DISPLAY_FOCUS_ID.'].initialized', 10);
        $I->assertSame(0, $I->executeJS('return document.querySelectorAll("iframe").length;'));

        $barPath = $this->focusPath(self::BAR_FOCUS_ID, 'display');
        $this->openFixture($I, [$barPath]);
        $this->waitForFocus($I, self::BAR_FOCUS_ID, true);
        $I->executeJS('document.querySelector(".mf-bar-collapser-'.self::BAR_FOCUS_ID.'").click();');
        $I->waitForJS('return document.querySelector(".mf-bar-collapser-'.self::BAR_FOCUS_ID.' svg").getAttribute("data-transform-direction") === "90"', 10);
        $collapsedState = $this->focusState($I, self::BAR_FOCUS_ID);
        $I->assertStringContainsString('mf-bar-collapser-'.self::BAR_FOCUS_ID.'=90', $collapsedState['identity']['cookies']);
        $this->assertAllowedFocusCookieWrites($I, $collapsedState['cookieWrites'], [
            'mautic_focus_'.self::BAR_FOCUS_ID,
            'mf-bar-collapser-'.self::BAR_FOCUS_ID,
        ]);

        $this->openFixture($I, [$barPath]);
        $this->waitForFocus($I, self::BAR_FOCUS_ID, true);
        $I->waitForJS('return document.querySelector(".mf-bar-collapser-'.self::BAR_FOCUS_ID.'").classList.contains("mf-bar-collapsed") && document.querySelector(".mf-bar-collapser-'.self::BAR_FOCUS_ID.' svg").getAttribute("data-transform-direction") === "90"', 10);
        $finalState = $this->focusState($I, self::BAR_FOCUS_ID);
        $this->assertIdentityValues($I, $finalState['identity']);
        $I->seeNumRecords(0, 'test_focus_stats', ['focus_id' => self::DISPLAY_FOCUS_ID]);
        $I->seeNumRecords(0, 'test_focus_stats', ['focus_id' => self::BAR_FOCUS_ID]);
    }

    public function displayThenTrackingCountsMountedItemOnce(\AcceptanceTester $I): void
    {
        $displayPath = $this->focusPath(self::EXPLICIT_FOCUS_ID, 'display');
        $trackingPath = $this->focusPath(self::EXPLICIT_FOCUS_ID, 'tracking');
        $viewPath = $this->focusPath(self::EXPLICIT_FOCUS_ID, 'view');
        $this->openFixture($I, [$displayPath], ['reset' => '1']);
        $I->waitForJS('return window.focusFixture.finished && window.MauticFocusItems && window.MauticFocusItems['.self::EXPLICIT_FOCUS_ID.'].runtimeReady', 10);
        $I->executeJS('window.MauticFocusItems['.self::EXPLICIT_FOCUS_ID.'].engageVisitor();');
        $this->waitForFocus($I, self::EXPLICIT_FOCUS_ID, true);
        $I->executeJS('window.MauticFocusItems['.self::EXPLICIT_FOCUS_ID.'].iframeDoc.body.insertAdjacentHTML("beforeend", "<div class=\"mauticform_wrapper\"><form data-mautic-form=\"test\"></form></div>");');

        $preActivationState = $this->focusState($I, self::EXPLICIT_FOCUS_ID);
        $I->assertSame(self::DESTINATION_URL, $this->linkUrl($I, self::EXPLICIT_FOCUS_ID));
        $I->assertSame(0, $this->formMarkerCount($I, self::EXPLICIT_FOCUS_ID));
        $I->assertSame(0, $this->requestCount($preActivationState['requests'], $viewPath));
        $I->seeNumRecords(0, 'test_focus_stats', ['focus_id' => self::EXPLICIT_FOCUS_ID]);

        $I->executeJS('window.MauticFocusItems['.self::EXPLICIT_FOCUS_ID.'].loadTracking(); window.MauticFocusItems['.self::EXPLICIT_FOCUS_ID.'].loadTracking();');
        $I->waitForJS('return window.MauticFocusItems['.self::EXPLICIT_FOCUS_ID.'].trackingEnabled === true', 10);
        $this->waitForNetworkQuiet($I);

        $postActivationState = $this->focusState($I, self::EXPLICIT_FOCUS_ID);
        $I->assertSame(1, $this->requestCount($postActivationState['requests'], $trackingPath, 'script'));
        $I->assertSame(1, $postActivationState['iframeCount']);
        $I->assertSame(1, $this->requestCount($postActivationState['requests'], $viewPath, 'image'));
        $I->seeNumRecords(1, 'test_focus_stats', ['focus_id' => self::EXPLICIT_FOCUS_ID, 'type' => 'view']);
        $I->assertNotSame(self::DESTINATION_URL, $this->linkUrl($I, self::EXPLICIT_FOCUS_ID));
        $I->assertSame(1, $this->formMarkerCount($I, self::EXPLICIT_FOCUS_ID));

        $I->executeJS('window.MauticFocusItems['.self::EXPLICIT_FOCUS_ID.'].trackingHooks.onEngage(); window.MauticFocusItems['.self::EXPLICIT_FOCUS_ID.'].trackingHooks.onEngage();');
        $this->waitForNetworkQuiet($I);
        $deduplicatedState = $this->focusState($I, self::EXPLICIT_FOCUS_ID);
        $I->assertSame(1, $this->requestCount($deduplicatedState['requests'], $viewPath, 'image'));
        $I->assertSame([], $deduplicatedState['errors']);
        $I->seeNumRecords(1, 'test_focus_stats', ['focus_id' => self::EXPLICIT_FOCUS_ID, 'type' => 'view']);

        $removedDisplayPath = $this->focusPath(self::REMOVED_FOCUS_ID, 'display');
        $removedTrackingPath = $this->focusPath(self::REMOVED_FOCUS_ID, 'tracking');
        $removedViewPath = $this->focusPath(self::REMOVED_FOCUS_ID, 'view');
        $this->openFixture($I, [$removedDisplayPath], ['reset' => '1']);
        $I->waitForJS('return window.focusFixture.finished && window.MauticFocusItems && window.MauticFocusItems['.self::REMOVED_FOCUS_ID.'].runtimeReady', 10);
        $I->executeJS('window.MauticFocusItems['.self::REMOVED_FOCUS_ID.'].engageVisitor();');
        $this->waitForFocus($I, self::REMOVED_FOCUS_ID, true);
        $I->executeJS('window.MauticFocusItems['.self::REMOVED_FOCUS_ID.'].iframeDoc.querySelector(".mf-modal-close a").click();');
        $I->waitForJS('return document.querySelectorAll("iframe").length === 0', 10);
        $I->executeJS('window.MauticFocusItems['.self::REMOVED_FOCUS_ID.'].loadTracking();');
        $I->waitForJS('return window.MauticFocusItems['.self::REMOVED_FOCUS_ID.'].trackingEnabled === true', 10);
        $this->waitForNetworkQuiet($I);

        $removedState = $this->focusState($I, self::REMOVED_FOCUS_ID);
        $I->assertSame(1, $this->requestCount($removedState['requests'], $removedTrackingPath, 'script'));
        $I->assertSame(0, $this->requestCount($removedState['requests'], $removedViewPath));
        $I->assertSame([], $removedState['errors']);
        $I->seeNumRecords(0, 'test_focus_stats', ['focus_id' => self::REMOVED_FOCUS_ID, 'type' => 'view']);
    }

    public function queuedConsentActivatesBeforeInitialEngagement(\AcceptanceTester $I): void
    {
        $displayPath = $this->focusPath(self::QUEUED_FOCUS_ID, 'display');
        $trackingPath = $this->focusPath(self::QUEUED_FOCUS_ID, 'tracking');
        $viewPath = $this->focusPath(self::QUEUED_FOCUS_ID, 'view');
        $this->openFixture($I, [$displayPath], ['reset' => '1', 'queued' => (string) self::QUEUED_FOCUS_ID]);
        $I->waitForJS('return Boolean(window.MauticFocusItems && window.MauticFocusItems['.self::QUEUED_FOCUS_ID.'].trackingEnabled && window.MauticFocusItems['.self::QUEUED_FOCUS_ID.'].iframeDoc)', 10);
        $I->waitForJS('return window.focusFixture.countRequests('.json_encode($viewPath, JSON_THROW_ON_ERROR).', "image") === 1', 10);
        $this->waitForNetworkQuiet($I);

        $state = $this->focusState($I, self::QUEUED_FOCUS_ID);
        $I->assertSame(1, $this->requestCount($state['requests'], $displayPath, 'script'));
        $I->assertSame(1, $this->requestCount($state['requests'], $trackingPath, 'script'));
        $I->assertFalse($state['queueEntry']);
        $I->assertTrue($state['item']['trackingEnabled']);
        $I->assertSame(1, $state['iframeCount']);
        $I->assertSame(1, $this->requestCount($state['requests'], $viewPath, 'image'));
        $I->assertSame([], $state['errors']);
        $I->seeNumRecords(1, 'test_focus_stats', ['focus_id' => self::QUEUED_FOCUS_ID, 'type' => 'view']);
    }

    public function mauticTrackingConsentActivatesFocusOnlyWithExplicitOptIn(\AcceptanceTester $I): void
    {
        $this->allowCorsFromFixtureOrigin();
        $displayPath = $this->focusPath(self::EXPLICIT_FOCUS_ID, 'display');
        $trackingPath = $this->focusPath(self::EXPLICIT_FOCUS_ID, 'tracking');
        $scripts = [$displayPath, '/mautic-essential.js', '/mautic-tracking.js'];

        $this->openFixture($I, $scripts, ['reset' => '1']);
        $I->waitForJS('return window.focusFixture.finished && window.MauticJS && window.MauticJS.trackingEnabled === true', 10);
        $this->waitForNetworkQuiet($I);
        $independentState = $this->focusState($I, self::EXPLICIT_FOCUS_ID);
        $I->assertTrue($I->executeJS('return window.MauticJS.trackingEnabled === true;'));
        $I->assertFalse($independentState['item']['trackingEnabled']);
        $I->assertSame(0, $this->requestCount($independentState['requests'], $trackingPath));
        $I->assertSame([], $independentState['errors']);

        $this->openFixture($I, $scripts, ['reset' => '1', 'shared' => '1']);
        $I->waitForJS('return window.focusFixture.finished && window.MauticJS && window.MauticJS.trackingEnabled === true && window.MauticFocusItems['.self::EXPLICIT_FOCUS_ID.'].trackingEnabled === true', 10);
        $this->waitForNetworkQuiet($I);
        $sharedState = $this->focusState($I, self::EXPLICIT_FOCUS_ID);
        $I->assertTrue($I->executeJS('return window.MauticJS.trackingEnabled === true;'));
        $I->assertTrue($sharedState['item']['trackingEnabled']);
        $I->assertSame(1, $this->requestCount($sharedState['requests'], $trackingPath, 'script'));
        $I->assertSame([], $sharedState['errors']);
    }

    public function legacyEndpointStillRendersAndTracks(\AcceptanceTester $I): void
    {
        $legacyPath = $this->focusPath(self::LEGACY_FOCUS_ID, 'legacy');
        $viewPath = $this->focusPath(self::LEGACY_FOCUS_ID, 'view');
        $this->openFixture($I, [$legacyPath], ['reset' => '1']);
        $this->waitForFocus($I, self::LEGACY_FOCUS_ID, true);
        $I->waitForJS('return Array.from(window.MauticFocusItems['.self::LEGACY_FOCUS_ID.'].iframeDoc.images).filter(function (image) { return new URL(image.src).pathname === '.json_encode($viewPath, JSON_THROW_ON_ERROR).' && image.complete; }).length === 1', 10);

        $state = $this->focusState($I, self::LEGACY_FOCUS_ID);
        $I->assertSame([], $state['errors']);
        $I->assertSame(1, $this->requestCount($state['requests'], $legacyPath, 'script'));
        $I->assertSame(1, $state['iframeCount']);
        $I->assertStringContainsString('mautic_focus_'.self::LEGACY_FOCUS_ID.'=', $state['identity']['cookies']);
        $I->assertNotSame(self::DESTINATION_URL, $this->linkUrl($I, self::LEGACY_FOCUS_ID));
        $I->assertSame(1, $I->executeJS('return Array.from(window.MauticFocusItems['.self::LEGACY_FOCUS_ID.'].iframeDoc.images).filter(function (image) { return new URL(image.src).pathname === '.json_encode($viewPath, JSON_THROW_ON_ERROR).'; }).length;'));
        $I->seeNumRecords(1, 'test_focus_stats', ['focus_id' => self::LEGACY_FOCUS_ID, 'type' => 'view']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function focusFixtures(): array
    {
        return [
            $this->focusFixture(self::DISPLAY_FOCUS_ID, 'Display and functional state', 'modal', 'immediately', 'once', true),
            $this->focusFixture(self::EXPLICIT_FOCUS_ID, 'Display then tracking', 'modal', 'leave'),
            $this->focusFixture(self::QUEUED_FOCUS_ID, 'Queued consent', 'modal', 'immediately'),
            $this->focusFixture(self::BAR_FOCUS_ID, 'Bar persistence', 'bar', 'immediately'),
            $this->focusFixture(self::LEGACY_FOCUS_ID, 'Legacy aggregate', 'modal', 'immediately'),
            $this->focusFixture(self::REMOVED_FOCUS_ID, 'Removed before tracking', 'modal', 'leave'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function focusFixture(int $id, string $name, string $style, string $when, string $frequency = 'everypage', bool $stopAfterConversion = false): array
    {
        return [
            'id'           => $id,
            'is_published' => 1,
            'name'         => $name,
            'focus_type'   => 'link',
            'style'        => $style,
            'utm_tags'     => serialize([]),
            'properties'   => serialize([
                'bar' => [
                    'allow_hide' => 1,
                    'push_page'  => 1,
                    'sticky'     => 1,
                    'size'       => 'large',
                    'placement'  => 'top',
                ],
                'modal' => [
                    'placement' => 'top',
                ],
                'notification' => [
                    'placement' => 'top_left',
                ],
                'page'            => [],
                'animate'         => 0,
                'link_activation' => 0,
                'colors'          => [
                    'primary'     => '4e5d9d',
                    'text'        => '000000',
                    'button'      => 'fdb933',
                    'button_text' => 'ffffff',
                ],
                'content' => [
                    'headline'        => $name,
                    'tagline'         => null,
                    'link_text'       => 'Continue',
                    'link_url'        => self::DESTINATION_URL,
                    'link_new_window' => 1,
                    'font'            => 'Arial, Helvetica, sans-serif',
                    'css'             => null,
                ],
                'when'                  => $when,
                'timeout'               => null,
                'frequency'             => $frequency,
                'stop_after_close'      => 0,
                'stop_after_conversion' => $stopAfterConversion ? 1 : 0,
            ]),
        ];
    }

    /**
     * @param string[]              $paths
     * @param array<string, string> $parameters
     */
    private function openFixture(\AcceptanceTester $I, array $paths, array $parameters = []): void
    {
        $mauticUrl = $this->getMauticUrl($I);
        $query = [];
        foreach ($parameters as $name => $value) {
            $query[] = rawurlencode($name).'='.rawurlencode($value);
        }
        foreach ($paths as $path) {
            $query[] = 'script='.rawurlencode($mauticUrl.$path);
        }

        $I->amOnPage('/tests/_data/focus-tracking.html?'.implode('&', $query));
        $I->waitForJS('return window.focusFixture && window.focusFixture.finished === true', 10);
    }

    private function waitForFocus(\AcceptanceTester $I, int $focusId, bool $withIframe): void
    {
        $condition = 'return window.MauticFocusItems && window.MauticFocusItems['.$focusId.'] && window.MauticFocusItems['.$focusId.'].runtimeReady';
        if ($withIframe) {
            $condition .= ' && typeof window.MauticFocusItems['.$focusId.'].iframeDoc !== "undefined"';
        }
        $I->waitForJS($condition.' ? true : false', 10);
    }

    private function waitForNetworkQuiet(\AcceptanceTester $I): void
    {
        $I->waitForJS('return window.focusFixture.pendingRequests === 0 && Date.now() - window.focusFixture.lastNetworkActivity >= window.focusFixture.networkQuietPeriod', 10);
    }

    /**
     * @return array<string, mixed>
     */
    private function focusState(\AcceptanceTester $I, int $focusId): array
    {
        return $I->executeJS('return window.focusFixture.state('.$focusId.');');
    }

    private function linkUrl(\AcceptanceTester $I, int $focusId): string
    {
        return $I->executeJS('return window.MauticFocusItems['.$focusId.'].iframeDoc.querySelector(".mf-link").href;');
    }

    private function formMarkerCount(\AcceptanceTester $I, int $focusId): int
    {
        return $I->executeJS('return window.MauticFocusItems['.$focusId.'].iframeDoc.querySelectorAll("input[name=\\"mauticform[focusId]\\"]").length;');
    }

    private function focusPath(int $focusId, string $scope): string
    {
        return match ($scope) {
            'display'  => '/focus/'.$focusId.'/display.js',
            'legacy'   => '/focus/'.$focusId.'.js',
            'tracking' => '/focus/'.$focusId.'/tracking.js',
            'view'     => '/focus/'.$focusId.'/viewpixel.gif',
            default    => throw new \InvalidArgumentException('Unknown Focus script scope: '.$scope),
        };
    }

    /**
     * @param array<int, array{path: string, type: string}> $requests
     */
    private function requestCount(array $requests, string $path, ?string $type = null): int
    {
        return count(array_filter(
            $requests,
            fn (array $request): bool => $path === $request['path'] && (null === $type || $type === $request['type']),
        ));
    }

    /**
     * @param array<int, array{path: string, url: string}> $requests
     */
    private function requestOrigin(array $requests, string $path): string
    {
        foreach ($requests as $request) {
            if ($path === $request['path']) {
                return (string) parse_url($request['url'], PHP_URL_SCHEME).'://'.parse_url($request['url'], PHP_URL_HOST);
            }
        }

        return '';
    }

    /**
     * @param array<int, array{key: string, method: string, storage: string}> $storageAccess
     *
     * @return array<int, array{key: string, method: string, storage: string}>
     */
    private function trackingStorageAccess(array $storageAccess): array
    {
        return array_values(array_filter(
            $storageAccess,
            fn (array $access): bool => in_array($access['key'], ['mtc_id', 'mautic_device_id'], true),
        ));
    }

    /**
     * @param array<string, string> $identity
     */
    private function assertIdentityValues(\AcceptanceTester $I, array $identity): void
    {
        $I->assertSame('existing-local-contact', $identity['localMtcId']);
        $I->assertSame('existing-local-device', $identity['localDeviceId']);
        $I->assertSame('existing-session-contact', $identity['sessionMtcId']);
        $I->assertSame('existing-session-device', $identity['sessionDeviceId']);
        $I->assertStringContainsString('mtc_id=existing-cookie-contact', $identity['cookies']);
        $I->assertStringContainsString('mautic_device_id=existing-cookie-device', $identity['cookies']);
    }

    /**
     * @param array<int, array{body: string, url: string}> $requests
     */
    private function assertIdentityValuesAbsentFromRequests(\AcceptanceTester $I, array $requests): void
    {
        $requestData = implode(' ', array_merge(array_column($requests, 'url'), array_column($requests, 'body')));
        foreach (['existing-local-contact', 'existing-local-device', 'existing-session-contact', 'existing-session-device', 'existing-cookie-contact', 'existing-cookie-device'] as $identity) {
            $I->assertStringNotContainsString($identity, $requestData);
        }
    }

    /**
     * @param string[] $cookieWrites
     * @param string[] $allowedNames
     */
    private function assertAllowedFocusCookieWrites(\AcceptanceTester $I, array $cookieWrites, array $allowedNames): void
    {
        foreach ($cookieWrites as $cookieWrite) {
            $name = rawurldecode(strtok($cookieWrite, '='));
            $I->assertContains($name, $allowedNames);
        }
    }

    private function getMauticUrl(\AcceptanceTester $I): string
    {
        $mauticUrl = rtrim((string) getenv('BROWSERTEST_OUTPUT_BASE_URL'), '/');
        $I->assertNotSame('', $mauticUrl, 'BROWSERTEST_OUTPUT_BASE_URL must contain the canonical DDEV URL.');

        return $mauticUrl;
    }

    private function allowCorsFromFixtureOrigin(): void
    {
        $configPath = $this->getCorsConfigPath();
        $this->corsConfigExisted = file_exists($configPath);
        $this->corsConfigBackup = $this->corsConfigExisted ? (string) file_get_contents($configPath) : '';

        if (null === $this->corsConfigParameters) {
            $parameters = [];
            if ($this->corsConfigExisted) {
                include_once $configPath;
            }
            $this->corsConfigParameters = $parameters;
        }

        $parameters = $this->corsConfigParameters;
        $parameters['cors_restrict_domains'] = true;
        $parameters['cors_valid_domains'] = ['https://web'];
        file_put_contents($configPath, "<?php\n\n\$parameters = ".var_export($parameters, true).";\n");
    }

    private function getCorsConfigPath(): string
    {
        return dirname(__DIR__, 2).'/config/parameters_local.php';
    }
}
