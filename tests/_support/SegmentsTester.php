<?php

use Page\DashboardPage;
use Page\LoginPage;

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
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = NULL)
 *
 * @SuppressWarnings(PHPMD)
 */
class SegmentsTester extends \Codeception\Actor
{
    use _generated\SegmentsTesterActions;

    /**
     * Define custom actions here.
     */
    public function loginToMautic()
    {
        $I = $this;
        // if snapshot exists - skipping login
        if ($I->loadSessionSnapshot('login')) {
            return;
        }
        $I->amOnPage(LoginPage::$URL);
        $I->fillField(LoginPage::$username, getenv('MAUTIC_ADMIN_USERNAME'));
        $I->fillField(LoginPage::$password, getenv('MAUTIC_ADMIN_PASSWORD'));
        $I->click(LoginPage::$login);
        $I->canSeeInCurrentUrl(getenv('MAUTIC_ENV').DashboardPage::$URL);

        $I->saveSessionSnapshot('login');
    }
}
