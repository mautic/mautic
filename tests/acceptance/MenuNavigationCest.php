<?php

declare(strict_types=1);

namespace Acceptance;

use Step\Acceptance\MenuStep;

final class MenuNavigationCest
{
    public function _before(\AcceptanceTester $I, MenuStep $menuStep): void
    {
        $menuStep->loginAsAdmin($I);
    }

    public function ensureManageGroupsHighlights(MenuStep $menuStep): void
    {
        $menuStep->navigateToManageGroups();
    }
}
