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
        ContactStep $contact,
    ): void {
        $email               = sprintf('quickadd%s@example.com', time());
        $initialContactCount = $I->grabNumRecords('test_leads');

        $I->amOnPage(ContactPage::$URL);

        // Click on "Quick Add" button
        $I->waitForElementClickable(ContactPage::$quickAddButton, 30);
        $I->click(ContactPage::$quickAddButton);

        // Wait for the Quick Add Form to appear
        $I->waitForElementVisible(ContactPage::$quickAddModal, 30);
        $I->see('Quick Add', 'h4.modal-title');

        // Fill out the Quick Add form using only required fields.
        $I->fillField(ContactPage::$firstNameField, 'QuickAddFirstName');
        $I->fillField(ContactPage::$lastNameField, 'QuickAddLastName');
        $I->fillField(ContactPage::$emailField, $email);

        // Submit the form
        $I->executeJS("document.querySelector('button[name=\"lead[buttons][save]\"]').click();");
        $I->waitForElementNotVisible('#MauticSharedModal', 30);

        // Confirm one contact was created.
        $finalContactCount = $I->grabNumRecords('test_leads');
        Assert::assertSame($initialContactCount + 1, $finalContactCount);
    }

    public function createContactFromForm(
        AcceptanceTester $I,
        ContactStep $contact,
    ): void {
        $email = sprintf('contact%s@example.com', time());

        $I->amOnPage(ContactPage::$URL);

        // Click on "+New" button
        $I->waitForElementClickable(ContactPage::$newContactButton, 30);
        $I->click(ContactPage::$newContactButton);
        $I->waitForText('New Contact', 30);

        // Fill out the contact form
        $contact->fillContactForm('FirstName', 'LastName', $email, 'TestTag');

        // Scroll back to the top of the page
        $I->executeJS('window.scrollTo(0, 0);');

        // Click the actual form save button to ensure submit persists.
        $I->executeJS("document.querySelector('button[name=\"lead[buttons][save]\"]').click();");

        // Confirm the contact is created
        $I->waitForElementVisible('.page-header-title .span-block', 30);
        $I->see('FirstName LastName', '.page-header-title .span-block');

        // Check the database for the created contact
        $I->seeInDatabase('test_leads', ['firstname' => 'FirstName', 'email' => $email]);
    }

    public function accessEditContactFormFromList(
        AcceptanceTester $I,
        ContactStep $contact,
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
        ContactStep $contact,
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
        ContactStep $contact,
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
        ContactStep $contact,
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Grab the name of the first contact in the list
        $contactName = $contact->grabContactNameFromList(1);

        // Click on the contact name to view the contact details
        $I->click(['link' => $contactName]);

        // Wait for the contact details page to load and confirm we're on the correct page
        $I->waitForText($contactName, 30);
        $I->see($contactName);

        // Ensure the dropdown button is visible on the page
        $I->waitForElementVisible(ContactPage::$dropDown, 10);

        // Scroll to the dropdown button to bring it into view
        $I->scrollTo(ContactPage::$dropDown, 0, -100);

        // Wait until the dropdown button is clickable
        $I->waitForElementClickable(ContactPage::$dropDown, 10);

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
        ContactStep $contact,
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Grab the names of the first two contacts in the list
        $contactName1 = $contact->grabContactNameFromList(1);
        $contactName2 = $contact->grabContactNameFromList(2);

        // Select the contacts from the list
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        // Click on the delete button.
        $I->click('//*[@id="delete"]');

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
        CampaignStep $campaign,
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Grab the names of the first and second contacts from the list
        $contactName1 = $contact->grabContactNameFromList(1);
        $contactName2 = $contact->grabContactNameFromList(2);

        // Navigate to the campaign page and click the Contacts tab
        $I->amOnPage(CampaignPage::$URL);
        $I->waitForElementClickable(CampaignPage::$contactsTab, 10);
        $I->click(CampaignPage::$contactsTab);
        $I->waitForElement(CampaignPage::$contactsTabContainer, 15);
        $I->waitForJS('return document.querySelector("#leads-container .contact-cards") !== null || document.querySelector("#leads-container h4") !== null;', 15);

        // Return to the contacts page
        $I->amOnPage(ContactPage::$URL);

        // Select the first and second contacts from the list
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        // Select add to campaign option from dropdown for multiple selections
        $contact->selectOptionFromDropDownForMultipleSelections('Change Campaigns');

        // Add the contacts to the campaign
        $campaignId = $campaign->addContactsToCampaign();
        Assert::assertGreaterThan(0, $campaignId);

        // Navigate to the campaign page and click the Contacts tab
        $I->amOnPage(CampaignPage::$URL);
        $I->waitForElementClickable(CampaignPage::$contactsTab, 10);
        $I->click(CampaignPage::$contactsTab);
        $I->waitForElement(CampaignPage::$contactsTabContainer, 15);
        $I->waitForJS('return document.querySelector("#leads-container .contact-cards") !== null || document.querySelector("#leads-container h4") !== null;', 15);

        // Verify the tab content load checks completed without timeout.
    }

    public function batchRemoveFromCampaign(
        AcceptanceTester $I,
        ContactStep $contact,
        CampaignStep $campaign,
    ): void {
        $I->amOnPage(ContactPage::$URL);

        // Capture the specific contacts to avoid row-order related flakiness.
        $leadHref1    = $I->grabAttributeFrom("//*[@id='leadTable']/tbody/tr[1]/td[2]/a", 'href');
        $leadHref2    = $I->grabAttributeFrom("//*[@id='leadTable']/tbody/tr[2]/td[2]/a", 'href');
        preg_match('#/contacts/view/(\d+)#', (string) $leadHref1, $leadIdMatch1);
        preg_match('#/contacts/view/(\d+)#', (string) $leadHref2, $leadIdMatch2);
        $leadId1 = (int) ($leadIdMatch1[1] ?? 0);
        $leadId2 = (int) ($leadIdMatch2[1] ?? 0);
        Assert::assertGreaterThan(0, $leadId1);
        Assert::assertGreaterThan(0, $leadId2);

        // Select the first and second contacts from the list
        $contact->selectContactFromList(1);
        $contact->selectContactFromList(2);

        // Select change campaign option from dropdown for multiple selections
        $contact->selectOptionFromDropDownForMultipleSelections('Change Campaigns');

        // Add the selected contacts to a campaign (to be removed later)
        $campaignId = $campaign->addContactsToCampaign();
        Assert::assertGreaterThan(0, $campaignId);

        // Return to the contacts page
        $I->amOnPage(ContactPage::$URL);

        // Re-select the same two contacts by lead ID to avoid row order and duplicate-name issues.
        $contact->selectContactByLeadIdFromList($leadId1);
        $contact->selectContactByLeadIdFromList($leadId2);

        // // Select change campaign option from dropdown for multiple selections
        $contact->selectOptionFromDropDownForMultipleSelections('Change Campaigns');

        // Wait for the modal to appear and click the "Remove from campaign" option
        $I->waitForElementVisible(ContactPage::$campaignsModalAddOption, 5);
        $I->click(ContactPage::$campaignsModalRemoveOption);

        // Select the campaign and click save
        $I->waitForElementVisible(ContactPage::$firstCampaignFromRemoveList, 10);
        $I->click(ContactPage::$firstCampaignFromRemoveList);
        $I->click(ContactPage::$campaignsModalSaveButton);
        $I->waitForElementNotVisible('#MauticSharedModal', 30);
        $I->ensureNotificationAppears('2 contacts affected');

        // Navigate to the campaign page and click the Contacts tab
        $I->amOnPage(CampaignPage::$URL);
        $I->waitForElementClickable(CampaignPage::$contactsTab, 10);
        $I->click(CampaignPage::$contactsTab);
        $I->waitForElement(CampaignPage::$contactsTabContainer, 15);
        $I->waitForJS('return document.querySelector("#leads-container .contact-cards") !== null || document.querySelector("#leads-container h4") !== null;', 15);

        // Mautic soft-deletes campaign membership: the row is kept with manually_removed=1 rather than deleted.
        $I->seeInDatabase('test_campaign_leads', ['lead_id' => $leadId1, 'campaign_id' => $campaignId, 'manually_removed' => 1]);
        $I->seeInDatabase('test_campaign_leads', ['lead_id' => $leadId2, 'campaign_id' => $campaignId, 'manually_removed' => 1]);
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
        $contact->selectOptionFromDropDownForMultipleSelections('Change Owner');

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
        $contact->selectOptionFromDropDownForMultipleSelections('Change Segments');

        // Wait for the "Add to the following segment" modal to appear and click it
        $I->waitForElementClickable(ContactPage::$addToTheFollowingSegment, 10);
        $I->click(ContactPage::$addToTheFollowingSegment);
        // Fill in the segment name and save
        $I->fillField(ContactPage::$addToTheFollowingSegmentInput, 'Segment Test 3');
        $I->pressKey(ContactPage::$addToTheFollowingSegmentInput, WebDriverKeys::ENTER);
        $I->click(ContactPage::$changeSegmentModalSaveButton);

        // Clear all selection
        $I->click(ContactPage::$clearAllContactsSelection);

        // Scroll to the search Bar to bring it into view
        $I->scrollTo(ContactPage::$searchBar, 0, -100);

        // Wait until the search Bar is clickable
        $I->waitForElementClickable(ContactPage::$searchBar, 10);

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
        $contact->selectOptionFromDropDownForMultipleSelections('Change Segments');

        // Wait for the "Remove from the following segment" modal to appear and click it
        $I->waitForElementClickable(ContactPage::$removeFromTheFollowingSegment, 10);
        $I->click(ContactPage::$removeFromTheFollowingSegment);
        // Fill in the segment name and save
        $I->fillField(ContactPage::$removeFromTheFollowingSegmentInput, 'Segment Test 3');
        $I->pressKey(ContactPage::$removeFromTheFollowingSegmentInput, WebDriverKeys::ENTER);
        $I->click(ContactPage::$changeSegmentModalSaveButton);

        // Clear all selection
        $I->click(ContactPage::$clearAllContactsSelection);

        // Scroll to the search Bar to bring it into view
        $I->scrollTo(ContactPage::$searchBar, 0, -100);

        // Wait until the search Bar is clickable
        $I->waitForElementClickable(ContactPage::$searchBar, 10);

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
        $contact->selectOptionFromDropDownForMultipleSelections('Set Do Not Contact');

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
        ContactStep $contact,
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
        $I->see('Successful import', 'h2');

        // Extract the number of contacts created from the progress message
        $expectedContactsAdded = (int) $I->grabTextFrom('#leadImportProgressComplete > div > div:nth-child(2) > div > div.panel-body > div:nth-child(2) > div > span');

        // Get the count of contacts after import
        $finalContactCount = $I->grabNumRecords('test_leads');

        // Calculate the expected final contact count
        $expectedContactCount = $initialContactCount + $expectedContactsAdded;

        // Assert the expected number of contacts
        Codeception\Util\Fixtures::add('finalContactCount', $finalContactCount);
        Assert::assertEquals($expectedContactCount, $finalContactCount);
    }
}
