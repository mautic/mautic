<?php

namespace Acceptance;

use Page\Acceptance\EmailsPage;

class DisplayTestCest
{
    public function _before(\AcceptanceTester $I)
    {
        $I->login('admin', 'Maut1cR0cks!');
    }

    // tests
    public function testCampaignDisplay(\AcceptanceTester $I): void
    {
        // Go directly to the batch campaign selection page
        $I->amOnPage('/s/contacts/batchCampaigns');

        // Wait for the form to load
        $I->waitForElementVisible('#lead_batch_add', 5);

        // Grab the options inside the dropdown
        $campaigns = $I->grabMultiple('#lead_batch_add option');

        // Assert that each campaign follows the format "name (id)"
        foreach ($campaigns as $campaign) {
            \PHPUnit\Framework\Assert::assertMatchesRegularExpression('/^.+ \(\d+\)$/', $campaign);
        }
    }

    public function testCategoryDisplay(\AcceptanceTester $I): void
    {
        // Go directly to the batch category selection page
        $I->amOnPage('/s/categories/batch/contact/view');

        // Wait for the form to load
        $I->waitForElementVisible('#lead_batch_add', 5);

        // Grab the options inside the dropdown
        $categories = $I->grabMultiple('#lead_batch_add option');

        // Assert that each category follows the format "name (id)"
        foreach ($categories as $category) {
            \PHPUnit\Framework\Assert::assertMatchesRegularExpression('/^.+ \(\d+\)$/', $category);
        }
    }

    public function testOwnerDisplay(\AcceptanceTester $I): void
    {
        // Go directly to the batch owner selection page
        $I->amOnPage('/s/contacts/batchOwners');

        // Wait for the dropdown to be visible
        $I->waitForElementVisible('#lead_batch_owner_addowner', 5);

        // Grab the owner options from the dropdown
        $owners = $I->grabMultiple('#lead_batch_owner_addowner option');

        // Assert that each owner follows the format "name (id)"
        foreach ($owners as $owner) {
            if (empty($owner)) {
                continue;
            }
            \PHPUnit\Framework\Assert::assertMatchesRegularExpression('/^.+ \(\d+\)$/', $owner);
        }
    }

    public function segmentDisplayTest(\AcceptanceTester $I): void
    {
        // Go directly to the batch segment selection page
        $I->amOnPage('/s/segments/batch/contact/view');

        // Wait for the form to load
        $I->waitForElementVisible('#lead_batch_add', 5);

        // Grab the options inside the dropdown
        $segments = $I->grabMultiple('#lead_batch_add option');

        // Assert that each segment follows the format "name (id)"
        foreach ($segments as $segment) {
            \PHPUnit\Framework\Assert::assertMatchesRegularExpression('/^.+ \(\d+\)$/', $segment);
        }
    }

    public function testStageDisplay(\AcceptanceTester $I): void
    {
        // Go directly to the batch stage selection page
        $I->amOnPage('/s/contacts/batchStages');

        // Wait for the form to load
        $I->waitForElementVisible('#lead_batch_stage_addstage', 5);

        // Grab the options inside the dropdown
        $stages = $I->grabMultiple('#lead_batch_stage_addstage option');

        // Assert that each stage follows the format "name (id)"
        foreach ($stages as $stage) {
            if (empty($stage)) {
                continue;
            }
            \PHPUnit\Framework\Assert::assertMatchesRegularExpression('/^.+ \(\d+\)$/', $stage);
        }
    }

    public function testPointGroupDisplay(\AcceptanceTester $I): void
    {
        // Go to the page where point groups are managed
        $I->amOnPage('/s/points/triggers/new');

        // Wait for the dropdown to be visible
        $I->waitForElementVisible('#pointtrigger_group_chosen', 5);

        // Grab the point group options from the dropdown
        $pointGroups = $I->grabMultiple('#pointtrigger_group_chosen option');

        // Assert that each point group follows the format "name (id)"
        foreach ($pointGroups as $pointGroup) {
            \PHPUnit\Framework\Assert::assertMatchesRegularExpression('/^.+ \(\d+\)$/', $pointGroup);
        }
    }

    public function testFormDisplay(\AcceptanceTester $I): void
    {
        // Go to the page where point groups are managed
        $I->amOnPage('/s/emails/new');

        // Wait for the select button to appear
        $I->waitForElementVisible(EmailsPage::$SELECT_SEGMENT_EMAIL, 5);

        // Click the "Select" button to choose the email type
        $I->click(EmailsPage::$SELECT_SEGMENT_EMAIL);

        // Wait for the dropdown to be visible
        $I->waitForElementVisible('#emailform_unsubscribeForm_chosen', 5);

        // Grab the point group options from the dropdown
        $I->click('#emailform_unsubscribeForm_chosen a.chosen-single');

        $I->click('#emailform_unsubscribeForm_chosen'); // Open dropdown

        // Grab all dropdown options
        $forms = $I->grabMultiple('#emailform_unsubscribeForm_chosen .chosen-results li');

        // Assert that each point group follows the format "name (id)"
        foreach ($forms as $form) {
            \PHPUnit\Framework\Assert::assertMatchesRegularExpression('/^.+ \(\d+\)$/', $form);
        }
    }
}
