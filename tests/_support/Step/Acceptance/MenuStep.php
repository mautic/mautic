<?php

declare(strict_types=1);

namespace Step\Acceptance;

use Page\Acceptance\MenuPage;

class MenuStep extends \AcceptanceTester
{
    public function navigateToManageGroups(): void
    {
        $I = $this;
        $I->amOnPage(MenuPage::$URL);
        $I->waitForElementClickable(MenuPage::$POINTS, self::TIMEOUT);
        $I->click(MenuPage::$POINTS);
        $I->waitForElementClickable(MenuPage::$MANAGE_GROUPS_ID, self::TIMEOUT);
        $I->click(MenuPage::$MANAGE_GROUPS_ID);
        $I->waitForElementVisible(MenuPage::$ACTIVE_NAV_GROUP, self::TIMEOUT);
        $I->seeElement(MenuPage::$ACTIVE_NAV_GROUP);
    }
}
