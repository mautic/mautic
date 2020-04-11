<?php

namespace Step\Acceptance;

use Page\Acceptance\Login as LoginPage;

class Login extends \AcceptanceTester
{
    public function loginAsUser()
    {
        $I = $this;
        $I->amOnPage(LoginPage::$URL);
        $I->fillField(LoginPage::$username, 'admin');
        $I->fillField(LoginPage::$password, 'mautic');
        $I->click(LoginPage::$login);
    }
}
