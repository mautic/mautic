<?php

use DataObjects\ContactsDataObjects;
use Page\DashboardPage;
use Page\NewContactPage;

class ContactsCest
{
    private $contactPageObjects = null;

    public function _before(ContactsTester $I)
    {
        $I->loginToMautic();
        $this->contactPageObjects = NewContactPage::getContactPageObject();
    }

    public function _after(ContactsTester $I)
    {
    }

    // tests
    public function CreateAContactAllFieldsAndCompany(ContactsTester $I)
    {
        $I->wantTo('Create a contact with all fields and new company (Primary) from new button with points not set up');
        $currentContactId =$I->grabNumRecords('mautic_leads') + 1;
        $currentCompanyId =$I->grabNumRecords('mautic_companies') + 1;
        $I->amGoingTo('Open Contacts and click on New button');
        $I->amOnPage(DashboardPage::$URL);
        $I->click(DashboardPage::$ContactPage);
        $I->amOnPage('/s/contacts');
        $I->click($this->contactPageObjects['newButton']);
        $I->amOnPage(NewContactPage::$URL);

        $I->amGoingTo('Fill Contact Data');
        $newContact = new ContactsDataObjects();
        $newContact->fillContact($I, 'FL', 'US');

        $I->amGoingTo('Fill Contact Social Data');
        $I->click('Social');
        $I->wait(3);
        $I->waitForText('Facebook');
        $newContact->fillContactSocial($I);

        $I->amGoingTo('Fill New Company Data');
        $I->click('Core');
        $I->wait(5);
        $I->click($this->contactPageObjects['companyField']);
        $I->click($this->contactPageObjects['contactNewCompanyOption']);
        $I->waitForText('Company Name');
        $newContact->fillContactCompany($I, 'CA', 'US');

        $I->amGoingTo('Save Company and Contact');
        $I->click($this->contactPageObjects['saveCompanyButton']);
        $I->wait(5);
        $I->click($this->contactPageObjects['saveCloseButton']);

        $I->amGoingTo('Review all data contact core data is saved correctly with 0 points and New Company is primary');
        $I->wait(5);
        $I->amOnPage('s/contacts/view/'.$currentContactId);
        $I->waitForText('Engagements');
        $I->click('//a[@data-target="#lead-details"]');
        $newContact->verifyContact($I, 'Florida', 'United States', true, $currentCompanyId);

        $I->amGoingTo(' Verify data in Social is correct');
        $I->click('//a[@href="#social"]');
        $I->waitForText('Facebook');
        $newContact->verifyContactSocial($I);

        $I->amGoingTo('Verify new Company information is correct');
        $I->amOnPage('s/companies/edit/'.$currentContactId);
        $I->waitForText('Edit Company');
        $newContact->verifyContactCompany($I, 'California', 'United States');
    }
}
