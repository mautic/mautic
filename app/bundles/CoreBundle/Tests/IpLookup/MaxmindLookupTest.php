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

use Mautic\CoreBundle\IpLookup\MaxmindCountryLookup;
use Mautic\CoreBundle\IpLookup\MaxmindOmniLookup;
use Mautic\CoreBundle\IpLookup\MaxmindPrecisionLookup;

/**
 * Class MaxmindLookupTest.
 *
 * Maxmind requires API key and thus cannot test actual lookup so just make API endpoint works and
 * classes are initiated
 */
class MaxmindLookupTest extends \PHPUnit_Framework_TestCase
{
    protected $mockHttp;

    public function setUp()
    {
        // Mock http connector
        $this->mockHttp = $this->getMockBuilder('Joomla\Http\Http')
            ->disableOriginalConstructor()
            ->getMock();

        // Mock a successful response
        $mockResponse = $this->getMockBuilder('Joomla\Http\Response')
            ->getMock();
        $mockResponse->code = 200;
        $mockResponse->body = <<<'RESPONSE'
{
  "city":  {
      "confidence":  25,
      "geoname_id": 54321,
      "names":  {
          "de":    "Los Angeles",
          "en":    "Los Angeles",
          "es":    "Los Ángeles",
          "fr":    "Los Angeles",
          "ja":    "ロサンゼルス市",
          "pt-BR":  "Los Angeles",
          "ru":    "Лос-Анджелес",
          "zh-CN": "洛杉矶"
      }
  },
  "continent":  {
      "code":       "NA",
      "geoname_id": 123456,
      "names":  {
          "de":    "Nordamerika",
          "en":    "North America",
          "es":    "América del Norte",
          "fr":    "Amérique du Nord",
          "ja":    "北アメリカ",
          "pt-BR": "América do Norte",
          "ru":    "Северная Америка",
          "zh-CN": "北美洲"

      }
  },
  "country":  {
      "confidence":  75,
      "geoname_id":  6252001,
      "iso_code":    "US",
      "names":  {
          "de":     "USA",
          "en":     "United States",
          "es":     "Estados Unidos",
          "fr":     "États-Unis",
          "ja":     "アメリカ合衆国",
          "pt-BR":  "Estados Unidos",
          "ru":     "США",
          "zh-CN":  "美国"
      }
  },
  "location":  {
      "accuracy_radius":     20,
      "average_income":      128321,
      "latitude":            37.6293,
      "longitude":           -122.1163,
      "metro_code":          807,
      "population_density":  7122,
      "time_zone":           "America/Los_Angeles"
  },
  "postal": {
      "code":       "90001",
      "confidence": 10
  },
  "registered_country":  {
      "geoname_id":  6252001,
      "iso_code":    "US",
      "names":  {
          "de":     "USA",
          "en":     "United States",
          "es":     "Estados Unidos",
          "fr":     "États-Unis",
          "ja":     "アメリカ合衆国",
          "pt-BR":  "Estados Unidos",
          "ru":     "США",
          "zh-CN":  "美国"
      }
  },
  "represented_country":  {
      "geoname_id":  6252001,
      "iso_code":    "US",
      "names":  {
          "de":     "USA",
          "en":     "United States",
          "es":     "Estados Unidos",
          "fr":     "États-Unis",
          "ja":     "アメリカ合衆国",
          "pt-BR":  "Estados Unidos",
          "ru":     "США",
          "zh-CN":  "美国"
      },
      "type": "military"
  },
  "subdivisions":  [
      {
          "confidence":  50,
          "geoname_id":  5332921,
          "iso_code":    "CA",
          "names":  {
              "de":    "Kalifornien",
              "en":    "California",
              "es":    "California",
              "fr":    "Californie",
              "ja":    "カリフォルニア",
              "ru":    "Калифорния",
              "zh-CN": "加州"
          }
      }
  ],
  "traits": {
      "autonomous_system_number":      1239,
      "autonomous_system_organization": "Linkem IR WiMax Network",
      "domain":                        "example.com",
      "is_anonymous_proxy":            true,
      "is_satellite_provider":         true,
      "isp":                           "Linkem spa",
      "ip_address":                    "1.2.3.4",
      "organization":                  "Linkem IR WiMax Network",
      "user_type":                     "traveler"
  },
  "maxmind": {
      "queries_remaining":            54321
  }
}
RESPONSE;

        $this->mockHttp->expects($this->once())
            ->method('get')
            ->willReturn($mockResponse);
    }

    public function testCountryIpLookupSuccessful()
    {
        $ipService = new MaxmindCountryLookup(null, null, __DIR__.'/../../../../cache/test', null, $this->mockHttp);

        $details = $ipService->setIpAddress('1.2.3.4')->getDetails();

        $this->checkDetails($details);
    }

    public function testOmniIpLookupSuccessful()
    {
        $ipService = new MaxmindOmniLookup(null, null, __DIR__.'/../../../../cache/test', null, $this->mockHttp);

        $details = $ipService->setIpAddress('1.2.3.4')->getDetails();

        $this->checkDetails($details);
    }

    public function testPrecisionIpLookupSuccessful()
    {
        $ipService = new MaxmindPrecisionLookup(null, null, __DIR__.'/../../../../cache/test', null, $this->mockHttp);

        $details = $ipService->setIpAddress('1.2.3.4')->getDetails();

        $this->checkDetails($details);
    }

    private function checkDetails($details)
    {
        $this->assertEquals('Los Angeles', $details['city']);
        $this->assertEquals('California', $details['region']);
        $this->assertEquals('United States', $details['country']);
        $this->assertEquals('90001', $details['zipcode']);
        $this->assertEquals('37.6293', $details['latitude']);
        $this->assertEquals('-122.1163', $details['longitude']);
        $this->assertEquals('America/Los_Angeles', $details['timezone']);
        $this->assertEquals('Linkem spa', $details['isp']);
        $this->assertEquals('Linkem IR WiMax Network', $details['organization']);
    }
}
