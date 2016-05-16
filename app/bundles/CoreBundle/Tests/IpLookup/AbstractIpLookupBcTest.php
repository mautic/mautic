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
    /** @var  IpLookupFactory */
    private $ipFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->ipFactory = new IpLookupFactory(array(
            'bctest' => array(
                'class'        => 'Mautic\CoreBundle\Tests\IpLookup\BC\BcIpLookup',
                'display_name' => 'BC Test'
            )
        ));
    }

    public function createBcIpLookup() {
        $instance = $this->ipFactory->getService('bctest');
        $this->assertInstanceOf(
            'Mautic\CoreBundle\Tests\IpLookup\BC\BcIpLookup',
            $instance,
            sprintf('Expected %s for service %s but received %s instead', 'Mautic\CoreBundle\Tests\IpLookup\BC\BcIpLookup', 'bctest', get_class($instance))
        );
        return $instance;
    }

    public function testIpLookupServiceInstantiation()
    {
        $instance = $this->createBcIpLookup();

        $details = $instance->setIpAddress('1.2.3.4')->getDetails();
        $this->assertEquals('San Francisco', $details['city']);
    }

    public function testDetailsShouldIncludeOnlyPublicClassProperties()
    {
        $instance = $this->createBcIpLookup();

        $details = $instance->setIpAddress('1.2.3.4')->getDetails();

        // public properties which defines class interface
        $this->assertEquals($details['city'], "San Francisco");
        $this->assertEquals($details['region'], "");
        $this->assertEquals($details['zipcode'], "");
        $this->assertEquals($details['country'], "");
        $this->assertEquals($details['latitude'], "");
        $this->assertEquals($details['longitude'], "");
        $this->assertEquals($details['isp'], "");
        $this->assertEquals($details['organization'], "");
        $this->assertEquals($details['timezone'], "");
        $this->assertEquals($details['extra'], "");

        // private and protected properties should not be included @see !1437
        $this->assertTrue(!isset($details['ip']));
        $this->assertTrue(!isset($details['auth']));
        $this->assertTrue(!isset($details['connector']));
        $this->assertTrue(!isset($details['logger']));
    }
}