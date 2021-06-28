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
use Mautic\CoreBundle\IpLookup\IpstackLookup;

class IpstackLookupTest extends \PHPUnit\Framework\TestCase
{
    private $cacheDir = __DIR__.'/../../../../../../var/cache/test';

    public function testIpLookupSuccessful()
    {
        // Mock http connector
        $mockHttp = $this->createMock(Client::class);

        // Mock a successful response
        $mockResponse = new Response(200, [], '{"ip":"192.30.252.131","country_code":"US","country_name":"United States","region_code":"CA","region_name":"California","city":"San Francisco","zip_code":"94107","time_zone":"America/Los_Angeles","latitude":37.7697,"longitude":-122.3933,"metro_code":807}');

        $mockHttp->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $ipService = new IpstackLookup('mockApiToken', null, $this->cacheDir, null, $mockHttp);

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
