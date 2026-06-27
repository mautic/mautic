<?php

namespace Step\Acceptance;

use Page\Acceptance\SegmentsPage;

class SegmentStep extends \AcceptanceTester
{
    /**
     * Create a contact segment with the given name.
     */
    public function createAContactSegment(string $name): void
    {
        $I = $this;
        $I->amOnPage(SegmentsPage::$URL);
        $I->waitForElementClickable(SegmentsPage::$NEW_BUTTON, self::TIMEOUT);
        $I->click(SegmentsPage::$NEW_BUTTON);
        $I->waitForElementVisible(SegmentsPage::$SEGMENT_NAME, self::TIMEOUT);
        $I->reloadPage(); // Temp fix: The CSRF token is invalid. Please try to resubmit the form.
        $I->waitForElementVisible(SegmentsPage::$SEGMENT_NAME, self::TIMEOUT);
        $I->fillField(SegmentsPage::$SEGMENT_NAME, $name);
        $I->waitForElementClickable(SegmentsPage::$SAVE_AND_CLOSE_BUTTON, self::TIMEOUT);
        $I->click(SegmentsPage::$SAVE_AND_CLOSE_BUTTON);
        $I->seeNotificationAppear('has been created!');
    }
}
