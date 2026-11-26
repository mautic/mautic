<?php

declare(strict_types=1);

namespace Acceptance;

final class FocusEngagementCest
{
    private const FOCUS_TABLE = 'test_focus';

    private const MODAL = 'iframe.mf-modal-iframe';

    private const DAY = 86400;

    public function engagesFromPageViewNumberAndEveryNumberOfDays(\AcceptanceTester $I): void
    {
        $focusId = $this->haveFocus($I, [
            'frequency'      => 'days',
            'frequency_days' => 7,
            'min_page_views' => 2,
        ]);

        $I->amGoingTo('land on the site');
        $this->viewPage($I, $focusId);
        $I->dontSeeElementInDOM(self::MODAL);

        $I->amGoingTo('view a second page');
        $this->viewPage($I, $focusId);
        $I->seeElementInDOM(self::MODAL);

        $I->amGoingTo('view a third page on the same day');
        $this->viewPage($I, $focusId);
        $I->dontSeeElementInDOM(self::MODAL);

        $I->amGoingTo('come back 6 days after the focus was shown');
        $this->setLastEngagement($I, $focusId, 6 * self::DAY);
        $this->viewPage($I, $focusId);
        $I->dontSeeElementInDOM(self::MODAL);

        $I->amGoingTo('come back 7 days after the focus was shown');
        $this->setLastEngagement($I, $focusId, 7 * self::DAY + 60);
        $this->viewPage($I, $focusId);
        $I->seeElementInDOM(self::MODAL);

        $I->amGoingTo('come back after converting');
        $I->executeJS(sprintf("document.cookie = 'mautic_focus_%d=-1; path=/; Secure';", $focusId));
        $this->viewPage($I, $focusId);
        $I->dontSeeElementInDOM(self::MODAL);

        $I->amGoingTo('land on the site in a new browser session');
        $this->clearFocusCookies($I, $focusId);
        $this->viewPage($I, $focusId);
        $I->dontSeeElementInDOM(self::MODAL);
    }

    public function withoutAPageViewNumberEngagesOnTheLandingPageAndCountsNothing(\AcceptanceTester $I): void
    {
        $focusId = $this->haveFocus($I, ['frequency' => 'everypage']);

        $this->viewPage($I, $focusId);

        $I->seeElementInDOM(self::MODAL);
        $I->dontSeeCookie(sprintf('mautic_focus_%d_page_views', $focusId));
    }

    public function savingEveryXDaysWithoutANumberOpensTheBuilderOnTheError(\AcceptanceTester $I): void
    {
        $focusId = $this->haveFocus($I, ['frequency' => 'everypage']);
        $I->login();

        $I->amOnPage(sprintf('/s/focus/edit/%d', $focusId));
        $I->waitForElement('#focus_properties_frequency', \AcceptanceTester::TIMEOUT);
        // The focus type properties live in the builder, which is closed when the page loads.
        $I->executeJS("mQuery('#focus_properties_frequency').val('days').trigger('chosen:updated'); mQuery('#focus_properties_frequency_days').val('');");
        $I->click('#focus_buttons_apply_toolbar');

        $I->waitForElementVisible('#focus_properties_frequency_days', \AcceptanceTester::TIMEOUT);
        $I->seeElement('.builder.builder-active');
        $I->see('Enter after how many days the focus should engage the visitor again.', '.builder');
    }

    /**
     * Inserts a published modal focus that engages upon arrival, with the given
     * properties on top of those the builder saves by default.
     *
     * @param array<string, mixed> $properties
     */
    private function haveFocus(\AcceptanceTester $I, array $properties): int
    {
        return $I->haveInDatabase(self::FOCUS_TABLE, [
            'is_published' => 1,
            'date_added'   => date('Y-m-d H:i:s'),
            'name'         => 'Focus engagement',
            'focus_type'   => 'link',
            'style'        => 'modal',
            'utm_tags'     => serialize([]),
            'properties'   => serialize(array_merge([
                'bar'             => ['allow_hide' => 1, 'push_page' => 1, 'sticky' => 1, 'size' => 'large', 'placement' => 'top'],
                'modal'           => ['placement' => 'top'],
                'notification'    => ['placement' => 'top_left'],
                'page'            => [],
                'animate'         => 0,
                'link_activation' => 1,
                'colors'          => ['primary' => '4e5d9d', 'text' => '000000', 'button' => 'fdb933', 'button_text' => 'ffffff'],
                'content'         => [
                    'headline'        => 'Focus engagement',
                    'tagline'         => null,
                    'link_text'       => 'Read more',
                    'link_url'        => 'https://example.com',
                    'link_new_window' => 1,
                    'font'            => 'Arial, Helvetica, sans-serif',
                    'css'             => null,
                ],
                'when'                  => 'immediately',
                'timeout'               => null,
                'stop_after_conversion' => 1,
                'stop_after_close'      => 0,
            ], $properties)),
        ]);
    }

    private function viewPage(\AcceptanceTester $I, int $focusId): void
    {
        $I->amOnPage(sprintf('/tests/_data/focus-engagement.html?focus=%d', $focusId));
        // The script engages synchronously while it initializes, so the modal is in the DOM once it has run.
        $I->waitForJS(sprintf("return typeof window.MauticFocus%d === 'function';", $focusId), \AcceptanceTester::TIMEOUT);
    }

    private function setLastEngagement(\AcceptanceTester $I, int $focusId, int $secondsAgo): void
    {
        $I->executeJS(sprintf(
            "document.cookie = 'mautic_focus_%d=' + (Math.floor(Date.now() / 1000) - %d) + '; path=/; Secure';",
            $focusId,
            $secondsAgo
        ));
    }

    private function clearFocusCookies(\AcceptanceTester $I, int $focusId): void
    {
        foreach (['', '_page_views', '_closed'] as $suffix) {
            $I->executeJS(sprintf("document.cookie = 'mautic_focus_%d%s=; Max-Age=0; path=/; Secure';", $focusId, $suffix));
        }
    }
}
