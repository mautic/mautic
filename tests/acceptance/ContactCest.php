<?php


class ContactCest
{
    public function _before(\Step\Acceptance\Login $I)
    {
        $I->loginAsUser();
        $I->amOnPage('/s/dashboard');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function viewContact(AcceptanceTester $I)
    {
        $I->amOnPage('/s/contacts');
        $leadName = $I->grabTextFrom('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a/div[1]');
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a');
        $I->see($leadName);
    }
}
