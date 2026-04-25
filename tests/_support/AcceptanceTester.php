<?php

/**
 * Inherited Methods.
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method void pause()
 *
 * @SuppressWarnings(PHPMD)
 */
class AcceptanceTester extends Codeception\Actor
{
    use _generated\AcceptanceTesterActions;

    public const TIMEOUT = 30;

    public function login(string $name = 'admin', string $password = 'Maut1cR0cks!'): void
    {
        $I = $this;
        // if snapshot exists - skipping login
        if ($I->loadSessionSnapshot('login')) {
            return;
        }
        // logging in
        $I->amOnPage('/s/login');
        $I->fillField('#username', $name);
        $I->fillField('#password', $password);
        $I->click('button[type=submit]');
        $I->waitForElement('h1.page-header-title', self::TIMEOUT);
        // saving snapshot
        $I->saveSessionSnapshot('login');
    }

    /**
     * Ensures that a notification appears after an action and contains the expected text.
     */
    public function seeNotificationAppear(string $message): void
    {
        $this->waitForElementVisible('#flashes .alert', self::TIMEOUT);
        $this->see($message, '#flashes .alert');
    }
}
