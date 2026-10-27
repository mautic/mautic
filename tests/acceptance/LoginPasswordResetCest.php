<?php

declare(strict_types=1);

namespace Acceptance;

final class LoginPasswordResetCest
{
    public function testPasswordResetLinkFollowsPasswordInTabOrder(\AcceptanceTester $I): void
    {
        $I->amOnPage('/s/login');
        $I->waitForElementVisible('#username', \AcceptanceTester::TIMEOUT);
        $I->click('#username');
        $I->pressKey('#username', \Facebook\WebDriver\WebDriverKeys::TAB);
        $I->assertSame('password', $I->executeJS('return document.activeElement.id;'));
        $I->pressKey('#password', \Facebook\WebDriver\WebDriverKeys::TAB);
        $I->assertTrue($I->executeJS('return document.activeElement.classList.contains("form-field-toggle");'));
        $I->pressKey('.form-field-toggle', \Facebook\WebDriver\WebDriverKeys::TAB);
        $I->assertTrue($I->executeJS('return document.activeElement.matches(".form-fluid--password > a");'));
        $I->click('.form-fluid--password > a');
        $I->waitForElementVisible('#passwordreset_identifier', \AcceptanceTester::TIMEOUT);
        $I->seeCurrentUrlMatches('~/passwordreset$~');
    }
}
