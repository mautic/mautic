<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\MonitoredEmail;

use Mautic\EmailBundle\MonitoredEmail\Processor\Address;

class AddressTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @testdox Test that an email header with email addresses are parsed into array
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Address::parseList()
     */
    public function testArrayOfAddressesAreReturnedFromEmailHeader()
    {
        $results = Address::parseList('<user@test.com>,<user2@test.com>');

        $this->assertEquals(
            [
                'user@test.com'  => null,
                'user2@test.com' => null,
            ],
            $results
        );
    }

    /**
     * @testdox Obtain hash ID from a special formatted email address
     *
     * @covers  \Mautic\EmailBundle\MonitoredEmail\Processor\Address::parseList()
     */
    public function testStatHashIsParsedFromEmail()
    {
        $hash = Address::parseAddressForStatHash('hello+bounce_123abc@test.com');

        $this->assertEquals('123abc', $hash);
    }
}
