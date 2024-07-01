<?php

use Page\Acceptance\ContactPage;
use Step\Acceptance\Contact;

class ContactManagementCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->login('admin', 'Maut1cR0cks!');
    }

    public function createContactFromQuickAdd(
        AcceptanceTester $I,
        Contact $contact
    ) {
        $I->amOnPage(ContactPage::$URL);

        // Click on "Quick Add" button
        $I->waitForElementClickable(ContactPage::$quickAddButton, 30);
        $I->click(ContactPage::$quickAddButton);

        // Wait for the Quick Add Form to appear
        $I->waitForElementVisible(ContactPage::$quickAddModal, 30);
        $I->see('Quick Add', 'h4.modal-title');

        $contact->fillContactForm('QuickAddFirstName', 'QuickAddLastName', 'quickadd@example.com', 'TestTag');

        // Submit the form
        $I->waitForElementClickable(ContactPage::$saveButton, 30);
        $I->click(ContactPage::$saveButton);
        $I->waitForElementNotVisible(ContactPage::$quickAddModal, 30);

        $I->reloadPage(); // Ensure the latest data is loaded

        $I->seeInDatabase('leads', ['firstname' => 'QuickAddFirstName', 'email' => 'quickadd@example.com']);
    }

    public function createContactFromForm(
        AcceptanceTester $I,
        Contact $contact
    ) {
        $I->amOnPage('/s/contacts');

        // Click on "+New" button
        $I->waitForElementClickable(ContactPage::$newContactButton, 30);
        $I->click(ContactPage::$newContactButton);
        $I->waitForText('New Contact', 30);

        $contact->fillContactForm('FirstName', 'LastName', 'email@example.com', 'TestTag');

        // Scroll back to the top of the page
        $I->executeJS('window.scrollTo(0, 0);');

        // Click the save and close button
        $I->waitForElementClickable(ContactPage::$saveAndCloseButton, 30);
        $I->click(ContactPage::$saveAndCloseButton);

        $I->seeInDatabase('leads', ['firstname' => 'FirstName', 'email' => 'email@example.com']);
    }

    public function acessEditContactFormFromList(AcceptanceTester $I)
    {
        // Navigate to the contacts page
        $I->amOnPage('/s/contacts');

        // Grab the name of the first contact in the list
        $contactName = $I->grabTextFrom('#leadTable tbody tr:first-child td:nth-child(2) a div');

        // Check we can see the first contact name
        $I->see($contactName);

        // Click on the dropdown caret on the first contact
        $I->click('#leadTable tbody tr:first-child td:first-child div div button');

        // Wait for the dropdown menu to show and click the delete menu option
        $I->waitForElementClickable('#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > ul > li:nth-child(1) > a', 30);
        $I->click('#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > ul > li:nth-child(1) > a');

        // Wait for the edit form to be visible
        $I->waitForElementVisible('#core > div.pa-md.bg-light-xs.bdr-b > h4', 30);
        $I->see("Edit $contactName");

        // Close the edit form (No changes are made)
        $I->click('#lead_buttons_cancel_toolbar');
    }

    public function editContactFromProfile(AcceptanceTester $I)
    {
        // Navigate to the contacts page
        $I->amOnPage('/s/contacts');

        // Grab the name of the first contact in the list
        $contactName = $I->grabTextFrom('#leadTable > tbody > tr:nth-child(1) > td:nth-child(2) > a > div');

        // Click on the contact name to view the contact details
        $I->click(['link' => $contactName]);

        // Wait for the contact details page to load and confirm we're on the correct page
        $I->waitForText($contactName, 30);
        $I->see($contactName);

        // Click on the edit button
        $I->click('#toolbar > div.std-toolbar.btn-group > a:nth-child(1)');

        // Wait for the edit form to be visible
        $I->waitForElementVisible('#core > div.pa-md.bg-light-xs.bdr-b > h4', 30);
        $I->see("Edit $contactName");

        // Edit the first and last names
        $I->fillField('#lead_firstname', 'Edited-First-Name');
        $I->fillField('#lead_lastname', 'Edited-Last-Name');

        // Save and close the form
        $I->waitForElementClickable('#lead_buttons_save_toolbar', 30);
        $I->click('#lead_buttons_save_toolbar');

        // Verify the update message
        $I->waitForText('Edited-First-Name Edited-Last-Name has been updated!', 30);
        $I->see('Edited-First-Name Edited-Last-Name has been updated!');
    }

    public function deleteContactFromList(AcceptanceTester $I)
    {
        // Navigate to the contacts page
        $I->amOnPage('/s/contacts');

        // Grab the name of the first contact in the list
        $contactName = $I->grabTextFrom('#leadTable tbody tr:first-child td:nth-child(2) a div');

        // Check we can see the first contact name
        $I->see($contactName);

        // Click on the dropdown caret on the first contact
        $I->click('#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > button');

        // Wait for the dropdown menu to show and click the delete menu option
        $I->waitForElementVisible('#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > ul > li:nth-child(4) > a', 5);
        $I->click('#leadTable > tbody > tr:nth-child(1) > td:nth-child(1) > div > div > ul > li:nth-child(4) > a');

        // Wait for the modal to show and confirm deletion
        $I->waitForElementVisible('button.btn.btn-danger', 5);
        $I->click('button.btn.btn-danger');

        // Wait for the delete confirmation message
        $I->waitForText("$contactName has been deleted!", 30);
        $I->see("$contactName has been deleted!");
    }

    public function deleteContactFromProfile(AcceptanceTester $I)
    {
        // Navigate to the contacts page
        $I->amOnPage('/s/contacts');

        // Grab the name of the first contact in the list
        $contactName = $I->grabTextFrom('#leadTable tbody tr:first-child td:nth-child(2) a div');

        // Click on the contact name to view the contact details
        $I->click(['link' => $contactName]);

        // Wait for the contact details page to load and confirm we're on the correct page
        $I->waitForText($contactName, 30);
        $I->see($contactName);

        // Click the dropdown caret to show the delete option
        $I->click('#toolbar .std-toolbar.btn-group > button > i');

        // Wait for the dropdown to be displayed and click on the delete option
        $I->waitForElementVisible('#toolbar .std-toolbar.btn-group.open > ul', 30);
        $I->click('#toolbar .std-toolbar.btn-group.open > ul > li:nth-child(5) > a');

        // Wait for the modal to become visible and click on the button to confirm delete
        $I->waitForElementVisible('button.btn.btn-danger', 30);
        $I->click('button.btn.btn-danger');

        // Wait for the delete to be completed and confirm the contact is deleted
        $I->waitForText("$contactName has been deleted!", 30);
        $I->see("$contactName has been deleted!");
    }
}
