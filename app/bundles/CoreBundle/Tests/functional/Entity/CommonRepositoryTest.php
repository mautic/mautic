<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\functional\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class CommonRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @testdox Test that is:mine does not throw an exception due to bad DQL
     */
    public function testIsMineSearchCommandDoesntCauseExceptionDueToBadDQL()
    {
        $this->client->request('GET', 's/contacts?search=is:mine');

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertContains('is:mine', $this->client->getResponse()->getContent());
    }
}
