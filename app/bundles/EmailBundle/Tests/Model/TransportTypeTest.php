<?php

namespace Mautic\EmailBundle\Tests\Model;

use Mautic\EmailBundle\Model\TransportType;

class TransportTypeTest extends \PHPUnit\Framework\TestCase
{
    public function testGetTransportTypes()
    {
        $transportType = new TransportType();

        $expected = [
        ];

        $this->assertSame($expected, $transportType->getTransportTypes());
    }

    public function testRequiresPassword()
    {
        $transportType = new TransportType();

        $this->assertSame('""', $transportType->getServiceRequiresPassword());
    }
}
