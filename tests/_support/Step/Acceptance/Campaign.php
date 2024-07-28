<?php

namespace Step\Acceptance;

class Campaign extends \AcceptanceTester
{
    public function selectContactFromContactsTab($place)
    {
        $I = $this;
        $I->click("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/button");
        $I->waitForElementVisible("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/ul/li[2]/a", 30);
        $I->click("//*[@id='leadTable']/tbody/tr[$place]/td[1]/div/div/ul/li[2]/a");
    }
}
