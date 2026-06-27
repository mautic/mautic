<?php

declare(strict_types=1);

use Step\Acceptance\MenuStep;

final class MenuNavigationCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function ensureManageGroupsHighlights(MenuStep $menuStep): void
    {
        $menuStep->navigateToManageGroups();
    }
}
