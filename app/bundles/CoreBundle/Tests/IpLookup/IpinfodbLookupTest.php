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

use Mautic\CoreBundle\IpLookup\IpinfodbLookup;

/**
 * Class IpinfodbLookupTest.
 */
class IpinfodbLookupTest extends \PHPUnit_Framework_TestCase
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
        $mockResponse->body = '{"statusCode" : "OK","statusMessage" : "","ipAddress" : "192.30.252.131","countryCode" : "US","countryName" : "United States","regionName" : "California","cityName" : "San Francisco","zipCode" : "94107","latitude" : "37.7757","longitude" : "-122.395","timeZone" : "-08:00"}';

        $mockHttp->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $ipService = new IpinfodbLookup(null, null, __DIR__.'/../../../../cache/test', null, $mockHttp);

        $details = $ipService->setIpAddress('192.30.252.131')->getDetails();

        $this->assertEquals('San Francisco', $details['city']);
        $this->assertEquals('California', $details['region']);
        $this->assertEquals('United States', $details['country']);
        $this->assertEquals('94107', $details['zipcode']);
        $this->assertEquals('37.7757', $details['latitude']);
        $this->assertEquals('-122.395', $details['longitude']);
        $this->assertEquals('-08:00', $details['timezone']);
    }
}
