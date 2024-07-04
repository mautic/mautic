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
        $I->amOnPage(ContactPage::$URL);

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

        $I->waitForElementVisible('.page-header-title .span-block', 30);
        $I->see('FirstName LastName', '.page-header-title .span-block');

        // Check the database for the created contact
        $I->seeInDatabase('leads', ['firstname' => 'FirstName', 'email' => 'email@example.com']);
    }

    public function acessEditContactFormFromList(
        AcceptanceTester $I,
        Contact $contact
    ) {
        $I->amOnPage(ContactPage::$URL);

        // Grab the name of the first contact in the list
        $contactName = $contact->grabContactNameFromList(1);

        // Click on the dropdown caret on the first contact
        $contact->dropDownMenu(1);

        // Wait for the dropdown menu to show and click the delete menu option
        $contact->selectOptionFromDropDown(1, 1);

        // Wait for the edit form to be visible
        $I->waitForElementVisible(ContactPage::$editForm, 30);
        $I->see("Edit $contactName");

        // Close the edit form (No changes are made)
        $I->click(ContactPage::$cancelButton);
    }

    public function editContactFromProfile(
        AcceptanceTester $I,
        Contact $contact
    ) {
        $I->amOnPage(ContactPage::$URL);

        // Grab the name of the first contact in the list
        $contactName = $contact->grabContactNameFromList(1);

        // Click on the contact name to view the contact details
        $I->click(['link' => $contactName]);

        // Wait for the contact details page to load and confirm we're on the correct page
        $I->waitForText($contactName, 30);
        $I->see($contactName);

        // Click on the edit button
        $I->click(ContactPage::$editButton);

        // Wait for the edit form to be visible
        $I->waitForElementVisible(ContactPage::$editForm, 30);
        $I->see("Edit $contactName");

        // Edit the first and last names
        $I->fillField(ContactPage::$firstNameField, 'Edited-First-Name');
        $I->fillField(ContactPage::$lastNameField, 'Edited-Last-Name');

        // Save and close the form
        $I->waitForElementClickable(ContactPage::$saveAndCloseButton, 30);
        $I->click(ContactPage::$saveAndCloseButton);

        // Verify the update message
        $I->waitForText('Edited-First-Name Edited-Last-Name has been updated!', 30);
        $I->see('Edited-First-Name Edited-Last-Name has been updated!');
    }

    public function deleteContactFromList(
        AcceptanceTester $I,
        Contact $contact
    ) {
        $I->amOnPage(ContactPage::$URL);

        // Grab the name of the first contact in the list
        $contactName = $contact->grabContactNameFromList(1);

        // Click on the dropdown caret on the first contact
        $contact->dropDownMenu(1);

        // Wait for the dropdown menu to show and click the delete menu option
        $contact->selectOptionFromDropDown(1, 4);

        // Wait for the modal to show and confirm deletion
        $I->waitForElementVisible(ContactPage::$ConfirmDelete, 5);
        $I->click(ContactPage::$ConfirmDelete);

        // Wait for the delete confirmation message
        $I->waitForText("$contactName has been deleted!", 30);
        $I->see("$contactName has been deleted!");
    }

    public function deleteContactFromProfile(
        AcceptanceTester $I,
        Contact $contact
    ) {
        $I->amOnPage(ContactPage::$URL);

        // Grab the name of the first contact in the list
        $contactName = $contact->grabContactNameFromList(1);

        // Click on the contact name to view the contact details
        $I->click(['link' => $contactName]);

        // Wait for the contact details page to load and confirm we're on the correct page
        $I->waitForText($contactName, 30);
        $I->see($contactName);

        // Click the dropdown caret to show the delete option
        $I->click(ContactPage::$dropDown);

        // click on the delete option
        $I->click(ContactPage::$delete);

        // Wait for the modal to become visible and click on the button to confirm delete
        $I->waitForElementVisible(ContactPage::$ConfirmDelete, 5);
        $I->click(ContactPage::$ConfirmDelete);

        // Wait for the delete to be completed and confirm the contact is deleted
        $I->waitForText("$contactName has been deleted!", 30);
        $I->see("$contactName has been deleted!");
    }

    public function batchDeleteContacts(
        AcceptanceTester $I,
        Contact $contact
    ) {
        $I->amOnPage(ContactPage::$URL);

        $contactName1 = $contact->grabContactNameFromList(1);
        $contactName2 = $contact->grabContactNameFromList(2);

        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        $contact->selectOptionFromDropDownForMultipleSelections(11);

        // Wait for the modal to become visible and click on the button to confirm delete
        $I->waitForElementVisible(ContactPage::$ConfirmDelete, 5);
        $I->click(ContactPage::$ConfirmDelete);

        $I->reloadPage(); // Wait for delete to be completed

        $I->dontSee($contactName1);
        $I->dontSee($contactName2);
    }
}
