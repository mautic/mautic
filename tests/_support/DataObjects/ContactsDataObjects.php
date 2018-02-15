<?php

namespace DataObjects;

use Codeception\Util\Debug;
use Page\NewContactPage;

class ContactsDataObjects
{
    public $contact              = null;
    public $social               = null;
    public $company              = null;
    protected $contactPageObject = null;

    public function __construct()
    {
        $this->contactPageObject = NewContactPage::getContactPageObject();

        $this->contact = [
            'lead[title]'       => 'Mr.',
            'lead[firstname]'   => 'New Button',
            'lead[lastname]'    => 'Contact',
            'lead[email]'       => 'newbuttoncontact@mailinator.com',
            'lead[position]'    => 'CTO',
            'lead[address1]'    => 'Contact Address line 1',
            'lead[address2]'    => 'Contact Address line 2',
            'lead[city]'        => 'Orlando',
            'lead[zipcode]'     => '33195',
            'lead[attribution]' => '150',
            'lead[mobile]'      => '3059999999',
            'lead[phone]'       => '3058888888',
            'lead[fax]'         => '3057777777',
            'lead[website]'     => 'www.newbuttoncontact.com',
        ];
        $this->social = ['lead[facebook]' => 'fb.com',
            'lead[foursquare]'            => 'f4.com',
            'lead[googleplus]'            => 'gplus.com',
            'lead[instagram]'             => 'ig.com',
            'lead[skype]'                 => 'skype.com',
            'lead[twitter]'               => 'twt.com', ];

        $this->company = ['company[companyname]' => 'New Button Company',
            'company[companyemail]'              => 'newbuttoncompany@mailinator.com',
            'company[companyaddress1]'           => 'Company Address 1',
            'company[companyaddress2]'           => 'Company Address 2',
            'company[companycity]'               => 'Miami',
            'company[companyzipcode]'            => '33178',
            'company[companyphone]'              => '3055555555',
            'company[companywebsite]'            => 'www.newbuttoncompany.com',
        ];
    }

    public function _2NoCompany10Points()
    {
        $this->contact = [
            'lead[title]'     => 'Mrs.',
            'lead[firstname]' => 'No Company',
            'lead[lastname]'  => 'Contact',
            'lead[email]'     => 'nocompanycontact@mailinator.com',
            'lead[position]'  => 'CPA',
            'lead[address1]'  => 'ABC Lane 1',
            'lead[address2]'  => 'ABC Lane 2',
            'lead[city]'      => 'Faux',
            'lead[zipcode]'   => '33198',
            'lead[mobile]'    => '3059999998',
            'lead[phone]'     => '3058888887',
            'lead[fax]'       => '3057777776',
            'lead[website]'   => 'www.nocompanycontact.com',
            'lead[points]'    => '10',
        ];
        $this->company = null;
    }

    public function _3ExistingCompany()
    {
        $this->contact = [
            'lead[title]'     => 'Mr.',
            'lead[firstname]' => 'Existing Company',
            'lead[lastname]'  => 'Jones',
            'lead[email]'     => 'existingcompany@mailinator.com',
            'lead[position]'  => 'ABC',
            'lead[address1]'  => 'ABC Lane 1',
            'lead[address2]'  => 'ABC Lane 2',
            'lead[city]'      => 'Faux',
            'lead[zipcode]'   => '33198',
            'lead[mobile]'    => '3059999998',
            'lead[phone]'     => '3058888887',
            'lead[fax]'       => '3057777776',
            'lead[website]'   => 'www.existingcompanycontact.com',
        ];
        $this->company = ['company[companyname]' => 'New Button Company'];
    }

    public function verifyImportContacts(\ContactsTester $I, $contactId, $companyId)
    {
        $I->amGoingTo('Verify all contacts and companies are created');
        $this->verifyContactCSV($I, $contactId, 'Stern', 'Caddie', 'scaddie0@theglobeandmail.com', 'Voonix', $companyId);
        $this->verifyContactCSV($I, $contactId + 1, 'Lalo', 'Santore', '', 'Blognation', $companyId + 1);
        $this->verifyContactCSV($I, $contactId + 2, 'Edward', '', '', 'Thoughtblab', $companyId + 2);
        $this->verifyContactCSV($I, $contactId + 3, '', '', '', 'Kaymbo', $companyId + 3);
        $this->verifyContactCSV($I, $contactId + 4, 'Diandra', '', 'dmoncrieffe4@mediafire.com', 'Vipe', $companyId + 4);
        $this->verifyContactCSV($I, $contactId + 5, '', 'Louden', 'wlouden5@house.gov', 'Jaxspan', $companyId + 5);
        $this->verifyContactCSV($I, $contactId + 6, '', '', 'laubery6@w3.org', 'Shuffledrive', $companyId + 6);
        $this->verifyContactCSV($I, $contactId + 7, 'Minnnie', 'Drinkhall', 'mdrinkhall7@earthlink.net', '', '');
        $this->verifyContactCSV($I, $contactId + 8, 'Lari', 'Frankling', '', '', '');
        $this->verifyContactCSV($I, $contactId + 9, 'Walden', '', '', '', '');
        $this->verifyContactCSV($I, $contactId + 10, 'Sheppard', '', 'smacdermida@goodreads.com', '', '');
        $this->verifyContactCSV($I, $contactId + 11, '', 'Osgardby', 'cosgardbyb@mysql.com', '', '');
        $this->verifyContactCSV($I, $contactId + 12, '', '', 'fbaackc@pagesperso-orange.fr', '', '');
        $this->verifyContactCSV($I, $contactId + 13, 'Carolyn', 'Ivannikov', 'civannikovd@boston.com', 'Tavu', $companyId + 7);
        $this->verifyContactCSV($I, $contactId + 14, 'Linoel', 'Jee', 'ljeee@people.com.cn', 'Meemm', $companyId + 8);
        $this->verifyContactCSV($I, $contactId + 15, 'Dorice', 'Wahner', 'dwahnerf@who.int', '', '');
        $this->verifyContactCSV($I, $contactId + 16, 'Keene', 'Wenzel', 'kwenzelg@virginia.edu', '', '');
        $this->verifyContactCSV($I, $contactId + 17, 'Zola', 'Cattemull', 'zcattemullh@pagesperso-orange.fr', '', '');
        $this->verifyContactCSV($I, $contactId + 18, 'Felizio', 'Hurich', 'fhurichi@com.com', 'Blogspan', $companyId + 9);
        $this->verifyContactCSV($I, $contactId + 19, 'Jahn', 'Duck', 'dduck@bloggy.com', 'Blognation', $companyId + 1);

        $this->verifyCompaniesCSV($I, $companyId, 'Voonix', 'scaddie0@zdnet.com', 'http://statcounter.com');
        $this->verifyCompaniesCSV($I, $companyId + 1, 'Blognation', 'johnny@blognation.com', 'http://columbia.edu');
        $this->verifyCompaniesCSV($I, $companyId + 2, 'Thoughtblab', 'ewardington2@imdb.com', 'http://gizmodo.com');
        $this->verifyCompaniesCSV($I, $companyId + 3, 'Kaymbo', 'amagor3@stanford.edu', 'http://multiply.com');
        $this->verifyCompaniesCSV($I, $companyId + 4, 'Vipe', 'dmoncrieffe4@prnewswire.com', 'http://hexun.com');
        $this->verifyCompaniesCSV($I, $companyId + 5, 'Jaxspan', 'wlouden5@google.ru', 'http://google.pl');
        $this->verifyCompaniesCSV($I, $companyId + 6, 'Shuffledrive', 'laubery6@prnewswire.com', 'http://unicef.org');
        $this->verifyCompaniesCSV($I, $companyId + 7, 'Tavu', 'civannikovd@dion.ne.jp', '');
        $this->verifyCompaniesCSV($I, $companyId + 8, 'Meemm', '', '');
        $this->verifyCompaniesCSV($I, $companyId + 9, 'Blogspan', '', 'http://abcd.la');
    }

    public function verifyContactCSV(\ContactsTester $I, $id, $first, $last, $email, $company, $companyId)
    {
        codecept_debug('ContactID is:'.$id);
        codecept_debug('CompanyId is:'.$companyId);
        $I->amOnPage('s/contacts/view/'.$id);
        $I->waitForText('Engagements');
        if ($first != '') {
            $I->canSee($first);
        }
        if ($last != '') {
            $I->canSee($last);
        }
        if ($email != '') {
            $I->canSee($email);
        }
        if ($company != '') {
            $I->canSee($company);
            $I->assertEquals($I->grabAttributeFrom('//*[@id="company-'.$companyId.'"]', 'class'), 'fa fa-check primary');
        }
    }

    public function verifyCompaniesCSV(\ContactsTester $I, $id, $name, $email, $website)
    {
        $I->amOnPage('s/companies/edit/'.$id);
        if ($name != '') {
            $I->canSeeInField('company[companyname]', $name);
        }
        if ($email != '') {
            $I->canSeeInField('company[companyemail]', $email);
        }
        if ($website != '') {
            $I->canSeeInField('company[companywebsite]', $website);
        }
    }

    public function noWebsiteContact()
    {
        unset($this->contact['lead[website]']);
    }

    public function verifyContact(\ContactsTester $I, $state, $country, $primaryCompany, $companyId)
    {
        foreach ($this->contact as $key => $data) {
            $I->canSee($data);
        }
        if ($this->company != null) {
            $I->canSee($this->company['company[companyname]']);
            if ($primaryCompany) {
                $I->assertEquals($I->grabAttributeFrom('//*[@id="company-'.$companyId.'"]', 'class'), 'fa fa-check primary');
            } else {
                $I->assertEquals($I->grabAttributeFrom('//*[@id="company-'.$companyId.'"]', 'class'), 'fa fa-check');
            }
        }

        $I->canSee($state);
        $I->canSee($country);
    }

    public function verifyContactSocial(\ContactsTester $I)
    {
        foreach ($this->social as $key => $data) {
            $I->canSee($data);
        }
    }

    public function verifyContactCompany(\ContactsTester $I, $state, $country)
    {
        foreach ($this->company as $key => $data) {
            if ($key == 'company[companywebsite]') {
                $text = (substr($data, 0, 5) != 'http:') ? 'http://'.$data : $data;
                $I->canSeeInField($key, $text);
            } else {
                $I->canSeeInField($key, $data);
            }
        }
        $I->canSee($state);
        $I->canSee($country);
    }

    public function fillContact(\ContactsTester $I, $state, $country)
    {
        foreach ($this->contact as $key => $data) {
            $I->fillField($key, $data);
        }
        $this->fillContactState($I, $state);
        $this->fillContactCountry($I, $country);
    }

    public function fillContactSocial(\ContactsTester $I)
    {
        foreach ($this->social as $key => $data) {
            $I->fillField($key, $data);
        }
    }

    public function fillContactCompany(\ContactsTester $I, $state, $country)
    {
        foreach ($this->company as $key => $data) {
            $I->fillField($key, $data);
        }
        $this->fillContactCompanyState($I, $state);
        $this->fillContactCompanyCountry($I, $country);
    }

    private function fillContactState(\ContactsTester $I, $state)
    {
        Debug::debug($this->contactPageObject);
        if (isset($state)) {
            $I->click($this->contactPageObject['contactStateField']);

            if ($state == 'CA') {
                $I->click(str_replace('$', '6', $this->contactPageObject['contactStateOption']));
            }
            if ($state == 'FL') {
                $I->click(str_replace('$', '10', $this->contactPageObject['contactStateOption']));
            }
            if ($state == 'MA') {
                $I->click(str_replace('$', '22', $this->contactPageObject['contactStateOption']));
            }
        }
    }

    private function fillContactCountry(\ContactsTester $I, $country)
    {
        if (isset($country)) {
            $I->click($this->contactPageObject['contactCountryField']);
            $I->click(str_replace('$', '184', $this->contactPageObject['contactCountryOption']));
        }
    }

    private function fillContactCompanyState(\ContactsTester $I, $state)
    {
        if (isset($state)) {
//            $I->click($this->contactPageObject['companyStateField']);
            if ($state == 'CA') {
                $I->fillField('company[companystate]', 'adsfadfa');
                $I->fillField('company[companystate]', 'California');
//                $I->click(str_replace('$', '6', $this->contactPageObject['companyStateOption']));
            }
            if ($state == 'FL') {
                $I->fillField('company[companystate]', 'Florida');
//                $I->click(str_replace('$', '10', $this->contactPageObject['companyStateOption']));
            }
            if ($state == 'MA') {
                $I->fillField('company[companystate]', 'Massachusetts');
//                $I->click(str_replace('$', '22', $this->contactPageObject['companyStateOption']));
            }
        }
    }

    private function fillContactCompanyCountry(\ContactsTester $I, $country)
    {
        if (isset($country)) {
            $I->click($this->contactPageObject['companyCountryField']);
            $I->click(str_replace('$', '184', $this->contactPageObject['companyCountryOption']));
        }
    }
}
