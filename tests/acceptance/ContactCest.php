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

    // Tests for adding contacts

    public function createContactFromQuickAdd(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $I->click('//*[@id="toolbar"]/div[1]/a[1]');
        $I->wait(2);
        $I->fillField('//*[@id="lead_firstname"]', 'Test First Name');
        $I->fillField('//*[@id="lead_lastname"]', 'Test Last Name');
        $I->fillField('//*[@id="lead_email"]', 'test@example.com');
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[2]'); // Click to save the contact
        $I->amOnPage('/s/contacts');
        $I->reloadPage(); // reload the page to ensure we have the latest data
        $I->fillField('//*[@id="list-search"]', 'Test'); // Search for the contact we just created
        $I->pressKey('//*[@id="list-search"]', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
        $I->wait(2);
        $I->see('Test First Name', '//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Look for our test contact in the contact list
        $I->fillField('//*[@id="list-search"]', ''); // Clear search (can't use clearfield as not supported in this version of Codeception)
        $I->pressKey('//*[@id="list-search"]', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
    //    $I->wait(1);
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
        $I->wait(2);

        $I->click(['css' => 'div#lead_timezone_chosen']); // Clicking the timezone select dropdown
        $I->wait(2);
        $I->fillField(['xpath' => '//*[@id="lead_timezone_chosen"]/div/div/input'], 'Los Angeles'); // Searching the desired timezone
        $I->wait(2); // We need to wait for it to look up the timezone
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
        $I->pressKey('//*[@id="lead_tags_chosen"]/ul/li/input', WebDriverKeys::ENTER); // Press enter
        $I->click(['css' => 'div#lead_tags_chosen']); // Clicking the lead stage dropdown
        $I->fillField(['xpath' => '//*[@id="lead_tags_chosen"]/ul/li/input'], 'Cold lead'); // Searching the desired lead stage
        $I->wait(1); // We need to wait for it to look up the tags
        $I->pressKey('//*[@id="lead_tags_chosen"]/ul/li/input', WebDriverKeys::ENTER); // Press enter
    }

    // Tests for viewing contacts

    public function viewContact(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts'); //Go to contacts list
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Grab the name of the first contact in the list
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]');
        $I->click(['link' => $contactName]); // Click on the first contact in the list
        $I->see($contactName); // Check we see the expected name
    }

    // Tests for editing contacts

    public function editContact(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Grab contact name
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]');
        $I->click(['link' => $contactName]);
        $I->wait(2);
        $I->see($contactName); // Confirm we're on the contact view page
        $I->click('//*[@id="toolbar"]/div[1]/a[1]'); // Click on edit button
        $I->waitForElement('//*[@id="core"]/div[1]/h4', 5); // Wait for the form to show (in secs)
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

    // Tests for deleting contacts

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
        $I->wait(2); // Wait for delete to be completed
        $I->see("$contactName has been deleted!"); // Confirm the contact is deleted
    }

    public function deleteContactFromProfile(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Get name of first contact so we can check for it after delete
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]');
        $I->click(['link' => $contactName]); // Click on the first contact in the list
        $I->see($contactName); // Check we see the expected name
        $I->wait(2);
        $I->click('//*[@id="toolbar"]/div[1]/button'); // Click the dropdown caret to show delete option
        $I->waitForElementVisible('//*[@id="toolbar"]/div[1]/ul/li[5]/a/span/span', 5); // Wait for the dropdown to be displayed
        $I->click('//*[@id="toolbar"]/div[1]/ul/li[5]/a/span/span');
        $I->waitForElementVisible('button.btn.btn-danger', 5); // We have to wait for the modal to become visible
        $I->click('button.btn.btn-danger'); //Now the modal is visible, click on the button to confirm delete
        $I->wait(2); // Wait for delete to be completed
        $I->see("$contactName has been deleted!"); // Confirm the contact is deleted
    }

    // Tests for searching contacts

    public function searchContactsFullName(AcceptanceTester $I) //TODO Add more search tests to incorporate wildcards and other fields etc
    {
        $I->amOnPage('/s/contacts');
        $contactName1 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Get name of first contact
        $contactName2 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Get name of second contact
        $I->see("$contactName1", '//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // check first two names
        $I->see("$contactName2", '//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]');
        $I->fillField('//*[@id="list-search"]', "$contactName1"); // Search for first contact
        $I->wait(1);
        $I->see("$contactName1", '//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Check that we see the first contact in the list
        $I->dontSee("$contactName2", '//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Check that we don't see the second contact in the list (as we have filtered to see only the first)
        $I->fillField('//*[@id="list-search"]', ''); // Clear search (can't use clearfield as not supported in this version of Codeception)
        $I->pressKey('//*[@id="list-search"]', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
        $I->wait(1);
    }

    // Tests for batch contact changes

    public function batchAddToCampaign(AcceptanceTester $I) // NOTE Sample data does not include campaigns - see https://github.com/mautic/mautic/issues/8677. Create one first.
    {
        $I->amOnPage('/s/contacts');
        $contactName1 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Get name of first contact
        $contactName2 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Get name of second contact
        $I->amOnPage('/s/campaigns/view/1'); // Go to campaign
        $I->click('//*[@id="app-content"]/div/div[2]/div[1]/div[2]/ul/li[3]/a'); // Click contacts tab
        $I->dontsee("$contactName1", '//*[@id="leads-container"]/div[1]/div/div[2]/div/div/div[2]/div'); // Confirm first contact is not in the campaign
        $I->dontsee("$contactName2", '//*[@id="leads-container"]/div[1]/div/div[1]/div/div/div[2]/div'); // Confirm second contact is not in the campaign
        $I->amOnPage('/s/contacts');
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[1]/td[1]/div/span/input'); // Select contact 1
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[2]/td[1]/div/span/input'); // Select contact 2
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button'); // Click for options
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/ul/li[1]/a/span/span'); // Select campaigns
        $I->waitForElementVisible('//*[@id="lead_batch_add_chosen"]/ul/li/input', 5); // Wait for modal
        $I->click('//*[@id="lead_batch_add_chosen"]/ul/li/input'); // Click into add box
        $I->click('//*[@id="lead_batch_add_chosen"]/div/ul/li'); // Select campaign
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[2]'); // Save
        $I->amOnPage('/s/campaigns/view/1'); // Go to campaign
        $I->click('//*[@id="app-content"]/div/div[2]/div[1]/div[2]/ul/li[3]/a'); // Click contacts tab
        $I->wait(2);
        $I->see("$contactName1", '//*[@id="leads-container"]/div[1]/div/div[2]/div/div/div[2]/div'); // Confirm first contact is now in the campaign
        $I->see("$contactName2", '//*[@id="leads-container"]/div[1]/div/div[1]/div/div/div[2]/div'); // Confirm second contact is now in the campaign
    }

    public function batchRemoveFromCampaign(AcceptanceTester $I) // NOTE: Assumes previous test has run, and users are still in campaign.
    {
        $I->amOnPage('/s/contacts');
        $contactName1 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Get name of first contact
        $contactName2 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Get name of second contact
        $I->amOnPage('/s/campaigns/view/1'); // Go to campaign
        $I->click('//*[@id="app-content"]/div/div[2]/div[1]/div[2]/ul/li[3]/a'); // Click contacts tab
        $I->wait(1);
        $I->see("$contactName1", '//*[@id="leads-container"]/div[1]/div/div[2]/div/div/div[2]/div'); // Confirm first contact is not in the campaign
        $I->see("$contactName2", '//*[@id="leads-container"]/div[1]/div/div[1]/div/div/div[2]/div'); // Confirm second contact is not in the campaign
        $I->amOnPage('/s/contacts');
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[1]/td[1]/div/span/input'); // Select contact 1
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[2]/td[1]/div/span/input'); // Select contact 2
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button'); // Click for options
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/ul/li[1]/a/span/span'); // Select campaigns
        $I->waitForElementVisible('//*[@id="lead_batch_remove_chosen"]/ul/li/input', 5); // Wait for modal
        $I->click('//*[@id="lead_batch_remove_chosen"]/ul/li/input'); // Click into add box
        $I->click('//*[@id="lead_batch_remove_chosen"]/div/ul/li'); // Select campaign
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[2]'); // Save
        $I->amOnPage('/s/campaigns/view/1'); // Go to campaign
        $I->click('//*[@id="app-content"]/div/div[2]/div[1]/div[2]/ul/li[3]/a'); // Click contacts tab
        $I->wait(2);
        $I->dontsee("$contactName1", '//*[@id="leads-container"]/div[1]/div/div[2]/div/div/div[2]/div'); // Confirm first contact is now in the campaign
        $I->dontsee("$contactName2", '//*[@id="leads-container"]/div[1]/div/div[1]/div/div/div[2]/div'); // Confirm second contact is now in the campaign
    }

    public function batchAddToCategory(AcceptanceTester $I)

    // NOTE: You need to manually create a global or contact category as it is not included in demo data. This test assumes that the first two contacts do not have any categories assigned to start with.
    {
        $I->amOnPage('/s/contacts');
        $contactName1 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Get name of first contact
        $contactName2 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Get name of second contact

        // Check contact 1 does not have the category set

        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Click on first contact
        $I->wait(2);
        $I->click('//*[@id="toolbar"]/div[1]/button'); // Click dropdown to reveal preferences
        $I->waitForElementVisible('//*[@id="toolbar"]/div[1]/ul', 5); // Wait for dropdown to show
        $I->click('//*[@id="toolbar"]/div[1]/ul/li[4]/a'); // Click on preferences
        $I->waitForElementVisible('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a', 5); // Wait for modal to be displayed
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a'); // click on Categories tab
        $I->wait(1);
        $I->seeFieldIsEmpty($I->grabTextFrom('//*[@id="lead_contact_frequency_rules_global_categories_chosen"]/ul/li[1]')); // Check that the field is empty

        // Check contact 2 does not have the category set

        $I->amOnPage('/s/contacts');
        $I->click('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Click on second contact
        $I->wait(2);
        $I->click('//*[@id="toolbar"]/div[1]/button'); // Click dropdown to reveal preferences
        $I->waitForElementVisible('//*[@id="toolbar"]/div[1]/ul', 5); // Wait for dropdown to show
        $I->click('//*[@id="toolbar"]/div[1]/ul/li[4]/a'); // Click on preferences
        $I->waitForElementVisible('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a', 5); // Wait for modal to be displayed
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a'); // click on Categories tab
        $I->wait(1);
        $I->seeFieldIsEmpty($I->grabTextFrom('//*[@id="lead_contact_frequency_rules_global_categories_chosen"]/ul/li[1]')); // Check that the field is empty

        // Batch add both contacts to category
        $I->amOnPage('/s/contacts');
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[1]/td[1]/div/span/input'); // Select contact 1
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[2]/td[1]/div/span/input'); // Select contact 2
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button'); // Click for options
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/ul/li[2]/a/span/span'); // Select categories
        $I->waitForElementVisible('//*[@id="lead_batch_add_chosen"]', 5); // Wait for modal
        $I->click('//*[@id="lead_batch_add_chosen"]/ul/li/input'); // Click into add box
        $I->click('//*[@id="lead_batch_add_chosen"]/div/ul/li'); // Select category
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[2]'); // Save

       // Check contact 1 has the category set

        $I->amOnPage('/s/contacts');
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Click on first contact
        $I->wait(2);
        $I->click('//*[@id="toolbar"]/div[1]/button'); // Click dropdown to reveal preferences
        $I->waitForElementVisible('//*[@id="toolbar"]/div[1]/ul', 5); // Wait for dropdown to show
        $I->click('//*[@id="toolbar"]/div[1]/ul/li[4]/a'); // Click on preferences
        $I->waitForElementVisible('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a', 5); // Wait for modal to be displayed
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a'); // click on Categories tab
        $I->wait(1);
        $I->seeFieldIsNotEmpty($I->grabTextFrom('//*[@id="lead_contact_frequency_rules_global_categories_chosen"]/ul/li[1]')); // Check that the field is not empty

        // Check contact 2 has the category set

        $I->amOnPage('/s/contacts');
        $I->click('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Click on second contact
        $I->wait(2);
        $I->click('//*[@id="toolbar"]/div[1]/button'); // Click dropdown to reveal preferences
        $I->waitForElementVisible('//*[@id="toolbar"]/div[1]/ul', 5); // Wait for dropdown to show
        $I->click('//*[@id="toolbar"]/div[1]/ul/li[4]/a'); // Click on preferences
        $I->waitForElementVisible('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a', 5); // Wait for modal to be displayed
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a'); // click on Categories tab
        $I->wait(1);
        $I->seeFieldIsNotEmpty($I->grabTextFrom('//*[@id="lead_contact_frequency_rules_global_categories_chosen"]/ul/li[1]')); // Check that the field is empty
    }

    public function batchRemoveCategory(AcceptanceTester $I)

    // NOTE: This assumes the previous test has run successfully and the first two contacts have a category set.
    {
        $I->amOnPage('/s/contacts');

        // Check contact 1 has the category set

        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Click on first contact
        $I->wait(2);
        $I->click('//*[@id="toolbar"]/div[1]/button'); // Click dropdown to reveal preferences
        $I->waitForElementVisible('//*[@id="toolbar"]/div[1]/ul', 5); // Wait for dropdown to show
        $I->click('//*[@id="toolbar"]/div[1]/ul/li[4]/a'); // Click on preferences
        $I->waitForElementVisible('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a', 5); // Wait for modal to be displayed
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a'); // click on Categories tab
        $I->wait(1);
        $I->seeFieldIsNotEmpty($I->grabTextFrom('//*[@id="lead_contact_frequency_rules_global_categories_chosen"]/ul/li[1]')); // Check that the field is not empty

        // Check contact 2 has the category set

        $I->amOnPage('/s/contacts');
        $I->click('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Click on second contact
        $I->wait(2);
        $I->click('//*[@id="toolbar"]/div[1]/button'); // Click dropdown to reveal preferences
        $I->waitForElementVisible('//*[@id="toolbar"]/div[1]/ul', 5); // Wait for dropdown to show
        $I->click('//*[@id="toolbar"]/div[1]/ul/li[4]/a'); // Click on preferences
        $I->waitForElementVisible('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a', 5); // Wait for modal to be displayed
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a'); // click on Categories tab
        $I->wait(1);
        $I->seeFieldIsNotEmpty($I->grabTextFrom('//*[@id="lead_contact_frequency_rules_global_categories_chosen"]/ul/li[1]')); // Check that the field is not empty

        // Batch remove both contacts from category
        $I->amOnPage('/s/contacts');
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[1]/td[1]/div/span/input'); // Select contact 1
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[2]/td[1]/div/span/input'); // Select contact 2
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button'); // Click for options
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/ul/li[2]/a/span/span'); // Select categories
        $I->waitForElementVisible('//*[@id="lead_batch_add_chosen"]', 5); // Wait for modal
        $I->click('//*[@id="lead_batch_remove_chosen"]'); // Click into remove box
        $I->click('//*[@id="lead_batch_remove_chosen"]/div/ul/li'); // Select category
        $I->wait(1);
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[2]'); // Save

        // Check contact 1 does not have a category set

        $I->amOnPage('/s/contacts');
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Click on first contact
        $I->wait(2);
        $I->click('//*[@id="toolbar"]/div[1]/button'); // Click dropdown to reveal preferences
        $I->waitForElementVisible('//*[@id="toolbar"]/div[1]/ul', 5); // Wait for dropdown to show
        $I->click('//*[@id="toolbar"]/div[1]/ul/li[4]/a'); // Click on preferences
        $I->waitForElementVisible('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a', 5); // Wait for modal to be displayed
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a'); // click on Categories tab
        $I->wait(1);
        $I->seeFieldIsEmpty($I->grabTextFrom('//*[@id="lead_contact_frequency_rules_global_categories_chosen"]/ul/li[1]')); // Check that the field is empty

        // Check contact 2 does not have a category set

        $I->amOnPage('/s/contacts');
        $I->click('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Click on second contact
        $I->wait(2);
        $I->click('//*[@id="toolbar"]/div[1]/button'); // Click dropdown to reveal preferences
        $I->waitForElementVisible('//*[@id="toolbar"]/div[1]/ul', 5); // Wait for dropdown to show
        $I->click('//*[@id="toolbar"]/div[1]/ul/li[4]/a'); // Click on preferences
        $I->waitForElementVisible('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a', 5); // Wait for modal to be displayed
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[2]/div[2]/form/ul/li[2]/a'); // click on Categories tab
        $I->wait(1);
        $I->seeFieldIsEmpty($I->grabTextFrom('//*[@id="lead_contact_frequency_rules_global_categories_chosen"]/ul/li[1]')); // Check that the field is empty
    }

    public function batchChangeOwner(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');

        //Check contact 1 owner

        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Click on first contact
        $I->wait(2);
        $I->see('Sales User', '//*[@id="app-content"]/div[1]/div[2]/div[2]/div[1]/div[4]/p[1]'); // Confirm contact owned by Sales User

        //Check contact 2 owner

        $I->amOnPage('/s/contacts');
        $I->click('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Click on second contact
        $I->wait(2);
        $I->see('Sales User', '//*[@id="app-content"]/div[1]/div[2]/div[2]/div[1]/div[4]/p[1]'); // Confirm contact owned by Sales User

        // Batch change owner

        $I->amOnPage('/s/contacts');
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[1]/td[1]/div/span/input'); // Select contact 1
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[2]/td[1]/div/span/input'); // Select contact 2
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button'); // Click for options
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/ul/li[4]/a/span/span'); // Select owner
        $I->waitForElementVisible('//*[@id="lead_batch_owner_addowner_chosen"]', 5); // Wait for modal
        $I->click('//*[@id="lead_batch_owner_addowner_chosen"]'); // Click into select box
        $I->click('//*[@id="lead_batch_owner_addowner_chosen"]/div/ul/li[1]'); // Select Admin User
        $I->wait(1);
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[2]'); // Save

        $I->amOnPage('/s/contacts');

        //Check contact 1 owner

        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Click on first contact
        $I->wait(2);
        $I->see('Admin User', '//*[@id="app-content"]/div[1]/div[2]/div[2]/div[1]/div[4]/p[1]'); // Confirm contact owned by Admin User

        //Check contact 2 owner

        $I->amOnPage('/s/contacts');
        $I->click('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Click on second contact
        $I->wait(2);
        $I->see('Admin User', '//*[@id="app-content"]/div[1]/div[2]/div[2]/div[1]/div[4]/p[1]'); // Confirm contact owned by Admin User
    }

    public function batchAddSegment(AcceptanceTester $I)
    {
        // Check if our first two contacts are in the segment
        $I->amOnPage('/s/contacts');
        $contactName1 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Get name of first contact
        $contactName2 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Get name of second contact
        $I->fillField('//*[@id="list-search"]', 'segment:us'); // Search for contacts in the US segment
        $I->pressKey('//*[@id="list-search"]', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
        $I->wait(1);
        $I->dontsee("$contactName1"); // check first two names are not in the segment
        $I->dontsee("$contactName2");
        $I->fillField('//*[@id="list-search"]', ''); // Clear search (can't use clearfield as not supported in this version of Codeception)
        $I->pressKey('//*[@id="list-search"]', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
        $I->wait(1);

        // Batch add first two contacts to US segment
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[1]/td[1]/div/span/input'); // Select contact 1
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[2]/td[1]/div/span/input'); // Select contact 2
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button'); // Click for options
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/ul/li[5]/a/span/span'); // Select segments
        $I->wait(1);
        $I->click('//*[@id="lead_batch_add_chosen"]'); // Click into select box
        $I->click('//*[@id="lead_batch_add_chosen"]/div/ul/li'); // Select United States
        $I->wait(1);
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[2]'); // Save
        $I->wait(2);

        // Search for contacts in segment

        $I->fillField('//*[@id="list-search"]', 'segment:us'); // Search for contacts in the US segment
        $I->pressKey('//*[@id="list-search"]', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
        $I->wait(1);
        $I->see("$contactName1"); // check first two names are not in the segment
        $I->see("$contactName2");
        $I->fillField('//*[@id="list-search"]', ''); // Clear search (can't use clearfield as not supported in this version of Codeception)
        $I->pressKey('//*[@id="list-search"]', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
        $I->wait(1);
    }

    public function batchRemoveSegment(AcceptanceTester $I)
    // NOTE: Assumes previous test has been successful and contacts are already in the segment
    {
        // Check if our first two contacts are in the segment
        $I->amOnPage('/s/contacts');
        $contactName1 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Get name of first contact
        $contactName2 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Get name of second contact
        $I->fillField('//*[@id="list-search"]', 'segment:us'); // Search for contacts in the US segment
        $I->pressKey('//*[@id="list-search"]', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
        $I->wait(1);
        $I->see("$contactName1"); // check first two names are in the segment
        $I->see("$contactName2");
        $I->makeScreenshot('contacts-in-segment');
        $I->fillField('//*[@id="list-search"]', ''); // Clear search (can't use clearfield as not supported in this version of Codeception)
        $I->pressKey('//*[@id="list-search"]', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
        $I->wait(1);

        // Batch remove first two contacts to US segment
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[1]/td[1]/div/span/input'); // Select contact 1
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[2]/td[1]/div/span/input'); // Select contact 2
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button'); // Click for options
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/ul/li[5]/a/span/span'); // Select segments
        $I->wait(1);
        $I->click('//*[@id="lead_batch_remove_chosen"]'); // Click into select box
        $I->click('//*[@id="lead_batch_remove_chosen"]/div/ul/li'); // Select United States
        $I->wait(1);
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[2]'); // Save
        $I->wait(2);

        // Search for contacts in segment

        $I->fillField('//*[@id="list-search"]', 'segment:us'); // Search for contacts in the US segment
        $I->pressKey('//*[@id="list-search"]', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
        $I->wait(1);
        $I->dontsee("$contactName1"); // check first two names are not in the segment
        $I->dontsee("$contactName2");
        $I->fillField('//*[@id="list-search"]', ''); // Clear search (can't use clearfield as not supported in this version of Codeception)
        $I->pressKey('//*[@id="list-search"]', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
        $I->wait(1);
    }

    public function batchAddStage(AcceptanceTester $I)
    // NOTE: You will need to manually create a stage as none is included in sample data. It is not currently possible to batch remove stages via contacts.
    {
        // Check if our first two contacts have a stage set
        $I->amOnPage('/s/contacts');
        $I->seeFieldIsEmpty($I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[5]')); // Check that the stage field is empty
         $I->seeFieldIsEmpty($I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[2]/td[5]')); // Check that the stage field is empty

        // Batch add stage to first two contacts
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[1]/td[1]/div/span/input'); // Select contact 1
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[2]/td[1]/div/span/input'); // Select contact 2
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button'); // Click for options
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/ul/li[6]/a/span/span'); // Select stages
        $I->wait(1);
        $I->click('//*[@id="lead_batch_stage_addstage_chosen"]'); // Click into select box
        $I->click('//*[@id="lead_batch_stage_addstage_chosen"]/div/ul/li'); // Select Stage
        $I->wait(1);
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[2]'); // Save
        $I->wait(2);
        $I->reloadPage();
        $I->wait(1);
        $I->seeFieldIsNotEmpty($I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[5]/span')); // Check that the stage field is not empty
        $I->seeFieldIsNotEmpty($I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[5]/span')); // Check that the stage field is not empty
    }

    public function batchSetDNC(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        // Check our first two contacts don't have DNC set
        $I->dontSeeElement('#leadTable > tbody > tr:nth-child(1) > td:nth-child(2) > a > div.pull-right.label.label-danger');
        $I->dontSeeElement('#leadTable > tbody > tr:nth-child(2) > td:nth-child(2) > a > div.pull-right.label.label-danger');

        // Batch add DNC to first two contacts
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[1]/td[1]/div/span/input'); // Select contact 1
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[2]/td[1]/div/span/input'); // Select contact 2
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button'); // Click for options
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/ul/li[8]/a/span/span'); // Select DNC
        $I->wait(1);
        $I->fillField('//*[@id="lead_batch_dnc_reason"]', 'Test');
        $I->wait(1);
        $I->click('//*[@id="MauticSharedModal"]/div/div/div[3]/div/button[2]'); // Save
        $I->wait(2);
        $I->reloadPage();
        $I->wait(1);

        // Check our first two contacts now have DNC set
        $I->SeeElement('#leadTable > tbody > tr:nth-child(1) > td:nth-child(2) > a > div.pull-right.label.label-danger');
        $I->SeeElement('#leadTable > tbody > tr:nth-child(2) > td:nth-child(2) > a > div.pull-right.label.label-danger');
    }

    public function batchDeleteContacts(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $contactName1 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Get name of first contact so we can check for it after delete
        $contactName2 = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Get name of second contact so we can check for it after delete
        $I->see("$contactName1", '//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // check first contact
        $I->see("$contactName2", '//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // check second contact
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[1]/td[1]/div/span/input'); // Select contact 1
        $I->checkOption('//*[@id="leadTable"]/tbody/tr[2]/td[1]/div/span/input'); // Select contact 2
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button'); // Click for options
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/ul/li[10]/a/span/span'); // Select Delete
        $I->wait(5); // Wait for the modal to show
        $I->waitForElementVisible('button.btn.btn-danger', 5); // We have to wait for the modal to become visible
        $I->click('button.btn.btn-danger'); //Now the modal is visible, click on the button to confirm delete
        $I->wait(2); // Wait for delete to be completed
        $I->see("$contactName1", '//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]'); // Check we can see first contact name
        $I->see("$contactName2", '//*[@id="leadTable"]/tbody/tr[2]/td[2]/a/div[1]'); // Check we can see second contact name
    }
}
