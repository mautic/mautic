<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\IpLookup;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class IpLookup
 */
class IpLookup extends WebTestCase
{
    protected $container;

    public function setUp()
    {
        self::bootKernel();

        $this->container = self::$kernel->getContainer();
    }

    protected function isIpLookupSuccessful($service = null, $auth = null, $expectedCity = 'San Francisco')
    {
        $ipFactory = $this->container->get('mautic.ip_lookup.factory');
        $service   = $ipFactory->getService($service, $auth);

        $service->setIpAddress('192.30.252.131');

        $details = $service->getDetails();

        $this->assertEquals($expectedCity, $details['city']);
    }
}