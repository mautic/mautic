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
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class BcAbstractIpLookupTest
 */
class BcAbstractIpLookupTest extends WebTestCase
{
    public function testBcCodeWorks()
    {
        $ipFactory = new IpLookupFactory(
            array(
                'freegeoip' => array(
                    'class'        => 'Mautic\CoreBundle\Tests\IpLookup\BC\FreegeoipIpLookup',
                    'display_name' => 'Freegoip.com'
                )
            )
        );

        $service = $ipFactory->getService('freegeoip');

        $service->setIpAddress('192.30.252.131');

        $details = $service->getDetails();

        $this->assertEquals('San Francisco', $details['city']);
    }
}