<?php

namespace Mautic\CoreBundle\Tests\Unit\Entity;

use Mautic\CoreBundle\Entity\IpAddress;

class IpAddressTest extends \PHPUnit\Framework\TestCase
{
    public function testExactIp()
    {
        $ipAddress = new IpAddress();
        $ipAddress->setDoNotTrackList(
            [
                '192.168.0.1',
            ]
        );
        $ipAddress->setIpAddress('192.168.0.1');
        $this->assertFalse($ipAddress->isTrackable());

        $ipAddress->setIpAddress('192.168.0.2');
        $this->assertTrue($ipAddress->isTrackable());
    }

    public function testIpRange()
    {
        // HostMin:   172.16.0.1
        // HostMax:   172.31.255.255
        $ipAddress = new IpAddress();
        $ipAddress->setDoNotTrackList(
            [
                '172.16.0.0/12',
            ]
        );

        $ipAddress->setIpAddress('172.16.0.1');
        $this->assertFalse($ipAddress->isTrackable());

        $ipAddress->setIpAddress('172.31.255.254');
        $this->assertFalse($ipAddress->isTrackable());

        $ipAddress->setIpAddress('172.15.1.32');
        $this->assertTrue($ipAddress->isTrackable());

        $ipAddress->setIpAddress('172.32.0.0');
        $this->assertTrue($ipAddress->isTrackable());
    }

    public function testIpWildcard()
    {
        $ipAddress = new IpAddress();
        $ipAddress->setDoNotTrackList(
            [
                '172.15.1.*',
            ]
        );
        $ipAddress->setIpAddress('172.15.1.1');
        $this->assertFalse($ipAddress->isTrackable());

        $ipAddress->setIpAddress('172.16.1.1');
        $this->assertTrue($ipAddress->isTrackable());
    }
}
