<?php

declare(strict_types=1);

use Page\Acceptance\CategoriesPage;
use Page\Acceptance\EmailsPage;
use Page\Acceptance\SegmentsPage;

class EmailManagementCest
{
    public const ADMIN_PASSWORD                   = 'Maut1cR0cks!';
    public const ADMIN_USER                       = 'admin';
    public const DATE_FORMAT                      = 'Y:m:d H:i:s';

    public function _before(AcceptanceTester $I): void
    {
        $I->login(self::ADMIN_USER, self::ADMIN_PASSWORD);
    }

    public function tryToBatchChangeEmailCategory(AcceptanceTester $I)
    {
        // Create a contact segment
        $I->amOnPage(SegmentsPage::URL);
        $I->click(SegmentsPage::NEW_BUTTON);
        $now = date(self::DATE_FORMAT);
        $I->wait(1);
        $I->fillField(SegmentsPage::LEADLIST_NAME, 'Segment '.$now);
        $I->click(SegmentsPage::SAVE_AND_CLOSE_BUTTON);

        // Create a new category
        $I->amOnPage(CategoriesPage::URL);
        $I->wait(1);
        $I->click(CategoriesPage::NEW_BUTTON);
        $I->waitForElementClickable(CategoriesPage::BUNDLE_DROPDOWN);
        $I->click(CategoriesPage::BUNDLE_DROPDOWN);

        $I->waitForElementClickable(CategoriesPage::BUNDLE_EMAIL_OPTION);
        $I->click(CategoriesPage::BUNDLE_EMAIL_OPTION);
        $I->fillField(CategoriesPage::TITLE_FIELD, '000 - Category '.date(self::DATE_FORMAT));
        $I->click(CategoriesPage::SAVE_AND_CLOSE);

        // Create a new email
        $I->amOnPage(EmailsPage::URL);
        $I->wait(1);
        $I->click(EmailsPage::NEW);
        $I->waitForElementClickable(EmailsPage::SELECT_SEGMENT_EMAIL);
        $I->click(EmailsPage::SELECT_SEGMENT_EMAIL);
        $I->fillField(EmailsPage::SUBJECT_FIELD, 'Email '.date(self::DATE_FORMAT));
        $I->click(''.EmailsPage::CONTACT_SEGMENT_DROPDOWN);
        $I->waitForElementClickable(EmailsPage::CONTACT_SEGMENT_OPTION);
        $I->click(EmailsPage::CONTACT_SEGMENT_OPTION);
        $I->click(EmailsPage::SAVE_AND_CLOSE);

        // Select all emails
        $I->amOnPage(EmailsPage::URL);
        $I->wait(1);
        $I->waitForElementClickable(EmailsPage::SELECT_ALL_CHECKBOX);
        $I->click(EmailsPage::SELECT_ALL_CHECKBOX);

        // Select change category action
        $I->waitForElementClickable(EmailsPage::SELECTED_ACTIONS_DROPDOWN);
        $I->click(EmailsPage::SELECTED_ACTIONS_DROPDOWN);
        $I->waitForElementClickable(EmailsPage::CHANGE_CATEGORY_ACTION);
        $I->click(EmailsPage::CHANGE_CATEGORY_ACTION);

        // Select new category
        $I->waitForElementClickable(EmailsPage::NEW_CATEGORY_DROPDOWN);
        $I->click(EmailsPage::NEW_CATEGORY_DROPDOWN);
        $I->waitForElementVisible(EmailsPage::NEW_CATEGORY_OPTION);
        $firstCategoryName = $I->grabTextFrom(EmailsPage::NEW_CATEGORY_OPTION);
        $I->click(EmailsPage::NEW_CATEGORY_OPTION);
        $I->click(EmailsPage::SAVE_BUTTON);
        $I->waitForElementNotVisible(EmailsPage::NEW_CATEGORY_DROPDOWN);

        $I->reloadPage();
        $categories = $I->grabMultiple('span.label-category');
        for ($i = 1; $i <= count($categories); ++$i) {
            $I->see($firstCategoryName, '//*[@id="app-content"]/div/div[2]/div[2]/div[1]/table/tbody/tr['.$i.']/td[3]/div');
        }
    }
}
