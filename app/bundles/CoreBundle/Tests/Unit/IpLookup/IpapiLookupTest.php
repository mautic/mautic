<?php

namespace Mautic\CoreBundle\Tests\Unit\IpLookup;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Mautic\CoreBundle\IpLookup\IpapiLookup;

class IpapiLookupTest extends \PHPUnit\Framework\TestCase
{
	private $cacheDir = __DIR__.'/../../../../../../var/cache/test';

	public function testIpLookupSuccessful()
	{
		// Mock http connector
		$mockHttp = $this->createMock(C`lient::class);`

		// Mock a successful response
		$mockResponse = new Response(200, [], '{"ip": "192.30.252.131", "type": "ipv4", "continent_code": "NA", "continent_name": "North America", "country_code": "US", "country_name": "United States", "region_code": "CA", "region_name": "California", "city": "San Francisco", "zip": "94107", "latitude": 37.76784896850586, "longitude": -122.39286041259766, "location": {"geoname_id": 5391959, "capital": "Washington D.C.", "languages": [{"code": "en", "name": "English", "native": "English"}], "country_flag": "https://assets.ipstack.com/flags/us.svg", "country_flag_emoji": "\ud83c\uddfa\ud83c\uddf8", "country_flag_emoji_unicode": "U+1F1FA U+1F1F8", "calling_code": "1", "is_eu": false}}');

		$mockHttp->expects($this->once())
			->method('get')
			->willReturn($mockResponse);

		$ipService = new IpstackLookup('mockApiToken', null, $this->cacheDir, null, $mockHttp);

		$details = $ipService->setIpAddress('192.30.252.131')->getDetails();

		$this->assertEquals('San Francisco', $details['city']);
		$this->assertEquals('California', $details['region_name']);
		$this->assertEquals('United States', $details['country_name']);
		$this->assertEquals('94107', $details['zip']);
		$this->assertEquals('37.76784896850586', $details['latitude']);
		$this->assertEquals('-122.39286041259766', $details['longitude']);
	}
}
