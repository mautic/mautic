<?php

/**
 * Verifies column preference toggling
 * NOTE: Requires clean companies table to properly verify visibility changes.
 */
class ColumnPreferencesCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/s/login');
        $I->fillField('#username', 'admin');
        $I->fillField('#password', 'Maut1cR0cks!');
        $I->click('button[type=submit]');
        $I->see('Dashboard');
    }

    public function testUserCanSetCompanyColumnPreferences(AcceptanceTester $I)
    {
        $I->amOnPage('/s/companies');
        $I->see('Companies');

        $I->click('New');
        $I->waitForText('New Company', 1);
        $I->fillField('#company_companyname', 'Test Company '.rand(1000, 9999));
        $I->fillField('#company_companyemail', 'test@example.com');
        $I->click('Save & Close');
        $I->waitForText('Companies', 1);

        $I->reloadPage();
        $I->makeScreenshot('default-columns');
        $I->click('#column-config-toggle');
        $I->makeScreenshot('after-toggle-click');
        $I->waitForElementVisible('#column-config-modal', 5);
        $I->makeScreenshot('no-preferences-saved');

        $I->waitForElementVisible('#col-name', 5);
        $I->waitForElementClickable('#col-name', 3);

        $this->toggleColumn($I, 'name');
        $this->toggleColumn($I, 'website');
        $this->toggleColumn($I, 'score');
        $this->toggleColumn($I, 'contacts');
        $this->toggleColumn($I, 'id');

        $I->makeScreenshot('preferences-selected');

        $I->click('#save-columns');
        $I->waitForElementNotVisible('#column-config-modal', 5);
        $I->waitForElementVisible('th.col-company-name', 3);

        $I->seeElement('th.col-company-name');
        $I->seeElement('td.col-company-name');
        $I->seeElement('th.col-company-website');
        $I->seeElement('td.col-company-website');
        $I->seeElement('th.col-company-score');
        $I->seeElement('td.col-company-score');
        $I->seeElement('th.col-company-contacts');
        $I->seeElement('td.col-company-contacts');
        $I->seeElement('th.col-company-id');
        $I->seeElement('td.col-company-id');
    }

    private function toggleColumn(AcceptanceTester $I, string $column): void
    {
        $selector = "#col-$column";
        $I->waitForElementClickable($selector, 3);
        $initialState = $I->executeJS("return document.querySelector('$selector').checked");
        $I->click($selector);
        $I->waitForJS("return document.querySelector('$selector').checked == ".(!$initialState), 3);
    }
}
