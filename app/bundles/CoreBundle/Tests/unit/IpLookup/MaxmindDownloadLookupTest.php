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

use Mautic\CoreBundle\IpLookup\MaxmindDownloadLookup;

/**
 * Class MaxmindDownloadTest.
 */
class MaxmindDownloadLookupTest extends \PHPUnit_Framework_TestCase
{
    public function testDownloadDataStore()
    {
        // Keep the file contained to cache/test
        $ipService = new MaxmindDownloadLookup(null, null, __DIR__.'/../../../../../cache/test');

        $result = $ipService->downloadRemoteDataStore();

        $this->assertTrue($result);
    }

    public function testIpLookupSuccessful()
    {
        // Keep the file contained to cache/test
        $ipService = new MaxmindDownloadLookup(null, null, __DIR__.'/../../../../../cache/test');

        $details = $ipService->setIpAddress('52.52.118.192')->getDetails();

        $this->assertEquals('San Jose', $details['city']);
        $this->assertEquals('California', $details['region']);
        $this->assertEquals('United States', $details['country']);
        $this->assertEquals('', $details['zipcode']);
        $this->assertEquals('37.3388', $details['latitude']);
        $this->assertEquals('-121.8914', $details['longitude']);
        $this->assertEquals('America/Los_Angeles', $details['timezone']);
    }
}
