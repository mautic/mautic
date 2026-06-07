<?php

declare(strict_types=1);

use Page\Acceptance\EmailsPage;
use Step\Acceptance\EmailStep;
use Step\Acceptance\SegmentStep;

final class EmailScheduleCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function triggeredEmailShowsScheduleOptionsOnCreate(AcceptanceTester $I): void
    {
        $I->amOnPage(EmailsPage::URL);

        // Create a new email
        $I->waitForElementClickable(EmailsPage::NEW_BUTTON);
        $I->click(EmailsPage::NEW_BUTTON);

        // Wait for email type modal
        $I->waitForElementVisible(EmailsPage::EMAIL_TYPE_MODAL, 10);

        // Click "Triggered email"
        $I->waitForElementClickable(EmailsPage::SELECT_TRIGGERED_EMAIL);
        $I->click(EmailsPage::SELECT_TRIGGERED_EMAIL);

        // Wait until modal closes
        $I->waitForElementNotVisible(EmailsPage::EMAIL_TYPE_MODAL, 10);

        // Assert schedule container is visible
        $I->waitForElementVisible('#scheduleOptions', 10);
        $I->seeElement('#scheduleOptions');

        // Assert publish up/down datetime fields exist
        $I->seeElement('#emailform_publishUp');
        $I->seeElement('#emailform_publishDown');

        // Scheduling options notice must NOT exist
        $I->dontSeeElement('#scheduleOptionsNotice');
    }

    public function segmentEmailHidesScheduleOptionsOnCreate(AcceptanceTester $I): void
    {
        $I->amOnPage(EmailsPage::URL);

        // Create a new email
        $I->waitForElementClickable(EmailsPage::NEW_BUTTON);
        $I->click(EmailsPage::NEW_BUTTON);

        // Wait for email type modal
        $I->waitForElementVisible(EmailsPage::EMAIL_TYPE_MODAL, 10);

        // Click "Segment email"
        $I->waitForElementClickable(EmailsPage::SELECT_SEGMENT_EMAIL);
        $I->click(EmailsPage::SELECT_SEGMENT_EMAIL);
        $I->waitForElementNotVisible(EmailsPage::EMAIL_TYPE_MODAL, 10);

        // Schedule options should exist in DOM but be hidden (class "hide")
        $I->seeElementInDOM('#scheduleOptions');
        $I->seeElementInDOM('#emailform_publishUp');
        $I->seeElementInDOM('#emailform_publishDown');

        // Schedule options should be hidden (not visible)
        $I->dontSeeElement('#scheduleOptions');
    }

    public function createTriggeredEmailAndVerifyScheduleOptionsOnEdit(AcceptanceTester $I, EmailStep $email): void
    {
        $name = 'Triggered Email '.date('YmdHis');

        $email->createTriggeredEmail($name);

        // Wait for redirect to email detail page
        $I->waitForText($name, 10, 'h1.page-header-title');

        // Assert the header contains the created email name
        $I->see($name, 'h1.page-header-title');

        // Click on Edit button
        $I->waitForElementClickable(EmailsPage::EDIT_BUTTON, 10);
        $I->click(EmailsPage::EDIT_BUTTON);

        // Assert schedule container is visible
        $I->waitForElementVisible('#scheduleOptions', 10);
        $I->seeElement('#scheduleOptions');

        // Assert publish up/down datetime fields exist
        $I->seeElement('#emailform_publishUp');
        $I->seeElement('#emailform_publishDown');

        // Scheduling options notice must NOT exist
        $I->dontSeeElement('#scheduleOptionsNotice');
    }

    public function createSegmentEmailAndVerifyScheduleNoticeTogglesOnEdit(AcceptanceTester $I, SegmentStep $segment, EmailStep $email): void
    {
        $contactSegmentName = 'Segment '.date('YmdHis');

        $segment->createAContactSegment($contactSegmentName);

        $segmentEmailName = 'Segment Email '.date('YmdHis');

        $email->createSegmentEmail($segmentEmailName);

        // Wait for redirect to email detail page
        $I->waitForText($segmentEmailName, 10, 'h1.page-header-title');

        // Assert the header contains the created email name
        $I->see($segmentEmailName, 'h1.page-header-title');

        // Click on Edit button
        $I->waitForElementClickable(EmailsPage::EDIT_BUTTON, 10);
        $I->click(EmailsPage::EDIT_BUTTON);

        // Assert schedule container is visible
        $I->waitForElementVisible('#scheduleOptions', 10);
        $I->seeElement('#scheduleOptions');

        // Assert publish up/down datetime fields does not exist
        $I->dontSeeElement('#emailform_publishUp');
        $I->dontSeeElement('#emailform_publishDown');

        // Notice must exist and be visible
        $I->seeElement('#scheduleOptionsNotice');
        $I->see(
            'To schedule a Segment Email, use the Schedule button on the email view page to set the start and stop dates.',
            '#scheduleOptionsNotice'
        );

        // Toggle isPublished to No
        $I->executeJS("document.getElementById('emailform_isPublished_0').click();");

        // Assert isPublished state changed
        $I->waitForJS("return document.getElementById('emailform_isPublished_0').checked === true;", 10);

        // Assert notice is not visible
        $I->dontSeeElement('#scheduleOptionsNotice');
    }

    public function createSegmentEmailAndVerifyScheduleButtonOnView(AcceptanceTester $I, SegmentStep $segment, EmailStep $email): void
    {
        $contactSegmentName = 'Segment '.date('YmdHis');

        $segment->createAContactSegment($contactSegmentName);

        $segmentEmailName = 'Segment Email '.date('YmdHis');

        $email->createSegmentEmail($segmentEmailName);

        // Confirm we're on the email view page for the created email
        $I->waitForText($segmentEmailName, 10, 'h1.page-header-title');
        $I->see($segmentEmailName, 'h1.page-header-title');

        // Assert Schedule button exists on view page
        $I->waitForElementVisible(EmailsPage::SCHEDULE_BUTTON, 10);
        $I->seeElement(EmailsPage::SCHEDULE_BUTTON);

        // Verify it links to scheduleSend/<id>
        $href = (string) $I->grabAttributeFrom(EmailsPage::SCHEDULE_BUTTON, 'href');
        $I->assertMatchesRegularExpression('~^/s/emails/scheduleSend/\d+$~', $href);
    }
}
