<?php

declare(strict_types=1);

namespace Step\Acceptance;

use Page\Acceptance\MenuPage;

final class MenuStep extends \AcceptanceTester
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
        $I->waitForElementClickable(MenuPage::$MANAGE_GROUPS_ID, self::TIMEOUT);
        $I->click(MenuPage::$MANAGE_GROUPS);
        $I->waitForElementVisible(MenuPage::$ACTIVE_NAV_GROUP, self::TIMEOUT);
        $I->seeElement(MenuPage::$ACTIVE_NAV_GROUP);
    }
}
