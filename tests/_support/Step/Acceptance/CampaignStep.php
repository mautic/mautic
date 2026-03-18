<?php

namespace Step\Acceptance;

use Page\Acceptance\ContactPage;

class CampaignStep extends \AcceptanceTester
{
    private const MODAL_SELECTOR = '#MauticSharedModal';

    public function addContactsToCampaign(): int
    {
        $I = $this;
        $I->waitForElementVisible(ContactPage::$campaignsModalAddOption, 5); // Wait for the modal to appear
        $I->click(ContactPage::$campaignsModalAddOption); // Click into "Add to the following" option
        $I->waitForElementVisible(ContactPage::$firstCampaignFromAddList, 10);
        $selectedCampaignText = $I->grabTextFrom(ContactPage::$firstCampaignFromAddList);
        $I->click(ContactPage::$firstCampaignFromAddList);
        $I->click(ContactPage::$campaignsModalSaveButton); // Click Save
        $I->waitForElementNotVisible(self::MODAL_SELECTOR, 30); // Wait for modal to close
        $I->ensureNotificationAppears('2 contacts affected');

        preg_match('/\((\d+)\)\s*$/', $selectedCampaignText, $campaignIdMatch);

        return (int) ($campaignIdMatch[1] ?? 0);
    }
}
