<?php

namespace Step\Acceptance;

use Page\Acceptance\EmailsPage;

class EmailStep extends \AcceptanceTester
{
    /**
     * Create an email with the given name.
     */
    public function createAnEmail(string $name): void
    {
        $I=$this;
        $I->amOnPage(EmailsPage::$URL);
        $I->wait(1);
        $I->click(EmailsPage::$NEW);
        $I->waitForElementClickable(EmailsPage::$SELECT_SEGMENT_EMAIL);
        $I->click(EmailsPage::$SELECT_SEGMENT_EMAIL);
        $I->fillField(EmailsPage::$SUBJECT_FIELD, $name);
        $I->click(''.EmailsPage::$CONTACT_SEGMENT_DROPDOWN);
        $I->waitForElementClickable(EmailsPage::$CONTACT_SEGMENT_OPTION);
        $I->click(EmailsPage::$CONTACT_SEGMENT_OPTION);
        $I->click(EmailsPage::$SAVE_AND_CLOSE);
    }

    /**
     * Change the category of the currently opened email.
     *
     * @return string the new category name
     */
    public function changeEmailCategory(): string
    {
        $I = $this;
        $I->waitForElementClickable(EmailsPage::$NEW_CATEGORY_DROPDOWN);
        $I->click(EmailsPage::$NEW_CATEGORY_DROPDOWN);
        $I->waitForElementVisible(EmailsPage::$NEW_CATEGORY_OPTION);
        $newCategoryName = $I->grabTextFrom(EmailsPage::$NEW_CATEGORY_OPTION);
        $I->click(EmailsPage::$NEW_CATEGORY_OPTION);
        $I->waitForElementClickable(EmailsPage::$SAVE_BUTTON);
        $I->click(EmailsPage::$SAVE_BUTTON);

        return $newCategoryName;
    }
}
