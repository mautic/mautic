<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\IpLookup;

use Joomla\Http\HttpFactory;
use Mautic\CoreBundle\IpLookup\MaxmindCountryLookup;
use Mautic\CoreBundle\IpLookup\MaxmindOmniLookup;
use Mautic\CoreBundle\IpLookup\MaxmindPrecisionLookup;

/**
 * Class MaxmindLookupTest
 *
 * Maxmind requires API key and thus cannot test actual lookup so just make API endpoint works and
 * classes are initiated
 */
class MaxmindLookupTest extends IpLookup
{
    public function testCountryGetsResponseCode401()
    {
        $url       = "https://geoip.maxmind.com/geoip/v2.1/country/192.30.252.131";
        $connector = HttpFactory::getHttp();
        $response  = $connector->get($url, array('Authorization' => 'Basic '.base64_encode('xxxx:xxxx')));

        $this->assertEquals(401, $response->code);
    }

    public function testCountryServiceInstantiated()
    {
        $ipFactory = $this->container->get('mautic.ip_lookup.factory');
        $service   = $ipFactory->getService('maxmind_country', 'xxxx:xxxx');

        $this->assertTrue($service instanceof MaxmindCountryLookup);
    }

    public function testPrecisionGetsResponseCode401()
    {
        $url       = "https://geoip.maxmind.com/geoip/v2.1/city/192.30.252.131";
        $connector = HttpFactory::getHttp();
        $response  = $connector->get($url, array('Authorization' => 'Basic '.base64_encode('xxxx:xxxx')));

        $this->assertEquals(401, $response->code);
    }

    public function testPrecisionServiceInstantiated()
    {
        $ipFactory = $this->container->get('mautic.ip_lookup.factory');
        $service   = $ipFactory->getService('maxmind_precision', 'xxxx:xxxx');

        $this->assertTrue($service instanceof MaxmindPrecisionLookup);
    }

    public function testOmniGetsResponseCode401()
    {
        $url       = "https://geoip.maxmind.com/geoip/v2.1/insights/192.30.252.131";
        $connector = HttpFactory::getHttp();
        $response  = $connector->get($url, array('Authorization' => 'Basic '.base64_encode('xxxx:xxxx')));

        $this->assertEquals(401, $response->code);
    }

    public function testOmniServiceInstantiated()
    {
        $ipFactory = $this->container->get('mautic.ip_lookup.factory');
        $service   = $ipFactory->getService('maxmind_omni', 'xxxx:xxxx');

        $this->assertTrue($service instanceof MaxmindOmniLookup);
    }
}