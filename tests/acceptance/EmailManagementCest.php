<?php

declare(strict_types=1);

use Page\Acceptance\EmailsPage;

class EmailManagementCest
{
    public const ADMIN_PASSWORD   = 'Maut1cR0cks!';
    public const ADMIN_USER       = 'admin';
    public const CATEGORY_PREFIX  = 'category #';
    public const EMAIL_PREFIX     = 'email #';
    public const EMAILS_TABLE     = 'test_emails';
    public const CATEGORIES_TABLE = 'test_categories';

    public function _before(AcceptanceTester $I): void
    {
        $I->login(self::ADMIN_USER, self::ADMIN_PASSWORD);
        $this->populateCategoriesTable($I);
        $this->populateEmailsTable($I);
    }

    public function tryToBatchChangeEmailCategory(AcceptanceTester $I)
    {
        $I->amOnPage(EmailsPage::$URL);
        $I->waitForElementClickable(EmailsPage::$selectAllCheckbox);
        $I->click(EmailsPage::$selectAllCheckbox);

        $I->waitForElementClickable(EmailsPage::$selectedActionsDropdown);
        $I->click(EmailsPage::$selectedActionsDropdown);

        $I->waitForElementClickable(EmailsPage::$changeCategoryAction);
        $I->click(EmailsPage::$changeCategoryAction);

        $I->waitForElementClickable(EmailsPage::$newCategoryDropdown);
        $I->click(EmailsPage::$newCategoryDropdown);

        // Click the dropdown menu
        $I->click('#email_batch_newCategory_chosen > a > span');
        $I->waitForElementVisible('#email_batch_newCategory_chosen > div > ul > li.active-result');
    }

    protected function populateEmailsTable(AcceptanceTester $I): void
    {
        $oldCategory = $I->grabFromDatabase(self::CATEGORIES_TABLE,
            'id',
            [
                'title' => self::CATEGORY_PREFIX.'1',
            ]
        );
        for ($i = 0; $i < 3; ++$i) {
            $I->haveInDatabase(self::EMAILS_TABLE, [
                'is_published'       => 1,
                'name'               => self::EMAIL_PREFIX.$i + 1,
                'read_count'         => 0,
                'sent_count'         => 0,
                'variant_sent_count' => 0,
                'variant_read_count' => 0,
                'lang'               => 'en',
                'headers'            => '',
                'revision'           => 1,
                'category_id'        => $oldCategory,
            ]);
        }
    }

    private function populateCategoriesTable(AcceptanceTester $I): void
    {
        for ($i = 0; $i < 3; ++$i) {
            $I->haveInDatabase(self::CATEGORIES_TABLE, [
                'is_published' => 1,
                'title'        => self::CATEGORY_PREFIX.$i + 1,
                'alias'        => self::CATEGORY_PREFIX.($i + 1).' alias',
                'bundle'       => 'email',
            ]);
        }
    }
}
