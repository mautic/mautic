<?php

class ContactManagementCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->login('admin', 'Maut1cR0cks!');
    }

    public function createContactFromQuickAdd(AcceptanceTester $I)
    {
        // Navigate to the contacts page
        $I->amOnPage('/s/contacts');
        $I->waitForElement('#toolbar > div.std-toolbar.btn-group > a.btn.btn-default.btn-nospin.quickadd > span > span', 10); // Wait for Quick Add button

        // Click on "Quick Add" button
        $I->click('#toolbar > div.std-toolbar.btn-group > a.btn.btn-default.btn-nospin.quickadd > span > span');
        $I->wait(2);
        $I->see('Quick Add', 'h4.modal-title');

        // Fill out the quick add form
        $I->fillField('#lead_firstname', 'QuickAddFirstName');
        $I->fillField('#lead_lastname', 'QuickAddLastName');
        $I->fillField('#lead_email', 'quickadd@example.com');
        $I->fillField(' #lead_tags_chosen > ul > li > input', 'TestTag');
        $I->pressKey('#lead_tags_chosen > ul > li > input', \Facebook\WebDriver\WebDriverKeys::ENTER);
        $I->fillField('#lead_companies_chosen > ul > li > input', 'Mautic');
        $I->pressKey('#lead_companies_chosen > ul > li > input', \Facebook\WebDriver\WebDriverKeys::ENTER);

        // Submit the form
        $I->click('#MauticSharedModal > div > div > div.modal-footer > div > button.btn.btn-default.btn-save.btn-copy');

        $I->amOnPage('/s/contacts');
        $I->reloadPage(); // reload the page to ensure we have the latest data

        // Search for the contact we just created
        $I->fillField('#list-search', 'QuickAddFirstName'); // Search for the contact by first name

        // Option 1: Press Enter to execute the search
        $I->pressKey('#list-search', \Facebook\WebDriver\WebDriverKeys::ENTER); // Press the enter key to execute the search
        $I->wait(2); // Wait for the search results to appear

        // Option 2: Click the search button to execute the search
        // $I->click('#btn-filter'); // Click the search button
        // $I->wait(2); // Wait for the search results to appear

        // Look for our test contact in the contact list
        $I->see('QuickAddFirstName', '#leadTable');

        // Clear search
        $I->click('#btn-filter');
    }

    public function createContactFromForm(AcceptanceTester $I)
    {
        // Navigate to the contacts page
        $I->amOnPage('/s/contacts');

        // Click on "+New" button
        $I->click('#toolbar > div.std-toolbar.btn-group > a:nth-child(2)');
        $I->wait(2);
        $I->see('New Contact');
        
        // Fill out the form fields
        $I->fillField('#lead_firstname', 'FirstName');
        $I->fillField('#lead_lastname', 'LastName');
        $I->fillField('#lead_email', 'email@example.com');
        
        // Fill Company field
        // $I->click('#lead_companies_chosen');
        // $I->fillField('#lead_companies_chosen > ul > li > input', 'TestCompany');
        // $I->wait(2);
        // $I->see('No matches found TestCompany');
        // $I->fillField('#lead_companies_chosen > ul > li > input', 'Create');
        
        // Fill tag field
        $I->fillField('#lead_tags_chosen > ul > li > input', 'TestTag');
        $I->pressKey('#lead_tags_chosen > ul > li > input', \Facebook\WebDriver\WebDriverKeys::ENTER);

        // Scroll back to the top of the page
        $I->executeJS('window.scrollTo(0, 0);');
    
        // Click the save and close button
        $I->click('#lead_buttons_save_toolbar');
        $I->wait(2);
    
        // Verify that the contact details page is loaded by checking for the presence of the contact's name
        $I->see('FirstName LastName');

        // Click the close button on the contact details page
        $I->click('#toolbar > div.std-toolbar.btn-group > a:nth-child(3)');

        $I->seeInCurrentUrl('/s/contacts');
    }
}

