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

use Mautic\CoreBundle\IpLookup\TelizeLookup;

/**
 * Class TelizeLookupTest.
 */
class TelizeLookupTest extends \PHPUnit_Framework_TestCase
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
        $mockResponse->body = '{"offset": "-4","longitude": -77.4875,"city": "Ashburn","timezone": "America/New_York","latitude": 39.0437,"area_code": "0","region": "Virginia","dma_code": "0","organization": "AS14618 Amazon.com, Inc.","country": "United States","ip": "54.86.225.32","country_code3": "USA","postal_code": "20147","continent_code": "NA","country_code": "US","region_code": "VA"}';

        $mockHttp->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $ipService = new TelizeLookup(null, null, __DIR__.'/../../../../cache/test', null, $mockHttp);

        $details = $ipService->setIpAddress('54.86.225.32')->getDetails();

        $this->assertEquals('Ashburn', $details['city']);
        $this->assertEquals('Virginia', $details['region']);
        $this->assertEquals('United States', $details['country']);
        $this->assertEquals('20147', $details['zipcode']);
        $this->assertEquals('39.0437', $details['latitude']);
        $this->assertEquals('-77.4875', $details['longitude']);
        $this->assertEquals('America/New_York', $details['timezone']);
        $this->assertEquals('AS14618 Amazon.com, Inc.', $details['organization']);
    }
}
