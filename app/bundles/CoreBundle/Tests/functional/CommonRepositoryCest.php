<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle;

class CommonRepositoryCest
{
    /**
     * @testdox Test that is:mine does not throw an exception due to bad DQL
     */
    public function ensureIsMineSearchCommandDoesntCauseExceptionDueToBadDQL(FunctionalTester $I)
    {
        $I->amHttpAuthenticated('admin', 'mautic');
        $I->amOnPage('/s/contacts/');
        $I->sendAjaxGetRequest('/s/contacts/?search=is:mine');
        $I->seeResponseCodeIs(200);
        $I->canSeeInSource('is:mine');
    }
}
