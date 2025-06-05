<?php

declare(strict_types=1);

namespace Acceptance;

use Step\Acceptance\DashboardStep;

final class DashboardWidgetsCest
{
    public function _before(\AcceptanceTester $I, DashboardStep $DashboardStep): void
    {
        $DashboardStep->loginAsAdmin($I);
    }

    public function verifyWidgets(DashboardStep $DashboardStep): void
    {
        $DashboardStep->createContactsCounterWidget();
        $DashboardStep->createDncWidget();
        $DashboardStep->createContactsCounterPerSegmentWidget();
    }
}
