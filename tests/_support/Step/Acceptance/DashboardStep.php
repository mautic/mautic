<?php

declare(strict_types=1);

namespace Step\Acceptance;

use Page\Acceptance\DashboardWidgets as Dw;

class DashboardStep extends \AcceptanceTester
{
    public function loginAsAdmin($I): void
    {
        $I->login(Dw::$ADMIN_USER, Dw::$ADMIN_PASSWORD);
        $I->amOnPage(Dw::$URL);
    }

    private function createWidget(string $widgetType): void
    {
        $I = $this;
        $I->waitForElementVisible(Dw::$TOOLBAR, 10);
        $I->click(Dw::$ADD_WIDGET, Dw::$TOOLBAR);
        $I->waitForElementVisible(Dw::$FORM, 10);
        $I->waitForElementVisible(Dw::$WIDGET_TYPE, 10);
        $I->waitForElementVisible(Dw::$WIDGET_TYPE_CHOSEN, 10);
        $I->click(Dw::$WIDGET_TYPE_CHOSEN);
        $I->click(Dw::$WIDGET_TYPE_LIST.'[contains(text(), "'.$widgetType.'")]');
        $I->waitForElementVisible(Dw::$SAVE_BUTTON_TEXT, 10);
        $I->executeJS("document.querySelector('".Dw::$SAVE_BUTTON."').click();");
    }

    public function createContactsCounterWidget(): void
    {
        $I = $this;
        $I->createWidget(Dw::$CONTACTS_COUNTER);
        $I->waitForElementVisible('//h4[contains(text(), "'.Dw::$CONTACTS_COUNTER.'")]', 10);
        $I->see(Dw::$CONTACTS_COUNTER);
    }

    public function createDncWidget(): void
    {
        $I = $this;
        $I->createWidget(Dw::$DNC_COUNTER);
        $I->waitForElementVisible('//h4[contains(text(), "'.Dw::$DNC_COUNTER.'")]', 10);
        $I->see(Dw::$DNC_COUNTER);
    }

    public function createContactsCounterPerSegmentWidget(): void
    {
        $I = $this;
        $I->createWidget(Dw::$CONTACTS_PER_SEGMENT);
        $I->waitForElementVisible('//h4[contains(text(), "'.Dw::$CONTACTS_PER_SEGMENT.'")]', 10);
        $I->see(Dw::$CONTACTS_PER_SEGMENT);
    }
}
