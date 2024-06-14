<?php

class FirstTestCest
{
    public function loginSuccessfully(AcceptanceTester $I)
    {
        $I->amOnPage('/s/login'); // Navigate to the login page

        // Fill in login form with credentials
        $I->fillField('#username', 'admin');
        $I->fillField('#password', 'Maut1cR0cks!');
        $I->click('button[type=submit]');

        // Assert that login was successful
        $I->see('Dashboard'); // Check that the user is on the dashboard page
    }
}
