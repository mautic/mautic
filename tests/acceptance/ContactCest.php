<?php

class ContactCest
{
    public function _before(\Step\Acceptance\Login $I)
    {
        $I->loginAsUser(); // Check for existing session or log in
        $I->amOnPage('/s/dashboard'); // Go to dashboard
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function viewContact(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts'); //Go to contacts list
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Grab the name of the first contact in the list
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]');
        $I->click(['link' => $contactName]); // Click on the first contact in the list
        $I->see($contactName); // Check we see the expected name
        $I->makeScreenshot('view-contact'); // Take a screenshot of the contact for confirmation
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
        $I->wait(5);
        $I->fillField('//*[@id="lead_lastname"]', 'Test-Last-Name');
        $I->wait(5);
        $I->click('//*[@id="lead_buttons_save_toolbar"]');
        $I->wait(5);
        $I->see('Test-First-Name Test-Last-Name has been updated!');
    }

    public function deleteContact(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Get name of first contact so we can check for it after delete
        $I->see($contactName); // Check we can see first contact name
        $I->click('#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > button'); // Click on dropdown caret on first contact
        $I->waitForElementVisible('#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > ul > li:nth-child(3) > a', 60); // Wait for the dropdown menu to show
        $I->click('#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > ul > li:nth-child(3) > a');
        $I->wait(5);
        $I->makeScreenshot('delete-modal-showing');
        $I->waitForElementVisible('body > div.modal.fade.confirmation-modal.in > div > div > div.modal-body.text-center > button.btn.btn-danger', 5);
        $I->click('body > div.modal.fade.confirmation-modal.in > div > div > div.modal-body.text-center > button.btn.btn-danger');
        $I->wait(5);
        $I->see("$contactName has been deleted!");
        $I->makeScreenshot('contact-deleted');
    }
}
