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
use Mautic\CoreBundle\IpLookup\GeoipsLookup;

/**
 * Class GeoipsLookupTest
 *
 * Requires an API key so can't test an actual lookup
 */
class GeoipsLookupTest extends IpLookup
{
    public function testResponseHasNotAuthorized()
    {
        $url       = "http://api.geoips.com/ip/192.30.252.131/key/xxxx/output/json";
        $connector = HttpFactory::getHttp();
        $response  = $connector->get($url);

        $this->assertContains('Not Authorized', $response->body);
    }

    public function testServiceInstantiated()
    {
        $ipFactory = $this->container->get('mautic.ip_lookup.factory');
        $service   = $ipFactory->getService('geoips', 'xxxx');

        $this->assertTrue($service instanceof GeoipsLookup);
    }
}