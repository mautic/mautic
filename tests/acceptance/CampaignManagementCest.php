<?php

declare(strict_types=1);

use Page\Acceptance\CampaignPage;
use Step\Acceptance\CampaignStep;
use Step\Acceptance\SegmentStep;

class CampaignManagementCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->login();
    }

    public function viewCampaignList(AcceptanceTester $I): void
    {
        $I->amOnPage(CampaignPage::URL);
        $I->waitForText('Campaigns', AcceptanceTester::TIMEOUT, 'h1.page-header-title');
        $I->seeElement(CampaignPage::NEW_BUTTON);
    }

    public function createCampaign(CampaignStep $campaignStep, SegmentStep $segmentStep): void
    {
        $segmentName = sprintf('E2E Segment for Campaign %s', uniqid('', true));
        $segmentStep->createAContactSegment($segmentName);

        $campaignName = $this->campaignName('Create');

        $campaignStep->createCampaign($campaignName);
        $campaignStep->launchCampaignBuilder();
        $campaignStep->addContactSegmentSource($segmentName);
        $campaignStep->addAdjustContactPointsAction(10);
        $campaignStep->closeCampaignBuilder();
        $campaignStep->saveAndCloseCampaign($campaignName);
    }

    private function campaignName(string $action): string
    {
        return sprintf('E2E Campaign %s %s', $action, str_replace('.', '', uniqid('', true)));
    }
}
