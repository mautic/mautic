<?php
/**
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\IpLookup;

use Mautic\CoreBundle\Factory\IpLookupFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class IpLookupFactoryTest.
 */
class IpLookupFactoryTest extends WebTestCase
{
    public function testIpLookupServiceInstantiation()
    {
        static::bootKernel();
        $ipServices = static::$kernel->getContainer()->getParameter('mautic.ip_lookup_services');
        $ipFactory  = new IpLookupFactory($ipServices);

        foreach ($ipServices as $service => $details) {
            $instance = $ipFactory->getService($service);

            $this->assertInstanceOf(
                $details['class'],
                $instance,
                sprintf('Expected %s for service %s but received %s instead', $details['class'], $service, get_class($instance))
            );
        }
    }
}

/*
    'bctest' => array(
        'class'        => 'Mautic\CoreBundle\Tests\IpLookup\BC\BcIpLookup',
        'display_name' => 'Freegoip.com'
    )
 */
