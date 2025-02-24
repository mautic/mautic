<?php

use Facebook\WebDriver\WebDriverKeys;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Page\Acceptance\CampaignPage;
use Page\Acceptance\ContactPage;
use PHPUnit\Framework\Assert;
use Step\Acceptance\CampaignStep;
use Step\Acceptance\ContactStep;

class ContactManagementCest
{
    public function _before(AcceptanceTester $I): void
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

        // Fill out the Quick Add form
        $contact->fillContactForm('QuickAddFirstName', 'QuickAddLastName', 'quickadd@example.com', 'TestTag');

        // Submit the form
        $I->waitForElementClickable(ContactPage::$saveButton, 30);
        $I->click(ContactPage::$saveButton);
        $I->waitForElementNotVisible(ContactPage::$quickAddModal, 30);

        // Confirm the contact is in the database
        $I->seeInDatabase('test_leads', ['firstname' => 'QuickAddFirstName', 'email' => 'quickadd@example.com']);
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

        // Fill out the contact form
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
        $I->seeInDatabase('test_leads', ['firstname' => 'FirstName', 'email' => 'email@example.com']);
    }

    public function accessEditContactFormFromList(
        AcceptanceTester $I,
        ContactStep $contact
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Grab the name of the first contact in the list
        $contactName = $contact->grabContactNameFromList(1);

        // Click on the dropdown caret on the first contact and click on the edit option
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

        // Click on the dropdown caret on the first contact and click the delete menu option
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
        $I->wait(5);

        // Confirm the contacts are deleted
        $I->dontSee($contactName1);
        $I->dontSee($contactName2);
        $I->dontSeeInDatabase('test_leads', ['firstname' => $contactName1]);
        $I->dontSeeInDatabase('test_leads', ['firstname' => $contactName2]);
    }

    public function batchAddToCampaign(
        AcceptanceTester $I,
        ContactStep $contact,
        CampaignStep $campaign
    ): void {
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

        // Select add to campaign option from dropdown for multiple selections
        $contact->selectOptionFromDropDownForMultipleSelections(1);

        // Add the contacts to the campaign
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
        CampaignStep $campaign
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Select the first and second contacts from the list
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        // Select change campaign option from dropdown for multiple selections
        $contact->selectOptionFromDropDownForMultipleSelections(1);

        // Add the selected contacts to a campaign (to be removed later)
        $campaign->addContactsToCampaign();

        // Return to the contacts page
        $I->amOnPage(ContactPage::$URL);

        // Grab the names of the first and second contacts from the list
        $contactName1 = $contact->grabContactNameFromList(1);
        $contactName2 = $contact->grabContactNameFromList(2);

        // Select the first and second contacts again for removal
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        // // Select change campaign option from dropdown for multiple selections
        $contact->selectOptionFromDropDownForMultipleSelections(1);

        // Wait for the modal to appear and click the "Remove from campaign" option
        $I->waitForElementVisible(ContactPage::$campaignsModalAddOption, 5);
        $I->click(ContactPage::$campaignsModalRemoveOption);

        // Select the first campaign from the list and click save
        $I->click(ContactPage::$firstCampaignFromRemoveList);
        $I->click(ContactPage::$campaignsModalSaveButton);

        // Navigate to the campaign page and click on the "Contacts" tab
        $I->amOnPage(CampaignPage::$URL);
        $I->click(CampaignPage::$contactsTab);

        // Verify that the first and second contacts are no longer in the campaign
        $I->dontSee($contactName1, CampaignPage::$firstContactFromContactsTab);
        $I->dontSee($contactName2, CampaignPage::$secondContactFromContactsTab);
    }

    public function batchChangeOwner(
        AcceptanceTester $I,
        ContactStep $contact,
    ): void {
        // Check the current owner of the first and second contacts, it should be the sales user
        $contact->checkOwner(1);
        $contact->checkOwner(2);

        // Navigate back to contacts page
        $I->amOnPage(ContactPage::$URL);

        // Select the first and second contacts from the list
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        // Select change owner option from dropdown for multiple selections
        $contact->selectOptionFromDropDownForMultipleSelections(4);

        // Wait for the modal to appear
        $I->waitForElementClickable(ContactPage::$addToTheFollowing, 5);

        // Select the new owner as "Admin User" from the options
        $I->click(ContactPage::$addToTheFollowing);
        $I->click(ContactPage::$adminUser);
        $I->click(ContactPage::$changeOwnerModalSaveButton);

        // Verify that the owner of the first and second contacts has been changed
        $contact->verifyOwner(1);
        $contact->verifyOwner(2);
    }

    public function batchAddAndRemoveSegment(
        AcceptanceTester $I,
        ContactStep $contact,
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Grab the names of the first and second contacts in the list
        $contactName1 = $contact->grabContactNameFromList(1);
        $contactName2 = $contact->grabContactNameFromList(2);

        // Search for contacts in the "Segment Test 3" segment
        $I->fillField(ContactPage::$searchBar, 'segment:segment-test-3');
        $I->wait(1);
        $I->pressKey(ContactPage::$searchBar, WebDriverKeys::ENTER);
        $I->wait(5); // Wait for search results to load

        // Verify that the first and second contacts are not in the segment
        $I->dontsee("$contactName1");
        $I->dontsee("$contactName2");

        // Clear the search bar
        $I->click(ContactPage::$clearSearch);
        $I->waitForElementVisible('#leadTable', 10); // Wait for the contact list to be visible

        // Select the first and second contacts from the list
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        // Select change segment option from dropdown for multiple selections
        $contact->selectOptionFromDropDownForMultipleSelections(5);

        // Wait for the "Add to the following segment" modal to appear and click it
        $I->waitForElementClickable(ContactPage::$addToTheFollowingSegment, 10);
        $I->click(ContactPage::$addToTheFollowingSegment);
        // Fill in the segment name and save
        $I->fillField(ContactPage::$addToTheFollowingSegmentInput, 'Segment Test 3');
        $I->pressKey(ContactPage::$addToTheFollowingSegmentInput, WebDriverKeys::ENTER);
        $I->click(ContactPage::$changeSegmentModalSaveButton);

        // Search again for contacts in the "Segment Test 3" segment
        $I->fillField(ContactPage::$searchBar, 'segment:segment-test-3');
        $I->wait(1);
        $I->pressKey(ContactPage::$searchBar, WebDriverKeys::ENTER);
        $I->wait(5);

        // Verify that the selected contacts are now in the 'segment-test-3' segment
        $I->see("$contactName1");
        $I->see("$contactName2");

        // Clear the search bar
        $I->click(ContactPage::$clearSearch);
        $I->waitForElementVisible('#leadTable', 10);

        // Now lets remove the contacts we just added to the "segment test 3"

        $I->amOnPage(ContactPage::$URL);

        // Select the first and second contacts from the list
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        // Select change segment option from dropdown for multiple selections
        $contact->selectOptionFromDropDownForMultipleSelections(5);

        // Wait for the "Remove from the following segment" modal to appear and click it
        $I->waitForElementClickable(ContactPage::$removeFromTheFollowingSegment, 10);
        $I->click(ContactPage::$removeFromTheFollowingSegment);
        // Fill in the segment name and save
        $I->fillField(ContactPage::$removeFromTheFollowingSegmentInput, 'Segment Test 3');
        $I->pressKey(ContactPage::$removeFromTheFollowingSegmentInput, WebDriverKeys::ENTER);
        $I->click(ContactPage::$changeSegmentModalSaveButton);

        // Search for contacts in the "Segment Test 3" segment
        $I->fillField(ContactPage::$searchBar, 'segment:segment-test-3');
        $I->wait(1);
        $I->pressKey(ContactPage::$searchBar, WebDriverKeys::ENTER);
        $I->wait(5); // Wait for search results to load
        // Verify that the first and second contacts are not in the segment
        $I->dontsee("$contactName1");
        $I->dontsee("$contactName2");

        // Clear the search bar
        $I->click(ContactPage::$clearSearch);
        $I->waitForElementVisible('#leadTable', 10);
    }

    public function batchSetDoNotContact(
        AcceptanceTester $I,
        ContactStep $contact,
    ): void {
        $I->amOnPage(ContactPage::$URL);
        $I->dontSeeElement(ContactPage::$firstContactDoNotContact);
        $I->dontSeeElement(ContactPage::$secondContactDoNotContact);

        // Select the first and second contacts from the list
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        // Select change segment option from dropdown for multiple selections
        $contact->selectOptionFromDropDownForMultipleSelections(10);

        $I->waitForElementClickable(ContactPage::$doNotContactSaveButton, 10);
        $I->click(ContactPage::$doNotContactSaveButton);

        $I->ensureNotificationAppears('2 contacts affected');

        $I->reloadPage();

        $I->waitForElementVisible(ContactPage::$firstContactDoNotContact, 15);
        $I->seeElement(ContactPage::$firstContactDoNotContact);

        $I->waitForElementVisible(ContactPage::$secondContactDoNotContact, 15);
        $I->seeElement(ContactPage::$secondContactDoNotContact);
    }

    public function importCSV(
        AcceptanceTester $I,
        ContactStep $contact
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Get initial contact count
        $initialContactCount = $I->grabNumRecords('test_leads');

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
        $finalContactCount = $I->grabNumRecords('test_leads');

        // Calculate the expected final contact count
        $expectedContactCount = $initialContactCount + $expectedContactsAdded;

        // Assert the expected number of contacts
        Codeception\Util\Fixtures::add('finalContactCount', $finalContactCount);
        Assert::assertEquals($expectedContactCount, $finalContactCount);
    }
}
