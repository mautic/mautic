<?php

namespace Step\Acceptance;

use Facebook\WebDriver\WebDriverKeys;
use Page\Acceptance\EmailsPage;

class EmailStep extends \AcceptanceTester
{
    /**
     * Create an email with the given name.
     *
     * @param string $name
     */
    public function createAnEmail(string $name): void
    {
        $this->amOnPage(EmailsPage::$URL);
        $this->wait(1);
        $this->click(EmailsPage::$NEW);
        $this->waitForElementClickable(EmailsPage::$SELECT_SEGMENT_EMAIL);
        $this->click(EmailsPage::$SELECT_SEGMENT_EMAIL);
        $this->fillField(EmailsPage::$SUBJECT_FIELD, $name);
        $this->click(''.EmailsPage::$CONTACT_SEGMENT_DROPDOWN);
        $this->waitForElementClickable(EmailsPage::$CONTACT_SEGMENT_OPTION);
        $this->click(EmailsPage::$CONTACT_SEGMENT_OPTION);
        $this->click(EmailsPage::$SAVE_AND_CLOSE);
    }

    /**
     * Change the category of the currently opened email.
     *
     * @return string the new category name
     */
    public function changeEmailCategory(AcceptanceTester $I): string
    {
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