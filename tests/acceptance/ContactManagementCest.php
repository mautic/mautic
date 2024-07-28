<?php

use Page\Acceptance\CampaignPage;
use Page\Acceptance\ContactPage;
use PHPUnit\Framework\Assert;
use Step\Acceptance\Campaign;
use Step\Acceptance\ContactStep;

class ContactManagementCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->login('admin', 'Maut1cR0cks!');
    }

    public function createContactFromQuickAdd(
        AcceptanceTester $I,
        ContactStep $contact
    ): void {
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

        // Confirm the contact is in the database
        $I->seeInDatabase('leads', ['firstname' => 'QuickAddFirstName', 'email' => 'quickadd@example.com']);
    }

    public function createContactFromForm(
        AcceptanceTester $I,
        ContactStep $contact
    ): void {
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

        // Confirm the contact is created
        $I->waitForElementVisible('.page-header-title .span-block', 30);
        $I->see('FirstName LastName', '.page-header-title .span-block');

        // Check the database for the created contact
        $I->seeInDatabase('leads', ['firstname' => 'FirstName', 'email' => 'email@example.com']);
    }

    public function accessEditContactFormFromList(
        AcceptanceTester $I,
        ContactStep $contact
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Grab the name of the first contact in the list
        $contactName = $contact->grabContactNameFromList(1);

        // Click on the dropdown caret on the first contact
        $contact->dropDownMenu(1);

        // Click the edit menu option
        $contact->selectOptionFromDropDown(1, 1);

        // Wait for the edit form to be visible
        $I->waitForElementVisible(ContactPage::$editForm, 30);
        $I->see("Edit $contactName");

        // Close the edit form (No changes are made)
        $I->click(ContactPage::$cancelButton);
    }

    public function editContactFromProfile(
        AcceptanceTester $I,
        ContactStep $contact
    ): void {
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
        ContactStep $contact
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Grab the name of the first contact in the list
        $contactName = $contact->grabContactNameFromList(1);

        // Click the delete menu option
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
        ContactStep $contact
    ): void {
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

        // Click on the delete option
        $I->click(ContactPage::$delete);

        // Wait for the modal to show and confirm deletion
        $I->waitForElementVisible(ContactPage::$ConfirmDelete, 5);
        $I->click(ContactPage::$ConfirmDelete);

        // Wait for the delete confirmation message
        $I->waitForText("$contactName has been deleted!", 30);
        $I->see("$contactName has been deleted!");
    }

    public function batchDeleteContacts(
        AcceptanceTester $I,
        ContactStep $contact
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Grab the names of the first two contacts in the list
        $contactName1 = $contact->grabContactNameFromList(1);
        $contactName2 = $contact->grabContactNameFromList(2);

        // Select the contacts from the list
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        // Select delete option from dropdown for multiple selections
        $contact->selectOptionFromDropDownForMultipleSelections(11);

        // Wait for the modal to become visible and click on the button to confirm delete
        $I->waitForElementVisible(ContactPage::$ConfirmDelete, 5);
        $I->click(ContactPage::$ConfirmDelete);
        $I->reloadPage(); // Wait for delete to be completed

        // Confirm the contacts are no longer visible
        $I->dontSee($contactName1);
        $I->dontSee($contactName2);
    }

    public function importCSV(
        AcceptanceTester $I,
        ContactStep $contact
    ): void {
        $I->amOnPage(ContactPage::$URL);

        $initialContactCount = $I->grabNumRecords('leads');

        // Click on the import button
        $contact->selectOptionFromDropDownContactsPage(3);

        // Wait for the import page to load
        $I->waitForText('Import Contacts', 30, 'h1.page-header-title');
        $I->seeElement(ContactPage::$importModal);

        // Click 'Choose file' and select a file
        $I->attachFile(ContactPage::$chooseFileButton, '10contacts.csv');

        // Click the upload button
        $I->click(ContactPage::$uploadButton);

        // Wait for the new form to open
        $I->waitForElement(ContactPage::$importForm, 30);

        // Fill in the form
        $I->seeElement(ContactPage::$importFormFields);
        $contact->fillImportFormFields();

        // Click 'import in browser'
        $I->click(ContactPage::$importInBrowser);

        // Wait for import completion message
        $I->waitForElement(ContactPage::$importProgressComplete, 30);
        $I->see('Success!', 'h4');

        // Extract the number of contacts created from the progress message
        $importProgress = $I->grabTextFrom('#leadImportProgressComplete > div > div > div.panel-body > h4');

        // Use a regular expression to extract the number of contacts created
        preg_match('/(\d+) created/', $importProgress, $matches);
        $expectedContactsAdded = isset($matches[1]) ? (int) $matches[1] : 0;

        // Get the count of contacts after import
        $finalContactCount = $I->grabNumRecords('leads');

        // Calculate the expected final contact count
        $expectedContactCount = $initialContactCount + $expectedContactsAdded;

        // Assert the expected number of contacts
        Codeception\Util\Fixtures::add('finalContactCount', $finalContactCount);
        Assert::assertEquals($expectedContactCount, $finalContactCount);
    }

    public function exportExcel(
        AcceptanceTester $I,
        ContactStep $contact
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Click on the export button
        $contact->selectOptionFromDropDownContactsPage(2);
    }

    public function batchAddToCampaign(
        AcceptanceTester $I,
        ContactStep $contact,
        Campaign $campaign
    ): void {
        // Navigate to the contacts page
        $I->amOnPage(ContactPage::$URL);

        // Grab the names of the first and second contacts from the list
        $contactName1 = $contact->grabContactNameFromList(1);
        $contactName2 = $contact->grabContactNameFromList(2);

        // Navigate to the campaign page
        $I->amOnPage(CampaignPage::$URL);

        // Click on the "Contacts" tab in the campaign page
        $I->click(CampaignPage::$contactsTab);

        // Verify that the first and second contacts are not in the campaign yet
        $I->dontSee($contactName1, CampaignPage::$firstContactFromContactsTab);
        $I->dontSee($contactName2, CampaignPage::$secondContactFromContactsTab);

        // Return to the contacts page
        $I->amOnPage(ContactPage::$URL);

        // Select the first and second contacts from the list
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        $contact->selectOptionFromDropDownForMultipleSelections(1);
        $campaign->addContactsToCampaign();

        // Navigate back to the campaign page and click on the "Contacts" tab
        $I->amOnPage(CampaignPage::$URL);
        $I->click(CampaignPage::$contactsTab);

        // Verify that the first and second contacts are now in the campaign
        $I->waitForElementVisible(CampaignPage::$firstContactFromContactsTab);
        $I->see($contactName1, CampaignPage::$firstContactFromContactsTab);
        $I->see($contactName2, CampaignPage::$secondContactFromContactsTab);
    }

    public function batchRemoveFromCampaign(
        AcceptanceTester $I,
        ContactStep $contact,
        Campaign $campaign
    ): void {
        // Navigate to the contacts page
        $I->amOnPage(ContactPage::$URL);

        // Select the first and second contacts from the list
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        $contact->selectOptionFromDropDownForMultipleSelections(1);
        $campaign->addContactsToCampaign();

        // Return to the contacts page
        $I->amOnPage(ContactPage::$URL);

        // Grab the names of the first and second contacts from the list
        $contactName1 = $contact->grabContactNameFromList(1);
        $contactName2 = $contact->grabContactNameFromList(2);

        // Select the first and second contacts again for removal
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        $contact->selectOptionFromDropDownForMultipleSelections(1);

        // Wait for the modal to appear and click the "Remove from campaign" option
        $I->waitForElementVisible(ContactPage::$campaignsModalAddOption, 5);
        $I->click(ContactPage::$campaignsModalRemoveOption);
        $I->click(ContactPage::$firstCampaignFromRemoveList);
        $I->click(ContactPage::$campaignsModalSaveButton);

        // Navigate to the campaign page and click on the "Contacts" tab
        $I->amOnPage(CampaignPage::$URL);
        $I->click(CampaignPage::$contactsTab);

        // Verify that the first and second contacts are no longer in the campaign
        $I->dontSee($contactName1, CampaignPage::$firstContactFromContactsTab);
        $I->dontSee($contactName2, CampaignPage::$secondContactFromContactsTab);
    }
}
