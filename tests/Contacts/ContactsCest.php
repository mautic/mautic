<?php

use DataObjects\ContactsDataObjects;
use Page\DashboardPage;
use Page\NewContactPage;

class ContactsCest
{
    private $contactPageObjects = null;
    private $isInitialized      = false;

    public function _before(ContactsTester $I)
    {
        if (!$this->isInitialized) {
            $this->isInitialized=true;
            $I->FillInitialData();
        }
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
        $I->canSeeNumRecords(2, 'mautic_lead_lists');
        $I->canSeeNumRecords(1, 'mautic_campaigns');
        $currentContactId = $I->grabNumRecords('mautic_leads') + 1;
        $currentCompanyId = $I->grabNumRecords('mautic_companies') + 1;
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

    public function CreateAContactNoCompany10Points(ContactsTester $I)
    {
        $I->wantTo('Create a contact with all fields and no company from new button with 10 points set up');
        $I->canSeeNumRecords(2, 'mautic_lead_lists');
        $I->canSeeNumRecords(1, 'mautic_campaigns');
        $I->amGoingTo('Open Contacts and click on create new');
        $I->amOnPage(DashboardPage::$URL);
        $I->click(DashboardPage::$ContactPage);
        $I->amOnPage('/s/contacts');
        $currentContactId = $I->grabNumRecords('mautic_leads') + 1;

        $I->click($this->contactPageObjects['newButton']);
        $I->amOnPage(NewContactPage::$URL);

        $I->amGoingTo('Fill Contact core data');
        $newContact = new ContactsDataObjects();
        $newContact->_2NoCompany10Points();
        $newContact->fillContact($I, 'FL', 'US');
        $I->wait(5);
        $I->click($this->contactPageObjects['saveCloseButton']);

        $I->amGoingTo('Verify all data is entered correctly without a company and with 10 points');
        $I->wait(5);
        $I->amOnPage('s/contacts/view/'.$currentContactId);
        $I->waitForText('Engagements');
        $I->click('//a[@data-target="#lead-details"]');
        $newContact->verifyContact($I, 'Florida', 'United States', null, null);
    }

    /**
     * @depends CreateAContactAllFieldsAndCompany
     */
    public function CreateAContactWithExistingCompany(ContactsTester $I)
    {
        $I->wantTo('Create a contact with existing company');

        $I->amGoingTo('Open Contacts and click on create new');
        $I->amOnPage(DashboardPage::$URL);
        $I->click(DashboardPage::$ContactPage);
        $I->amOnPage('/s/contacts');
        $currentContactId = $I->grabNumRecords('mautic_leads') + 1;
        $currentCompanyId = $I->grabNumRecords('mautic_companies');
        $I->click($this->contactPageObjects['newButton']);
        $I->amOnPage(NewContactPage::$URL);

        $I->amGoingTo('Fill Contact Data');
        $newContact = new ContactsDataObjects();
        $newContact->_3ExistingCompany();
        $newContact->fillContact($I, 'FL', 'US');

        $I->amGoingTo('Select "New Button Company"');
        $I->click($this->contactPageObjects['companyField']);
        $I->click(str_replace('$', '2', $this->contactPageObjects['contactCompanyOption']));
        $I->wait(5);
        $I->click($this->contactPageObjects['saveCloseButton']);

        $I->amGoingTo('Review all data contact core data is saved correctly with 0 points and Existing Company is primary');
        $I->wait(5);
        $I->amOnPage('s/contacts/view/'.$currentContactId);
        $I->waitForText('Engagements');
        $I->click('//a[@data-target="#lead-details"]');
        $newContact->verifyContact($I, 'Florida', 'United States', true, $currentCompanyId);
    }

    public function ImportContactsFromCsv(ContactsTester $I)
    {
        $I->wantTo('Create contacts and companies by importing a CSV');

        $I->amGoingTo('Open Contacts and click on create new');
        $I->amOnPage(DashboardPage::$URL);
        $I->click(DashboardPage::$ContactPage);
        $I->amOnPage('/s/contacts');
        $I->click($this->contactPageObjects['dropdownMenu']);
        $I->click($this->contactPageObjects['importButton']);
        $I->waitForText('Import');
        $I->attachFile('lead_import[file]', 'importcompany.csv');
        $I->click('lead_import[start]');
        $I->waitForText('Import contacts');
        $I->click('//*[@id="lead_field_import_buttons_save_toolbar"]');
        $I->wait(15);
        $I->canSee('Success');
        $I->canSee('20 created,');
        $newContact       = new ContactsDataObjects();
        $currentContactId = $I->grabNumRecords('mautic_leads') - 19;
        $currentCompanyId = $I->grabNumRecords('mautic_companies') - 9;
        codecept_debug('Initial values contact:'.$currentContactId);
        codecept_debug('Initial values company:'.$currentCompanyId);
        $newContact->verifyImportContacts($I, $currentContactId, $currentCompanyId);
    }

    public function filterContacts(ContactsTester $I)
    {
        $I->wantTo('Use filters to find contacts');
        $I->amOnPage('s/contacts');
        $I->fillField('//*[@id="list-search"]', 'Contact');
        $I->wait(4);
        $I->canSee('New Button Contact');
        $I->canSee('No Company Contact');
        $I->canSee('2 items,');
        $I->click('//*[@id="btn-filter"]/i');
    }

    public function changeSegment(ContactsTester $I)
    {
        $I->wantTo('Change a contacts segment');
        $I->amOnPage('/s/contacts');
        $I->wait(2);
        $I->checkOption('cb1');
        $I->checkOption('cb2');
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button/i');
        $I->click('Change Segments');
        $I->click('//*[@id="lead_batch_add_chosen"]/ul');
        $I->click('//*[@id="lead_batch_add_chosen"]/div/ul/li[2]');
        $I->wait(3);
        $I->click(\Page\ModalPage::$SaveButton);
        $I->runShellCommand('php app/console mautic:segments:update');
        $I->wait(12);
        $I->click(DashboardPage::$SegmentsPage);
        $I->waitForText('Contact Segments');
        $I->click('View 2 Contacts');
        $I->wait(3);
        $I->canSee('New Button Contact');
        $I->canSee('No Company Contact');
        $I->click('//*[@id="btn-filter"]/i');
    }

    public function changeCampaign(ContactsTester $I)
    {
        $I->wantTo('Change a contacts campaign');
        $I->amOnPage('/s/contacts');
        $I->wait(2);
        $I->checkOption('cb3');
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button/i');
        $I->click('Change Campaigns');
        $I->click('//*[@id="lead_batch_add_chosen"]/ul');
        $I->click('//*[@id="lead_batch_add_chosen"]/div/ul/li[1]');
        $I->click(\Page\ModalPage::$SaveButton);
        $I->runShellCommand('php app/console mautic:campaigns:update');
        $I->runShellCommand('php app/console mautic:campaigns:trigger');
        $I->amOnPage('/s/contacts/view/3');
        $I->canSee('25 points');
        $I->canSee('Campaign action triggered');
        $I->canSee('1: Add points campaign / 25');
    }

    public function setToDoNotContact(ContactsTester $I)
    {
        $I->wantTo('Set Contact to DoNotContact');
        $I->amOnPage('/s/contacts');
        $I->checkOption('cb4');
        $I->click('//*[@id="leadTable"]/thead/tr/th[1]/div/div/button/i');
        $I->click('Set Do Not Contact');
        $I->waitForText('Reason');
        $I->fillField('lead_batch_dnc[reason]', 'No reason');
        $I->click(\Page\ModalPage::$SaveButton);
        $I->amOnPage('/s/contacts/view/4');
        $I->wait(6);
        $I->canSee('Do Not Contact');
    }

    public function sendEmailToDoNotContact(ContactsTester $I)
    {
        //Todo implement https://github.com/WhatDaFox/Codeception-Mailtrap
    }

    public function editContact(ContactsTester $I)
    {
        $I->wantTo('Edit a Contact');
        $I->amOnPage('/s/contacts');
        $I->checkOption('cb5');
        $I->click('//*[@id="leadTable"]/tbody/tr[5]/td[1]/div/div/button/i');
        $I->click('//*[@id="leadTable"]/tbody/tr[5]/td[1]/div/div/ul/li[1]/a/span/i');
        $I->waitForText('Preferred profile image');
        $I->fillField('lead[lastname]', 'Edited Last');
        $I->click('Save & Close');
        $I->waitForText('Engagements');
        $I->canSee('Edited Last');
    }

    public function removeContact(ContactsTester $I)
    {
        $I->wantTo('Delete a Contact');
        $I->amOnPage('/s/contacts');
        $I->checkOption('cb6');
        $I->click('//*[@id="leadTable"]/tbody/tr[6]/td[1]/div/div/button/i');
        $I->click('//*[@id="leadTable"]/tbody/tr[6]/td[1]/div/div/ul/li[2]/a/span/span');
        $I->wait(2);
        $I->click('//button[text()][2]');
        $I->amOnPage('/s/contacts/view/6');
        $I->canSee('No contact with an id of 6 was found!');
    }

    public function quickAddContact(ContactsTester $I)
    {
        $I->wantTo('Add a contact via QuickAdd');
        $currentContactId = $I->grabNumRecords('mautic_leads') + 1;
        $I->amOnPage('/s/contacts');
        $I->click('Quick Add');
        $I->wait(3);
        $I->fillField('lead[firstname]', 'QuickAdd');
        $I->fillField('lead[lastname]', 'Contact');
        $I->fillField('lead[email]', 'quick@mailinator.com');
        $I->click('//div[@class="modal-form-buttons"]/button[2]');
        $I->amOnPage('/s/contacts/view/'.$currentContactId);
        $I->wait(6);
        $I->canSee('Quick Add');
        $I->canSee('Contact');
        $I->canSee('quick@mailinator.com');
    }

    public function allTypesContactCustomFields(ContactsTester $I)
    {
    }
}
