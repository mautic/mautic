<?php

use Page\Acceptance\SegmentsPage;
use Step\Acceptance\ContactStep;
use Step\Acceptance\SegmentStep;

class SegmentManagementCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login('admin', 'Maut1cR0cks!');
    }

    public function createSegment(
        AcceptanceTester $I,
        SegmentStep $segment
    ): void {
        $segment->createAContactSegment('testSegment');
        $I->waitForElementVisible('.page-header-title', 30);
        $I->wait(2);
        $I->seeInDatabase('test_lead_lists', ['name' => 'testSegment']);
    }

    public function editSegment(
        AcceptanceTester $I,
        SegmentStep $segment
    ): void {
        $I->amOnPage(SegmentsPage::$URL);
        $segmentName  = explode('(', $segment->grabSegmentNameFromList(1))[0];
        $segmentAlias = '('.explode('(', $segment->grabSegmentNameFromList(1))[1];
        $I->click(['link' => $segmentName.$segmentAlias]);

        $I->waitForText($segmentName, 30);
        $I->see($segmentName);

        // Click on the edit button
        $I->click(SegmentsPage::$editButton);

        // Wait for the edit form to be visible
        $I->waitForText('Edit Segment', 30);

        $I->fillField(SegmentsPage::$segmentName, 'Edited-Segment-Name');

        // Save and close the form
        $I->waitForElementClickable(SegmentsPage::$saveAndCloseButton, 30);
        $I->click(SegmentsPage::$saveAndCloseButton);

        // Verify the update message
        $I->waitForText("Edited-Segment-Name $segmentAlias has been updated!", 30);
    }

    public function deleteSingleSegment(
        AcceptanceTester $I,
        SegmentStep $segment
    ): void {
        $I->amOnPage(SegmentsPage::$URL);

        // Grab the name of the first contact in the list
        $segmentName = $segment->grabSegmentNameFromList(1);

        // Click on the contact name to view the contact details
        $I->click(['link' => $segmentName]);

        // Wait for the segment details page to load and confirm we're on the correct page
        $I->waitForText($segmentName, 30);
        $I->see($segmentName);

        // Click the dropdown caret to show the delete option
        $I->click(SegmentsPage::$dropDown);

        // Click on the delete option
        $I->click(SegmentsPage::$delete);

        // Wait for the modal to show and confirm deletion
        $I->waitForElementVisible(SegmentsPage::$ConfirmDelete, 5);
        $I->click(SegmentsPage::$ConfirmDelete);

        // Wait for the delete confirmation message
        $I->waitForText("$segmentName has been deleted!", 30);
        $I->see("$segmentName has been deleted!");
    }

    public function batchDeleteSegment(
        AcceptanceTester $I,
        SegmentStep $segment
    ): void {
        $I->amOnPage(SegmentsPage::$URL);

        // Grab the names of the first two segments in the list
        $segmentName1 = $segment->grabSegmentNameFromList(1);
        $segmentName2 = $segment->grabSegmentNameFromList(2);

        // Select the segments from the list
        $segment->selectsegmentFromList(1);
        $segment->selectsegmentFromList(2);

        // Select delete option from dropdown for multiple selections
        $segment->selectOptionFromDropDownForMultipleSelections(SegmentsPage::$tableName, 11);

        // Wait for the modal to become visible and click on the button to confirm delete
        $I->waitForElementVisible(SegmentsPage::$ConfirmDelete, 5);
        $I->click(SegmentsPage::$ConfirmDelete);
        $I->wait(5);

        // Confirm the segments are deleted
        $I->dontSee($segmentName1);
        $I->dontSee($segmentName2);
        $I->dontSeeInDatabase('test_lead_lists', ['name' => $segmentName1]);
        $I->dontSeeInDatabase('test_lead_lists', ['name' => $segmentName2]);
    }

    public function addToSegmentViaFilter(
        AcceptanceTester $I,
        SegmentStep $segment,
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
        $contact->fillContactForm('FilterFirstName', 'FilterLastName', 'filter@example.com', 'TestTag');

        // Submit the form
        $I->waitForElementClickable(ContactPage::$saveButton, 30);
        $I->click(ContactPage::$saveButton);
        $I->waitForElementNotVisible(ContactPage::$quickAddModal, 30);

        $I->amOnPage(SegmentsPage::$URL);
        $I->waitForElementClickable(SegmentsPage::$newButton);
        $I->click(SegmentsPage::$newButton);
        $I->waitForElementVisible(SegmentsPage::$segmentName);
        $I->fillField(SegmentsPage::$segmentName, 'filterTest');
        $I->click(SegmentsPage::$filtersTab);
        $I->waitForElementVisible(SegmentsPage::$filtersDropdown);
        $I->clickWithLeftButton(SegmentsPage::$filtersDropdown);
        $I->waitForElementVisible(SegmentsPage::$filtersSearchField);
        $I->fillField(SegmentsPage::$filtersSearchField, 'First');
        $I->waitForElementNotVisible('#available_segment_filters_chosen > div > ul > li:nth-child(3)');
        $I->click(SegmentsPage::$saveAndCloseButton);
    }
}
