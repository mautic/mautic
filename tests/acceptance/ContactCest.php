<?php

class ContactCest
{
    public function _before(\Step\Acceptance\Login $I)
    {
        $I->loginAsUser();
        $I->amOnPage('/s/dashboard');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function viewContact(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]');
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]');
        $I->click(['link' => $contactName]);
        $I->see($contactName);
    }

    public function editContact(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]');
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]');
        $I->click(['link' => $contactName]);
        $I->see($contactName);
        $I->click('//*[@id="toolbar"]/div[1]/a[1]'); // Click on edit button
        $I->waitForElement('//*[@id="core"]/div[1]/h4', 2); // Wait for the form to show (in secs)
        $I->see("Edit $contactName");
        $I->fillField('//*[@id="lead_firstname"]', 'Test-First-Name');
        $I->click('//*[@id="lead_buttons_apply_toolbar"]');
        $I->wait(1);
        $I->fillField('//*[@id="lead_lastname"]', 'Test-Last-Name');
        $I->wait(1);
        $I->click('//*[@id="lead_buttons_save_toolbar"]');
        $I->wait(1);
        $I->see('Test-First-Name Test-Last-Name has been updated!');
    }
}
