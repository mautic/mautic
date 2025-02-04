<?php

namespace Step\Acceptance;

use Page\Acceptance\ContactPage;

class CampaignStep extends \AcceptanceTester
{
    public function addContactsToCampaign()
    {
        $I = $this;
        $I->waitForElementVisible(ContactPage::$campaignsModalAddOption, 5); // Wait for the modal to appear
        $I->click(ContactPage::$campaignsModalAddOption); // Click into "Add to the following" option
        $I->click(ContactPage::$firstCampaignFromAddList); // Select the first campaign from the list
        $I->click(ContactPage::$campaignsModalSaveButton); // Click Save
    }
}
