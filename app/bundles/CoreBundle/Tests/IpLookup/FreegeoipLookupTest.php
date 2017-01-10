<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\IpLookup;

use Mautic\CoreBundle\IpLookup\FreegeoipLookup;

/**
 * Class FreegeoipLookupTest.
 */
class FreegeoipLookupTest extends \PHPUnit_Framework_TestCase
{
    public function testIpLookupSuccessful()
    {
        // Mock http connector
        $mockHttp = $this->getMockBuilder('Joomla\Http\Http')
            ->disableOriginalConstructor()
            ->getMock();

        // Mock a successful response
        $mockResponse = $this->getMockBuilder('Joomla\Http\Response')
            ->getMock();
        $mockResponse->code = 200;
        $mockResponse->body = '{"ip":"192.30.252.131","country_code":"US","country_name":"United States","region_code":"CA","region_name":"California","city":"San Francisco","zip_code":"94107","time_zone":"America/Los_Angeles","latitude":37.7697,"longitude":-122.3933,"metro_code":807}';

        $mockHttp->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $ipService = new FreegeoipLookup(null, null, __DIR__.'/../../../../cache/test', null, $mockHttp);

        $details = $ipService->setIpAddress('192.30.252.131')->getDetails();

        $this->assertEquals('San Francisco', $details['city']);
        $this->assertEquals('California', $details['region']);
        $this->assertEquals('United States', $details['country']);
        $this->assertEquals('94107', $details['zipcode']);
        $this->assertEquals('37.7697', $details['latitude']);
        $this->assertEquals('-122.3933', $details['longitude']);
        $this->assertEquals('America/Los_Angeles', $details['timezone']);
    }
}
