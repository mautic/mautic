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
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Grab contact name
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]');
        $I->click(['link' => $contactName]);
        $I->see($contactName); // Confirm we're on the contact view page
        $I->click('//*[@id="toolbar"]/div[1]/a[1]'); // Click on edit button
        $I->waitForElement('//*[@id="core"]/div[1]/h4', 2); // Wait for the form to show (in secs)
        $I->see("Edit $contactName"); // Confirm we're on the edit contact page
        $I->fillField('//*[@id="lead_firstname"]', 'Test-First-Name'); // Set the first name to Test-First-Name
        $I->click('//*[@id="lead_buttons_apply_toolbar"]'); // Click on the Apply button
        $I->wait(5); // Wait for apply action to complete
        $I->fillField('//*[@id="lead_lastname"]', 'Test-Last-Name'); // Set the last name to Test-Last-Name
        $I->wait(5); // Wait while the field is filled
        $I->click('//*[@id="lead_buttons_save_toolbar"]'); // Click on the Save & Close button
        $I->wait(5); // Wait for save to complete
        $I->see('Test-First-Name Test-Last-Name has been updated!'); // Confirm that the contact has been deleted
    }

    public function deleteContactFromList(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Get name of first contact so we can check for it after delete
        $I->see($contactName); // Check we can see first contact name
        $I->click('#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > button'); // Click on dropdown caret on first contact
        $I->waitForElementVisible('#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > ul > li:nth-child(3) > a', 5); // Wait for the dropdown menu to show
        $I->click('#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > ul > li:nth-child(3) > a'); // Click on the delete menu option
        $I->wait(5); // Wait for the modal to show
        $I->waitForElementVisible('button.btn.btn-danger', 5); // We have to wait for the modal to become visible
        $I->click('button.btn.btn-danger'); //Now the modal is visible, click on the button to confirm delete
        $I->wait(5); // Wait for delete to be completed
        $I->see("$contactName has been deleted!"); // Confirm the contact is deleted
    }
}
