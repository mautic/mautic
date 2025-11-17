<?php

declare(strict_types=1);

namespace Step\Acceptance;

use Page\Acceptance\MenuPage;

class MenuStep extends \AcceptanceTester
{
    public function loginAsAdmin($I): void
    {
        $I->login(MenuPage::$ADMIN_USER, MenuPage::$ADMIN_PASSWORD);
        $I->amOnPage(MenuPage::$URL);
    }

    public function navigateToManageGroups(): void
    {
        $I = $this;
        $I->click(MenuPage::$POINTS);
        $I->waitForElementClickable(MenuPage::$MANAGE_GROUPS_ID, 10);
        $I->click(MenuPage::$MANAGE_GROUPS);
        $I->waitForElementVisible(MenuPage::$ACTIVE_NAV_GROUP, 10);
        $I->seeElement(MenuPage::$ACTIVE_NAV_GROUP);
    }
}
