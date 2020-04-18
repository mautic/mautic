<?php


class CompaniesCest
{
    public function _before(\Step\Acceptance\Login $I)
    {
        $I->loginAsUser(); // Check for existing session or log in
        $I->amOnPage('/s/dashboard'); // Go to dashboard
    }

    public function _after(AcceptanceTester $I)
    {
    }

    // tests
    public function createCompany(AcceptanceTester $I)
    {
        $I->amOnPage('/s/companies');
        $I->dontSee('Test Company'); // Check we don't already have a test company present
        $I->click('//*[@id="toolbar"]/div[1]/a/span/span'); // Click to create new button
        $I->wait(2);
        $I->fillField('//*[@id="company_companyname"]', 'Test Company');
        $I->fillField('//*[@id="company_companyemail"]', 'test@example.com');
        $I->fillField('//*[@id="company_companyaddress1"]', 'Address 1');
        $I->fillField('//*[@id="company_companyaddress2"]', 'Address 2');
        $I->fillField('//*[@id="company_companycity"]', 'City');
        $I->click('//*[@id="company_companystate_chosen"]'); // Click to show dropdown
        $I->fillField('//*[@id="company_companystate_chosen"]/div/div/input', 'California'); //Enter state
        $I->click('//*[@id="company_companystate_chosen"]/div/ul/li[2]'); // Select state
        $I->fillField('//*[@id="company_companyzipcode"]', 'CA12345');
        $I->click('//*[@id="company_companycountry_chosen"]');
        $I->fillField('//*[@id="company_companycountry_chosen"]/div/div/input', 'United States');
        $I->click('//*[@id="company_companycountry_chosen"]/div/ul/li');
        $I->fillField('//*[@id="company_companyphone"]', '+12345678901');
        $I->fillField('//*[@id="company_companywebsite"]', 'https://www.mautic.com');
        $I->click('//*[@id="company_owner_chosen"]');
        $I->click('//*[@id="company_owner_chosen"]/div/ul/li[2]');
        $I->click('//*[@id="app-content"]/div/form/div[1]/div[1]/div/ul/li[2]/a'); // Click to add professional fields
        $I->fillField('//*[@id="company_companynumber_of_employees"]', '599');
        $I->fillField('//*[@id="company_companyfax"]', '+12345678901');
        $I->fillField('//*[@id="company_companyannual_revenue"]', '1500000'); // Must be a numerical field with no commas
        $I->click('//*[@id="company_companyindustry_chosen"]');
        $I->fillField('//*[@id="company_companyindustry_chosen"]/div/div/input', 'Communications');
        $I->click('//*[@id="company_companyindustry_chosen"]/div/ul/li[1]/em');
        $I->fillField('//*[@id="company_companydescription"]', 'A test company');
        $I->click('//*[@id="company_buttons_save_toolbar"]'); // Save company
        $I->wait(2);
        $I->see('Test Company'); // Check we see test company in the companies list
    }

    public function editCompany(AcceptanceTester $I)
    {
        $I->amOnPage('/s/companies');
        $I->dontSee('Amazon Test'); // Check we haven't already run this test
        $I->click('//*[@id="companyTable"]/tbody/tr[1]/td[2]/div/a'); // Click on first company to edit
        $I->wait(2);
        $I->fillField('//*[@id="company_companyname"]', 'Amazon Test');
        $I->fillField('//*[@id="company_companyemail"]', 'test@example.com');
        $I->fillField('//*[@id="company_companyaddress1"]', 'Address 1');
        $I->fillField('//*[@id="company_companyaddress2"]', 'Address 2');
        $I->fillField('//*[@id="company_companycity"]', 'City');
        $I->click('//*[@id="company_companystate_chosen"]'); // Click to show dropdown
        $I->fillField('//*[@id="company_companystate_chosen"]/div/div/input', 'California'); //Enter state
        $I->click('//*[@id="company_companystate_chosen"]/div/ul/li[2]'); // Select state
        $I->fillField('//*[@id="company_companyzipcode"]', 'CA12345');
        $I->click('//*[@id="company_companycountry_chosen"]');
        $I->fillField('//*[@id="company_companycountry_chosen"]/div/div/input', 'United States');
        $I->click('//*[@id="company_companycountry_chosen"]/div/ul/li');
        $I->fillField('//*[@id="company_companyphone"]', '+12345678901');
        $I->fillField('//*[@id="company_companywebsite"]', 'https://www.mautic.com');
        $I->click('//*[@id="company_owner_chosen"]');
        $I->click('//*[@id="company_owner_chosen"]/div/ul/li[2]');
        $I->click('//*[@id="app-content"]/div/form/div[1]/div[1]/div/ul/li[2]/a'); // Click to add professional fields
        $I->fillField('//*[@id="company_companynumber_of_employees"]', '599');
        $I->fillField('//*[@id="company_companyfax"]', '+12345678901');
        $I->fillField('//*[@id="company_companyannual_revenue"]', '1500000'); // Must be a numerical field with no commas
        $I->click('//*[@id="company_companyindustry_chosen"]');
        $I->fillField('//*[@id="company_companyindustry_chosen"]/div/div/input', 'Communications');
        $I->click('//*[@id="company_companyindustry_chosen"]/div/ul/li[1]/em');
        $I->fillField('//*[@id="company_companydescription"]', 'Testing Editing Company');
        $I->click('//*[@id="company_buttons_save_toolbar"]'); // Save company
        $I->wait(2);
        $I->see('Amazon Test');
    }
}
