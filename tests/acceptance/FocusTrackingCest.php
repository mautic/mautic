<?php

declare(strict_types=1);

namespace Acceptance;

final class FocusTrackingCest
{
    private const FOCUS_ID = 999991;

    public function _before(\AcceptanceTester $I): void
    {
        $I->haveInDatabase('test_focus', [
            'id'           => self::FOCUS_ID,
            'is_published' => 1,
            'name'         => 'Focus tracking acceptance test',
            'focus_type'   => 'link',
            'style'        => 'modal',
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
                    'headline'        => 'Focus tracking test',
                    'tagline'         => null,
                    'link_text'       => 'Continue',
                    'link_url'        => 'https://privacysafe.example/destination',
                    'link_new_window' => 1,
                    'font'            => 'Arial, Helvetica, sans-serif',
                    'css'             => null,
                ],
                'when'                  => 'leave',
                'timeout'               => null,
                'frequency'             => 'everypage',
                'stop_after_conversion' => 0,
            ]),
        ]);
    }

    public function queuesConsentBeforeAsyncDisplayAndTracksFutureEngagement(\AcceptanceTester $I): void
    {
        $this->openFixture($I, true);
        $I->waitForJS(sprintf('return window.MauticFocusItems && window.MauticFocusItems[%d].runtimeReady === true', self::FOCUS_ID), 10);

        $queuedState = $this->trackingState($I);
        $I->assertSame([], $queuedState['errors']);
        $I->assertTrue($queuedState['trackingEnabled']);
        $I->assertSame(1, $queuedState['displayRequests']);
        $I->assertSame(1, $queuedState['trackingRequests']);

        $this->openFixture($I, false);
        $I->waitForJS(sprintf('return window.MauticFocusItems && window.MauticFocusItems[%d].runtimeReady === true', self::FOCUS_ID), 10);
        $I->executeJS(sprintf('window.MauticFocusItems[%d].engageVisitor();', self::FOCUS_ID));
        $I->waitForJS(sprintf('return window.MauticFocusItems[%d].iframeDoc !== undefined', self::FOCUS_ID), 10);

        $preActivationState = $this->trackingState($I);
        $I->assertSame(0, $preActivationState['viewRequests']);

        $I->executeJS(sprintf('window.MauticFocusItems[%d].loadTracking(); window.MauticFocusItems[%d].loadTracking();', self::FOCUS_ID, self::FOCUS_ID));
        $I->waitForJS(sprintf('return window.MauticFocusItems[%d].trackingEnabled === true', self::FOCUS_ID), 10);
        $I->waitForJS('return performance.getEntriesByType("resource").some(function (entry) { return new URL(entry.name).pathname.endsWith("/tracking.js"); });', 10);

        $postActivationState = $this->trackingState($I);
        $I->assertSame(0, $postActivationState['viewRequests']);
        $I->assertSame(1, $postActivationState['trackingRequests']);
        $I->assertStringNotContainsString('privacysafe.example/destination', $postActivationState['linkUrl']);

        $I->executeJS(sprintf('window.MauticFocusItems[%d].engageVisitor(); window.MauticFocusItems[%d].engageVisitor();', self::FOCUS_ID, self::FOCUS_ID));
        $I->waitForJS('return window.focusNetworkRequests.filter(function (request) { return new URL(request.url).pathname.endsWith("/viewpixel.gif"); }).length === 1;', 10);

        $futureEngagementState = $this->trackingState($I);
        $I->assertSame(1, $futureEngagementState['viewRequests']);
        $I->assertSame([], $futureEngagementState['errors']);
        $I->assertStringContainsString('mautic_focus_'.self::FOCUS_ID.'=', $futureEngagementState['cookies']);

        $formState = $I->executeJS(sprintf(<<<'JS'
var focus = window.MauticFocusItems[%d];
focus.iframeDoc.body.insertAdjacentHTML('beforeend', '<div class="mauticform_wrapper"><form data-mautic-form="test"></form></div>');
focus.convertVisitor();

return focus.iframeDoc.querySelector('input[name="mauticform[focusId]"]').value;
JS, self::FOCUS_ID));
        $I->assertSame((string) self::FOCUS_ID, $formState);
    }

    private function openFixture(\AcceptanceTester $I, bool $queued): void
    {
        $mauticUrl = rtrim((string) getenv('BROWSERTEST_OUTPUT_BASE_URL'), '/');
        $I->assertNotSame('', $mauticUrl, 'BROWSERTEST_OUTPUT_BASE_URL must contain the canonical DDEV URL.');
        $I->amOnPage('/tests/_data/focus-tracking.html?focus='.self::FOCUS_ID.'&queued='.($queued ? '1' : '0').'&mautic='.rawurlencode($mauticUrl));
    }

    /**
     * @return array{cookies: string, displayRequests: int, errors: array<int, string>, linkUrl: string, trackingEnabled: bool, trackingRequests: int, viewRequests: int}
     */
    private function trackingState(\AcceptanceTester $I): array
    {
        return $I->executeJS(<<<'JS'
var resources = performance.getEntriesByType('resource').map(function (entry) {
    return new URL(entry.name).pathname;
});
var focus = window.MauticFocusItems[999991];

return {
    cookies: document.cookie,
    displayRequests: resources.filter(function (path) { return path.endsWith('/display.js'); }).length,
    errors: window.focusScriptErrors,
    linkUrl: focus.iframeDoc ? focus.iframeDoc.querySelector('.mf-link').href : '',
    trackingEnabled: focus.trackingEnabled,
    trackingRequests: resources.filter(function (path) { return path.endsWith('/tracking.js'); }).length,
    viewRequests: window.focusNetworkRequests.filter(function (request) { return new URL(request.url).pathname.endsWith('/viewpixel.gif'); }).length
};
JS);
    }
}
