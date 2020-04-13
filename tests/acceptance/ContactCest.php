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

    public function createContactFromQuickAdd(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $I->click('//*[@id="toolbar"]/div[1]/a[1]');
        $I->wait(1);
        $I->fillField('//*[@id="lead_firstname"]', 'Test First Name');
        $I->wait(1);
        $I->fillField('//*[@id="lead_lastname"]', 'Test Last Name');
        $I->wait(1);
        $I->fillField('//*[@id="lead_email"]', 'test@example.com');
        $I->wait(1);
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[2]');
        $I->amOnPage('/s/contacts');
        $I->reloadPage();
        $I->see('Test First Name Test Last Name');
    }

    public function createContactFromForm(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $I->click('//*[@id="toolbar"]/div[1]/a[2]');
        $I->wait(2);
        $I->fillField('//*[@id="lead_firstname"]', 'Test 2 First Name');
        $I->fillField('//*[@id="lead_lastname"]', 'Test 2 Last Name');
        $I->fillField('//*[@id="lead_email"]', 'test2@example.com');

        $I->click(['css' => 'div#lead_companies_chosen']); // Clicking the company select dropdown
        $I->fillField(['xpath' => '//*[@id="lead_companies_chosen"]/ul/li/input'], 'Amazon'); // Searching the desired company
        $I->wait(1); // We need to wait for it to look up the companies
        $I->click(['xpath' => '//div[@id="lead_companies_chosen"]/div/ul/li[1]']); // Click the search result

        $I->fillField('//*[@id="lead_position"]', 'Owner');
        $I->fillField('//*[@id="lead_address1"]', 'Address 1');
        $I->fillField('//*[@id="lead_address2"]', 'Address 2');
        $I->fillField('//*[@id="lead_city"]', 'City');

        $I->click(['css' => 'div#lead_state_chosen']); // Clicking the dropdown
        $I->fillField(['xpath' => '//div[@id="lead_state_chosen"]/div/div/input'], 'California'); // Searching the desired state
        $I->click(['xpath' => '//div[@id="lead_state_chosen"]/div/ul/li[2]']); // Click the search result
        $I->fillField('//*[@id="lead_zipcode"]', 'CA 12345');

        $I->click(['css' => 'div#lead_country_chosen']); // Clicking the dropdown
        $I->fillField(['xpath' => '//div[@id="lead_country_chosen"]/div/div/input'], 'United States'); // Searching the desired country
        $I->click(['xpath' => '//div[@id="lead_country_chosen"]/div/ul/li[1]']); // Click the search result

        $I->fillField('//*[@id="lead_mobile"]', '+12345678901');
        $I->fillField('//*[@id="lead_phone"]', '+21345678901');
        $I->fillField('//*[@id="lead_fax"]', '+31345678901');
        $I->fillField('//*[@id="lead_website"]', 'https://www.mautic.org');
        $I->fillField('//*[@id="lead_attribution"]', '1500');

        $today = date('yy-m-d h:m'); //Get today's date and use this for the date field
        $I->fillField('//*[@id="lead_attribution_date"]', $today);

        $I->click(['css' => 'div#lead_preferred_locale_chosen']); // Clicking the dropdown
        $I->fillField(['xpath' => '//div[@id="lead_preferred_locale_chosen"]/div/div/input'], 'United States'); // Searching the desired language
        $I->click(['xpath' => '//div[@id="lead_preferred_locale_chosen"]/div/ul/li[1]']); // Click the search result

        $I->click(['css' => 'div#lead_timezone_chosen']); // Clicking the timezone select dropdown
        $I->fillField(['xpath' => '//*[@id="lead_timezone_chosen"]/div/div/input'], 'Los Angeles'); // Searching the desired timezone
        $I->wait(1); // We need to wait for it to look up the timezone
        $I->click(['xpath' => '//div[@id="lead_timezone_chosen"]/div/ul/li[2]']); // Click the search result

        $I->click(['css' => 'div#lead_stage_chosen']); // Clicking the lead stage dropdown
        $I->fillField(['xpath' => '//*[@id="lead_stage_chosen"]/div/div/input'], 'Cold'); // Searching the desired lead stage
        $I->wait(1); // We need to wait for it to look up the stages
        $I->click(['xpath' => '//div[@id="lead_stage_chosen"]/div/ul/li[1]']); // Click the search result

       /* NOTE: Comment out this test until https://github.com/mautic/mautic/issues/8674 is resolved as it's failing

        $I->click(['css' => 'div#lead_owner_chosen']); // Clicking the lead owner select dropdown
        $I->wait(1);
        $I->makeScreenshot('lead-owner-select-active');
        $I->fillField(['xpath' => '//*[@id="lead_owner_chosen"]/div/div/input'], 'Sales'); // Searching the desired lead owner
        $I->click(['xpath' => '//*[@id="lead_owner_chosen"]/div/ul/li[1]']); // Click Sales User
        $I->wait(2);
        $I->makeScreenshot('lead-owner');*/

        $I->click(['css' => 'div#lead_tags_chosen']); // Clicking the lead stage dropdown
        $I->fillField(['xpath' => '//*[@id="lead_tags_chosen"]/ul/li/input'], 'Mautic'); // Searching the desired lead stage
        $I->wait(1); // We need to wait for it to look up the tags
        $I->pressKey('//*[@id="lead_tags_chosen"]/ul/li/input', WebDriverKeys::ENTER);
        $I->click(['css' => 'div#lead_tags_chosen']); // Clicking the lead stage dropdown
        $I->fillField(['xpath' => '//*[@id="lead_tags_chosen"]/ul/li/input'], 'Cold lead'); // Searching the desired lead stage
        $I->wait(1); // We need to wait for it to look up the tags
        $I->pressKey('//*[@id="lead_tags_chosen"]/ul/li/input', WebDriverKeys::ENTER);
    }

    public function viewContact(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts'); //Go to contacts list
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Grab the name of the first contact in the list
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]');
        $I->click(['link' => $contactName]); // Click on the first contact in the list
        $I->see($contactName); // Check we see the expected name
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

    public function deleteContactFromProfile(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Get name of first contact so we can check for it after delete
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]');
        $I->click(['link' => $contactName]); // Click on the first contact in the list
        $I->see($contactName); // Check we see the expected name
        $I->click('//*[@id="toolbar"]/div[1]/button'); // Click the dropdown caret to show delete option
        $I->waitForElementVisible('//*[@id="toolbar"]/div[1]/ul/li[5]/a/span/span', 2); // Wait for the dropdown to be displayed
        $I->click('//*[@id="toolbar"]/div[1]/ul/li[5]/a/span/span');
        $I->waitForElementVisible('button.btn.btn-danger', 5); // We have to wait for the modal to become visible
        $I->click('button.btn.btn-danger'); //Now the modal is visible, click on the button to confirm delete
        $I->wait(5); // Wait for delete to be completed
        $I->see("$contactName has been deleted!"); // Confirm the contact is deleted
    }
}
