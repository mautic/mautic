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
        $I->waitForElementVisible('#column-config-modal', 1);
        $I->makeScreenshot('no-preferences-saved');

        $I->see('Select Columns');

        $I->waitForElementVisible('#col-email', 3);
        $emailCheckboxState = $I->executeJS('return document.querySelector("#col-email").checked');
        $I->click('#col-name');
        $I->click('#col-website');
        $I->click('#col-score');
        $I->click('#col-contacts');
        $I->click('#col-id');
        $I->wait(0.5);
        $I->makeScreenshot('preferences-selected');

        $I->click('#save-columns');
        $I->waitForElementNotVisible('#column-config-modal', 5);

        $I->dontSeeElement('th.col-company-email');       // header
        $I->dontSeeElement('td.col-company-email');       // datacell
        $I->makeScreenshot('preferences-applied');

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
}
