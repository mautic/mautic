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

        // Wait for and click on the "Quick Add" button
        $I->waitForElementClickable('#toolbar .quickadd', 30);
        $I->click('#toolbar .quickadd');

        // Wait for the Quick Add modal to appear and verify its presence
        $I->waitForElementVisible('#MauticSharedModal-label', 30);
        $I->see('Quick Add', 'h4.modal-title');

        // Wait for the form fields to be visible
        $I->waitForElementVisible('#lead_firstname', 10);

        // Fill out the quick add form
        $I->fillField('#lead_firstname', 'QuickAddFirstName');
        $I->fillField('#lead_lastname', 'QuickAddLastName');
        $I->fillField('#lead_email', 'quickadd@example.com');
        $I->fillField('#lead_tags_chosen input', 'TestTag');
        $I->pressKey('#lead_tags_chosen input', \Facebook\WebDriver\WebDriverKeys::ENTER);
        $I->fillField('#lead_companies_chosen input', 'Mautic');
        $I->pressKey('#lead_companies_chosen input', \Facebook\WebDriver\WebDriverKeys::ENTER);

        // Wait for the save button to be clickable and submit the form
        $I->waitForElementClickable('#MauticSharedModal > div > div > div.modal-footer > div > button.btn.btn-default.btn-save.btn-copy', 30);
        $I->click('#MauticSharedModal > div > div > div.modal-footer > div > button.btn.btn-default.btn-save.btn-copy');
        $I->waitForElementNotVisible('#MauticSharedModal-label', 30);

        $I->amOnPage('/s/contacts');
        $I->reloadPage(); // Ensure the latest data is loaded

        // Search for the contact we just created
        $I->fillField('#list-search', 'QuickAddFirstName');
        $I->pressKey('#list-search', \Facebook\WebDriver\WebDriverKeys::ENTER);
        $I->waitForElementVisible('#leadTable', 10); // Wait for the search results to appear

        // Verify the contact is in the list
        $I->see('QuickAddFirstName', '#leadTable');

        // Clear the search
        $I->click('#btn-filter');
    }

    public function createContactFromForm(AcceptanceTester $I)
    {
        // Navigate to the contacts page
        $I->amOnPage('/s/contacts');

        // Click on "+New" button
        $I->waitForElementClickable('#toolbar a:nth-child(2)', 30);
        $I->click('#toolbar a:nth-child(2)');
        $I->waitForText('New Contact', 30);

        // Fill out the form fields
        $I->waitForElementVisible('#lead_firstname', 10);
        $I->fillField('#lead_firstname', 'FirstName');
        $I->fillField('#lead_lastname', 'LastName');
        $I->fillField('#lead_email', 'email@example.com');

        // Fill Company field
        // $I->click('#lead_companies_chosen');
        // $I->fillField('#lead_companies_chosen > ul > li > input', 'TestCompany');
        // $I->wait(2);
        // $I->see('No matches found TestCompany');
        // $I->fillField('#lead_companies_chosen > ul > li > input', 'Create');

        $I->fillField('#lead_tags_chosen input', 'TestTag');
        $I->pressKey('#lead_tags_chosen input', \Facebook\WebDriver\WebDriverKeys::ENTER);

        // Scroll back to the top of the page
        $I->executeJS('window.scrollTo(0, 0);');

        // Click the save and close button
        $I->waitForElementClickable('#lead_buttons_save_toolbar', 30);
        $I->click('#lead_buttons_save_toolbar');

        // Wait for the contact details page to load
        $I->waitForElementVisible('.page-header-title .span-block', 30);
        $I->see('FirstName LastName', '.page-header-title .span-block');

        // Click the close button on the contact details page
        $I->waitForElementClickable('#toolbar > div.std-toolbar.btn-group > a:nth-child(3)', 30);
        $I->click('a.btn.btn-default[href="/s/contacts"]');

        // Verify that we are back on the contacts page
        //$I->waitForElementVisible('h1.page-header-title', 30);
        //$I->see('Contacts', 'h1.page-header-title');
        //$I->makeScreenshot('after-contacts-check');
    }

    public function viewContact(AcceptanceTester $I)
    {
        // Go to contacts list
        $I->amOnPage('/s/contacts');

        // Grab the name of the first contact in the list
        $contactName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]');

        // Click on the first contact in the list
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a');

        $I->wait(2);

        // Verify that the contact name is displayed correctly on the contact details page
        $I->see($contactName);
    }
}
