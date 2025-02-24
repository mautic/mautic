<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\DoNotContact;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Segment\DoNotContact\DoNotContactParts;

class DoNotContactPartsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider dataProvider
     */
    public function testParts(string $field, string $channel, int $type): void
    {
        $doNotContactParts = new DoNotContactParts($field);
        $this->assertSame($channel, $doNotContactParts->getChannel());
        $this->assertSame($type, $doNotContactParts->getParameterType());
    }

    /**
     * @return iterable<array<string,string|int>>
     */
    public static function dataProvider(): iterable
    {
        yield [
            'field'   => 'dnc_bounced',
            'channel' => 'email',
            'type'    => DoNotContact::BOUNCED,
        ];

        yield [
            'field'   => 'dnc_unsubscribed',
            'channel' => 'email',
            'type'    => DoNotContact::UNSUBSCRIBED,
        ];

        yield [
            'field'   => 'dnc_manual_email',
            'channel' => 'email',
            'type'    => DoNotContact::MANUAL,
        ];

        yield [
            'field'   => 'dnc_bounced_sms',
            'channel' => 'sms',
            'type'    => DoNotContact::BOUNCED,
        ];

        yield [
            'field'   => 'dnc_unsubscribed_sms',
            'channel' => 'sms',
            'type'    => DoNotContact::UNSUBSCRIBED,
        ];

        yield [
            'field'   => 'dnc_manual_sms',
            'channel' => 'sms',
            'type'    => DoNotContact::MANUAL,
        ];

        yield [
            'field'   => 'dnc_unsubscribed_sms_manually',
            'channel' => 'sms',
            'type'    => DoNotContact::MANUAL,
        ];
    }
}
