<?php

namespace Step\Acceptance;

use Page\Acceptance\CategoriesPage;

class CategoryStep extends \AcceptanceTester
{
    public function createACategory(string $name): void
    {
        $this->amOnPage(CategoriesPage::$URL);
        $this->waitForElementClickable(CategoriesPage::$NEW_BUTTON);
        $this->click(CategoriesPage::$NEW_BUTTON);
        $this->waitForElementClickable(CategoriesPage::$BUNDLE_DROPDOWN);
        $this->click(CategoriesPage::$BUNDLE_DROPDOWN);
        $this->waitForElementClickable(CategoriesPage::$BUNDLE_EMAIL_OPTION);
        $this->click(CategoriesPage::$BUNDLE_EMAIL_OPTION);
        $this->fillField(CategoriesPage::$TITLE_FIELD, $name);
        $this->waitForElementClickable(CategoriesPage::$SAVE_AND_CLOSE);
        $this->click(CategoriesPage::$SAVE_AND_CLOSE);
    }
}
