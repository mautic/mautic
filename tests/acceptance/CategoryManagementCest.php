<?php

declare(strict_types=1);

use Page\Acceptance\CategoriesPage;
use Step\Acceptance\CategoryStep;

class CategoryManagementCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function createCategory(AcceptanceTester $I, CategoryStep $categoryStep): void
    {
        $categoryName = sprintf('E2E Category %s', str_replace('.', '', uniqid('', true)));

        $categoryStep->createACategory($categoryName);

        $I->seeNotificationAppear("{$categoryName} has been created!");

        $I->waitForText($categoryName, AcceptanceTester::TIMEOUT, CategoriesPage::CATEGORY_TABLE);
    }
}
