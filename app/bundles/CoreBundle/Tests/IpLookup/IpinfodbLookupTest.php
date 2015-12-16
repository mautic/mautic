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
use Mautic\CoreBundle\IpLookup\IpinfodbLookup;

/**
 * Class IpinfodbLookupTest
 *
 * Requires an API key restricted by IP so cannot test actual lookup
 */
class IpinfodbLookupTest extends IpLookup
{
    public function testResponseHasInvalidApiKey()
    {
        $url       = "http://api.ipinfodb.com/v3/ip-city/?key=xxxx&format=json&ip=192.30.252.131";
        $connector = HttpFactory::getHttp();
        $response  = $connector->get($url);

        $this->assertContains('Invalid API key', $response->body);
    }

    public function testServiceInstantiated()
    {
        $ipFactory = $this->container->get('mautic.ip_lookup.factory');
        $service   = $ipFactory->getService('ipinfodb', 'xxxx');

        $this->assertTrue($service instanceof IpinfodbLookup);
    }
}