<?php


class ContactCest
{
    public function _before(\Step\Acceptance\Login $I)
    {
        $I->loginAsUser();
        $I->amOnPage('/s/dashboard');
        $I->amOnPage('/s/contacts');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function viewContact(AcceptanceTester $I)
    {
        $I->click('//*[@id="leadTable"]/tbody/tr[1]/td[2]/a');
        $I->amOnPage('/s/contacts/view/1');
        $I->see('Penny Moore');
    }
}
