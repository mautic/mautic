<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\Unit\IpLookup;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\IpLookup\IpinfodbLookup;

class IpinfodbLookupTest extends \PHPUnit\Framework\TestCase
{
    private $cacheDir = __DIR__.'/../../../../../../var/cache/test';

    public function testIpLookupSuccessful()
    {
        // Mock http connector
        $mockHttp = $this->createMock(Client::class);

        // Mock a successful response
        $mockResponse = new Response(200, [], '{"statusCode" : "OK","statusMessage" : "","ipAddress" : "192.30.252.131","countryCode" : "US","countryName" : "United States","regionName" : "California","cityName" : "San Francisco","zipCode" : "94107","latitude" : "37.7757","longitude" : "-122.395","timeZone" : "-08:00"}');

        $mockHttp->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $ipService = new IpinfodbLookup(null, null, $this->cacheDir, null, $mockHttp);

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
