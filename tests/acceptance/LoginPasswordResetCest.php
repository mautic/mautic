<?php

declare(strict_types=1);

namespace Acceptance;

final class LoginPasswordResetCest
{
    public function testPasswordResetLinkCanBeClicked(\AcceptanceTester $I): void
    {
        $I->amOnPage('/s/login');
        $I->waitForElementVisible('.login-form .form-fluid__label a', \AcceptanceTester::TIMEOUT);
        $I->click('.login-form .form-fluid__label a');
        $I->waitForElementVisible('#passwordreset_identifier', \AcceptanceTester::TIMEOUT);
        $I->seeCurrentUrlMatches('~/passwordreset$~');
    }
}
