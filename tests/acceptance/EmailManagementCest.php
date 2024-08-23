<?php

declare(strict_types=1);

use Page\Acceptance\EmailsPage;

class EmailManagementCest
{
    public const ADMIN_PASSWORD                   = 'Maut1cR0cks!';
    public const ADMIN_USER                       = 'admin';
    public const CATEGORY_PREFIX                  = 'category #';
    public const EMAIL_PREFIX                     = 'email #';
    public const EMAILS_TABLE                     = 'test_emails';
    public const CATEGORIES_TABLE                 = 'test_categories';
    public const EMAILS_QTY                       = 3;
    public const CATEGORY_QTY                     = 3;
    public const SELECT_SEGMENT_EMAIL             = '#app-content > div > div.modal.fade.in.email-type-modal > div > div > div.modal-body.form-select-modal > div > div:nth-child(2) > div > div.hidden-xs.panel-footer.text-center > button';
    public const CONTACT_SEGMENT_DROPDOWN         = '#emailform_lists_chosen';
    public const CONTACT_SEGMENT_OPTION           = '#emailform_lists_chosen > div > ul > li';
    public const EMAILFORM_BUTTONS_SAVE_AND_CLOSE = '#emailform_buttons_save_toolbar';
    private int $oldCategoryId;

    public function _before(AcceptanceTester $I): void
    {
        $I->login(self::ADMIN_USER, self::ADMIN_PASSWORD);
    }

    public function tryToBatchChangeEmailCategory(AcceptanceTester $I)
    {
        // Create a contact segment

        // Create a new category
        $I->amOnPage('/s/categories');
        $I->waitForElementClickable('#new');
        $I->click('#new');

        $I->waitForElementClickable('#category_form_bundle_chosen > a > span');
        $I->click('#category_form_bundle_chosen');

        $emailOption = '#category_form_bundle_chosen > div > ul > li.active-result:nth-child(4)';
        $I->waitForElementClickable($emailOption);
        $I->click($emailOption);
        $newCategoryName = '000 - Category '.date('Y:m:d:H:i:s');
        $I->fillField('category_form[title]', $newCategoryName);
        $I->click('#MauticSharedModal > div > div > div.modal-footer > div > button.btn.btn-default.btn-save.btn-copy');

        // Create a new email
        $I->amOnPage('/s/emails');
        $I->waitForElementClickable('#new');
        $I->click('#new');
        $I->waitForElementClickable(self::SELECT_SEGMENT_EMAIL);
        $I->click(self::SELECT_SEGMENT_EMAIL);
        $I->fillField('emailform[subject]', 'Email '.date('Y:m:d:H:i:s'));
        $I->click(''.self::CONTACT_SEGMENT_DROPDOWN);
        $I->waitForElementClickable(self::CONTACT_SEGMENT_OPTION);
        $I->click(self::CONTACT_SEGMENT_OPTION);
        $I->click(self::EMAILFORM_BUTTONS_SAVE_AND_CLOSE);

        // Change category
        $I->amOnPage('/s/emails');
        $I->waitForElementClickable(EmailsPage::$selectAllCheckbox);
        $I->click(EmailsPage::$selectAllCheckbox);

        $I->waitForElementClickable(EmailsPage::$selectedActionsDropdown);
        $I->click(EmailsPage::$selectedActionsDropdown);

        $I->waitForElementClickable(EmailsPage::$changeCategoryAction);
        $I->click(EmailsPage::$changeCategoryAction);

        $I->waitForElementClickable(EmailsPage::$newCategoryDropdown);

        // Click the dropdown menu
        $I->click('#email_batch_newCategory_chosen > a > span');
        $newCategorySelector = '#email_batch_newCategory_chosen > div > ul > li.active-result:nth-child(1)';
        $I->waitForElementVisible($newCategorySelector);
        $firstCategoryName = $I->grabTextFrom($newCategorySelector);
        $I->click($newCategorySelector);
        $I->click(EmailsPage::$saveButton);
        $I->waitForElementNotVisible(EmailsPage::$newCategoryDropdown);

        $I->reloadPage();
        $I->see($firstCategoryName, '//*[@id="app-content"]/div/div[2]/div[2]/div[1]/table/tbody/tr[1]/td[3]/div');
    }
}
