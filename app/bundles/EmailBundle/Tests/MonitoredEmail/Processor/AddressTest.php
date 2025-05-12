<?php

namespace Mautic\EmailBundle\Tests\MonitoredEmail\Processor;

use Mautic\EmailBundle\MonitoredEmail\Processor\Address;

#[\PHPUnit\Framework\Attributes\CoversClass(Address::class)]
class AddressTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Test that an email header with email addresses are parsed into array')]
    public function testArrayOfAddressesAreReturnedFromEmailHeader(): void
    {
        $results = Address::parseList('<user@test.com>,<user2@test.com>');

        $this->assertEquals(
            [
                'user@test.com'  => null,
                'user2@test.com' => null,
            ],
            $results
        );
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Obtain hash ID from a special formatted email address')]
    public function testStatHashIsParsedFromEmail(): void
    {
        $hash = Address::parseAddressForStatHash('hello+bounce_123abc@test.com');

        $this->assertEquals('123abc', $hash);
    }
}
