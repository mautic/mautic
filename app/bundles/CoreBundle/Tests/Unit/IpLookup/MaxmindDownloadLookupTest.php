<?php

namespace Mautic\CoreBundle\Tests\Unit\IpLookup;

use Mautic\CoreBundle\IpLookup\MaxmindDownloadLookup;

class MaxmindDownloadLookupTest extends \PHPUnit\Framework\TestCase
{
    public function testDownloadDataStore()
    {
        if (empty($_ENV['MAXMIND_LICENSE_KEY'])) {
            // The env variable MAXMIND_LICENSE_KEY. can be set in phpunit.xml
            $this->markTestSkipped('You can run this test just if you add license key to env variable MAXMIND_LICENSE_KEY.');
        }

        $license_key =  $_ENV['MAXMIND_LICENSE_KEY'];

        // Keep the file contained to cache/test
        $ipService = new MaxmindDownloadLookup($license_key, null, sys_get_temp_dir());

        $result = $ipService->downloadRemoteDataStore();

        $this->assertTrue($result);
    }

    public function testIpLookupSuccessful()
    {
        if (empty($_ENV['MAXMIND_LICENSE_KEY'])) {
            $this->markTestSkipped('It can be tested just with testDownloadDataStore. It needs env variable MAXMIND_LICENSE_KEY.');
        }

        // Keep the file contained to cache/test
        $ipService = new MaxmindDownloadLookup(null, null, sys_get_temp_dir());

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
