<?php

namespace Step\Acceptance;

use Facebook\WebDriver\WebDriverKeys;
use Page\Acceptance\SegmentsPage;

class SegmentStep extends \AcceptanceTester
{
    /**
     * Create a contact segment with the given name.
     *
     * @param string $name
     */
    public function createAContactSegment(string $name): void
    {
        $this->amOnPage(SegmentsPage::$URL);
        $this->waitForElementClickable(SegmentsPage::$NEW_BUTTON);
        $this->click(SegmentsPage::$NEW_BUTTON);
        $this->waitForElementVisible(SegmentsPage::$SEGMENT_NAME);
        $this->fillField(SegmentsPage::$SEGMENT_NAME, $name);
        $this->click(SegmentsPage::$SAVE_AND_CLOSE_BUTTON);
    }

}