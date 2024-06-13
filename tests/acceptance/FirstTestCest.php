<?php

class FirstTestCest
{
    public function loginSuccessfully(AcceptanceTester $I)
    {
        $I->amOnPage('/s/login'); // Navigate to the login page

        // Fill in login form with credentials
        $I->fillField('//*[@id="username"]', 'admin');
        $I->fillField('//*[@id="password"]', 'Maut1cR0cks!');
        $I->click('//*[@id="main"]/div/div[1]/div/div/div/form/button');

        // Assert that login was successful
        $I->see('Dashboard'); // Check that the user is on the dashboard page
    }
}
