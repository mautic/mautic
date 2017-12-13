<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticWebTestCase;

class CommonRepositoryTest extends MauticWebTestCase
{
    /**
     * @testdox Test that is:mine does not throw an exception due to bad DQL
     */
    public function testIsMineSearchCommandDoesntCauseExceptionDueToBadDQL()
    {
        $client = $this->getClient();

        $client->request('GET', 's/contacts?search=is:mine');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('is:mine', $client->getResponse()->getContent());
    }
}
