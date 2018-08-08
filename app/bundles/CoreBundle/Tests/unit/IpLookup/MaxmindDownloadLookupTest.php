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

        $details = $ipService->setIpAddress('192.30.150.130')->getDetails();

        $this->assertEquals('Norwalk', $details['city']);
        $this->assertEquals('Connecticut', $details['region']);
        $this->assertEquals('United States', $details['country']);
        $this->assertEquals('', $details['zipcode']);
        $this->assertEquals('41.1003', $details['latitude']);
        $this->assertEquals('-73.3983', $details['longitude']);
        $this->assertEquals('America/New_York', $details['timezone']);
    }
}
