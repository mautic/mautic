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
use Mautic\CoreBundle\IpLookup\TelizeLookup;

/**
 * Class TelizeLookupTest
 *
 * Requires an API key restricted by IP so cannot test actual lookup
 */
class TelizeLookupTest extends IpLookup
{
    public function testResponseHasInvalidMashapeKey()
    {
        $url       = "https://telize-v1.p.mashape.com/geoip/192.30.252.131";
        $connector = HttpFactory::getHttp();
        $response  = $connector->get(
            $url,
            array(
                "X-Mashape-Key" => 'xxxx',
                "Accept"        => "application/json"
            )
        );

        $this->assertContains('Invalid Mashape Key', $response->body);
    }

    public function testServiceInstantiated()
    {
        $ipFactory = $this->container->get('mautic.ip_lookup.factory');
        $service   = $ipFactory->getService('telize', 'xxxx');

        $this->assertTrue($service instanceof TelizeLookup);
    }
}