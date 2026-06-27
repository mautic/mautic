<?php

namespace Step\Acceptance;

use Page\Acceptance\CampaignPage;
use Page\Acceptance\ContactPage;

class CampaignStep extends \AcceptanceTester
{
    public function createCampaign(string $campaignName): void
    {
        $I = $this;
        $I->amOnPage(CampaignPage::URL);
        $I->waitForElementClickable(CampaignPage::NEW_BUTTON, self::TIMEOUT);
        $I->click(CampaignPage::NEW_BUTTON);
        $I->waitForElementVisible(CampaignPage::NAME_FIELD, self::TIMEOUT);
        $I->reloadPage(); // Temp fix: The CSRF token is invalid. Please try to resubmit the form.
        $I->waitForElementVisible(CampaignPage::NAME_FIELD, self::TIMEOUT);
        $I->fillField(CampaignPage::NAME_FIELD, $campaignName);
        $I->seeInField(CampaignPage::NAME_FIELD, $campaignName);
    }

    public function launchCampaignBuilder(): void
    {
        $I = $this;
        $I->waitForElementClickable('#campaign_buttons_builder_toolbar', self::TIMEOUT);
        $I->click('#campaign_buttons_builder_toolbar');
    }

    public function addContactSegmentSource(string $segmentName): void
    {
        $I = $this;
        $I->waitForElementClickable('#SourceList_chosen .chosen-results li.option_campaignLeadSource_lists', self::TIMEOUT);
        $I->click('#SourceList_chosen .chosen-results li.option_campaignLeadSource_lists');

        $I->waitForElementVisible('.bundle-form-header', self::TIMEOUT);
        $I->see('Contact Source', '.bundle-form-header h3');
        $I->waitForElementClickable(CampaignPage::CAMPAIGN_SOURCE_SEGMENT_PICKER, self::TIMEOUT);
        $I->click(CampaignPage::CAMPAIGN_SOURCE_SEGMENT_PICKER);

        $I->waitForElementVisible(CampaignPage::CAMPAIGN_SOURCE_SEGMENT_SEARCH, self::TIMEOUT);
        $I->fillField(CampaignPage::CAMPAIGN_SOURCE_SEGMENT_SEARCH, $segmentName);

        $resultSelector = $this->chosenResultByText('campaign_leadsource_lists_chosen', $segmentName);
        $I->waitForElementVisible(CampaignPage::CAMPAIGN_SOURCE_SEGMENT_RESULTS, self::TIMEOUT);
        $I->waitForElementVisible($resultSelector, self::TIMEOUT);
        $I->click($resultSelector);
        $I->waitForText($segmentName, self::TIMEOUT, CampaignPage::CAMPAIGN_SOURCE_SEGMENT_PICKER.' .search-choice');

        $I->waitForElementClickable(CampaignPage::CAMPAIGN_EVENT_MODAL_SAVE_BUTTON, self::TIMEOUT);
        $I->click(CampaignPage::CAMPAIGN_EVENT_MODAL_SAVE_BUTTON);
    }

    public function addAdjustContactPointsAction(int $points): void
    {
        $I = $this;
        $I->waitForElementClickable('.jtk-endpoint.CampaignEvent_lists.jtk-endpoint-anchor-leadsource', self::TIMEOUT);
        $I->click('.jtk-endpoint.CampaignEvent_lists.jtk-endpoint-anchor-leadsource');

        $I->waitForElementClickable('#CampaignEventPanel [data-type="Action"]', self::TIMEOUT);
        $I->click('#CampaignEventPanel [data-type="Action"]');
        $I->waitForElement('#ActionGroupList #ActionList option[value="lead.changepoints"]', self::TIMEOUT);
        $I->executeJS("mQuery('#ActionList').val('lead.changepoints').trigger('change');");

        $I->waitForElementVisible('.bundle-form-header', self::TIMEOUT);
        $I->see('Adjust contact points', '.bundle-form-header h3');
        $I->fillField('#campaignevent_properties_points', (string) $points);
        $I->waitForElementClickable(CampaignPage::CAMPAIGN_EVENT_MODAL_SAVE_BUTTON, self::TIMEOUT);
        $I->click(CampaignPage::CAMPAIGN_EVENT_MODAL_SAVE_BUTTON);
    }

    public function closeCampaignBuilder(): void
    {
        $I = $this;
        $I->waitForElementClickable('button[aria-label="Close Builder"]', self::TIMEOUT);
        $I->click('button[aria-label="Close Builder"]');
        $I->waitForElementNotVisible('#CampaignCanvas', self::TIMEOUT);
    }

    public function saveAndCloseCampaign(string $campaignName): void
    {
        $I = $this;
        $I->waitForElementClickable(CampaignPage::SAVE_AND_CLOSE_BUTTON, self::TIMEOUT);
        $I->click(CampaignPage::SAVE_AND_CLOSE_BUTTON);
        $I->waitForText($campaignName, self::TIMEOUT, 'h1.page-header-title');
        $I->see($campaignName, 'h1.page-header-title');
    }

    public function addContactsToCampaign(): int
    {
        $I = $this;
        $I->waitForElementVisible(ContactPage::$campaignsModalAddOption, self::TIMEOUT); // Wait for the modal to appear
        $I->click(ContactPage::$campaignsModalAddOption); // Click into "Add to the following" option
        $I->waitForElementVisible(ContactPage::$firstCampaignFromAddList, self::TIMEOUT);
        $selectedCampaignText = $I->grabTextFrom(ContactPage::$firstCampaignFromAddList);
        $I->click(ContactPage::$firstCampaignFromAddList);
        $I->click(ContactPage::$campaignsModalSaveButton); // Click Save
        $I->waitForElementNotVisible(CampaignPage::MODAL_SELECTOR, self::TIMEOUT); // Wait for modal to close
        $I->seeNotificationAppear('2 contacts affected');

        preg_match('/\((\d+)\)\s*$/', $selectedCampaignText, $campaignIdMatch);

        return (int) ($campaignIdMatch[1] ?? 0);
    }

    private function chosenResultByText(string $chosenId, string $text): string
    {
        return sprintf(
            '//*[@id=%s]//*[contains(concat(" ", normalize-space(@class), " "), " chosen-results ")]//li[contains(normalize-space(.), %s)]',
            $this->xpathLiteral($chosenId),
            $this->xpathLiteral($text),
        );
    }

    private function xpathLiteral(string $value): string
    {
        if (!str_contains($value, "'")) {
            return "'$value'";
        }

        if (!str_contains($value, '"')) {
            return "\"$value\"";
        }

        return 'concat(\''.str_replace("'", "', \"'\", '", $value).'\')';
    }
}
