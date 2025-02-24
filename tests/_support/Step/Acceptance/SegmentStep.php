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
        $I=$this;
        $I->amOnPage(SegmentsPage::$URL);
        $I->waitForElementClickable(SegmentsPage::$NEW_BUTTON);
        $I->click(SegmentsPage::$NEW_BUTTON);
        $I->waitForElementVisible(SegmentsPage::$SEGMENT_NAME);
        $I->fillField(SegmentsPage::$SEGMENT_NAME, $name);
        $I->click(SegmentsPage::$SAVE_AND_CLOSE_BUTTON);
    }
}
