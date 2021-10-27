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
use Mautic\CoreBundle\IpLookup\GeoipsLookup;

class GeoipsLookupTest extends \PHPUnit\Framework\TestCase
{
    private $cacheDir = __DIR__.'/../../../../../../var/cache/test';

    public function testIpLookupSuccessful()
    {
        // Mock http connector
        $mockHttp = $this->createMock(Client::class);

        // Mock a successful response
        $mockResponse = new Response(200, [], '{"response": {"status": "Propper Request","message": "Success","notes": "The following results has been returned","code": "200_1","location": {"ip": "8.8.8.8","owner": "LEVEL 3 COMMUNICATIONS INC","continent_name": "NORTH AMERICA","continent_code": "NA","country_name": "UNITED STATES","country_code": "US","region_name": "CALIFORNIA","region_code": "CA","county_name": "SANTA CLARA","city_name": "MOUNTAIN VIEW","latitude": "37.3801","longitude": "-122.0865","timezone": "PST"},"languages": {"language": [{"alpha2_code": "EN","alpha3_code": "ENG","language_name": "ENGLISH","native_name": "ENGLISH"}]},"currencies": {"currency": [{"alpha_code": "USD","numeric_code": "840","currency_name": "US DOLLAR","currency_symbol": "$","fractional_unit": "CENT"}]},"unit_test": {"elapsed_time": "0.0609","memory_usage": "0.76MB"}}}');

        $mockHttp->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);

        $ipService = new GeoipsLookup(null, null, $this->cacheDir, null, $mockHttp);

        $details = $ipService->setIpAddress('192.30.252.131')->getDetails();

        $this->assertEquals('MOUNTAIN VIEW', $details['city']);
        $this->assertEquals('CALIFORNIA', $details['region']);
        $this->assertEquals('UNITED STATES', $details['country']);
        $this->assertEquals('', $details['zipcode']);
        $this->assertEquals('37.3801', $details['latitude']);
        $this->assertEquals('-122.0865', $details['longitude']);
        $this->assertEquals('PST', $details['timezone']);
        $this->assertEquals('LEVEL 3 COMMUNICATIONS INC', $details['isp']);
    }
}
