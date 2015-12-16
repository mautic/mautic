<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Tests\IpLookup;

use Mautic\CoreBundle\Factory\IpLookupFactory;

/**
 * Class IpLookupFactoryTest
 */
class AbstractIpLookupBcTest extends \PHPUnit_Framework_TestCase
{
    public function testIpLookupServiceInstantiation()
    {
        $ipFactory  = new IpLookupFactory(array(
            'bctest' => array(
                'class'        => 'Mautic\CoreBundle\Tests\IpLookup\BC\BcIpLookup',
                'display_name' => 'BC Test'
            )
        ));

        $instance = $ipFactory->getService('bctest');

        $this->assertInstanceOf(
            'Mautic\CoreBundle\Tests\IpLookup\BC\BcIpLookup',
            $instance,
            sprintf('Expected %s for service %s but received %s instead', 'Mautic\CoreBundle\Tests\IpLookup\BC\BcIpLookup', 'bctest', get_class($instance))
        );

        $details = $instance->setIpAddress('1.2.3.4')->getDetails();

        $this->assertEquals('San Francisco', $details['city']);
    }
}