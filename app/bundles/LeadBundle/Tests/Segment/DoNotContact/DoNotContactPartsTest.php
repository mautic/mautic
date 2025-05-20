<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\DoNotContact;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Segment\DoNotContact\DoNotContactParts;

class DoNotContactPartsTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('dataProvider')]
    public function testParts(string $field, string $channel, int $type, ?string $commentFilter = null, bool $isAllDnc = false): void
    {
        $doNotContactParts = new DoNotContactParts($field);
        $this->assertSame($channel, $doNotContactParts->getChannel());
        $this->assertSame($type, $doNotContactParts->getParameterType());
        $this->assertSame($commentFilter, $doNotContactParts->getCommentFilter());
        $this->assertSame($isAllDnc, $doNotContactParts->isAllDnc());
    }

    /**
     * @return iterable<array<string,string|int|bool|null>>
     */
    public static function dataProvider(): iterable
    {
        yield [
            'field'         => 'dnc_bounced',
            'channel'       => 'email',
            'type'          => DoNotContact::BOUNCED,
            'commentFilter' => null,
            'isAllDnc'      => false,
        ];

        yield [
            'field'         => 'dnc_unsubscribed',
            'channel'       => 'email',
            'type'          => DoNotContact::UNSUBSCRIBED,
            'commentFilter' => null,
            'isAllDnc'      => false,
        ];

        yield [
            'field'         => 'dnc_manual_email',
            'channel'       => 'email',
            'type'          => DoNotContact::MANUAL,
            'commentFilter' => null,
            'isAllDnc'      => false,
        ];

        yield [
            'field'         => 'dnc_bounced_sms',
            'channel'       => 'sms',
            'type'          => DoNotContact::BOUNCED,
            'commentFilter' => null,
            'isAllDnc'      => false,
        ];

        yield [
            'field'         => 'dnc_unsubscribed_sms',
            'channel'       => 'sms',
            'type'          => DoNotContact::UNSUBSCRIBED,
            'commentFilter' => null,
            'isAllDnc'      => false,
        ];

        yield [
            'field'         => 'dnc_manual_sms',
            'channel'       => 'sms',
            'type'          => DoNotContact::MANUAL,
            'commentFilter' => null,
            'isAllDnc'      => false,
        ];

        yield [
            'field'         => 'dnc_unsubscribed_sms_manually',
            'channel'       => 'sms',
            'type'          => DoNotContact::MANUAL,
            'commentFilter' => null,
            'isAllDnc'      => false,
        ];
        
        // New DNC filter types
        yield [
            'field'         => 'dnc_all',
            'channel'       => 'email',
            'type'          => DoNotContact::UNSUBSCRIBED,
            'commentFilter' => null,
            'isAllDnc'      => true,
        ];
        
        yield [
            'field'         => 'dnc_hard_bounce',
            'channel'       => 'email',
            'type'          => DoNotContact::BOUNCED,
            'commentFilter' => 'hard',
            'isAllDnc'      => false,
        ];
        
        yield [
            'field'         => 'dnc_soft_bounce',
            'channel'       => 'email',
            'type'          => DoNotContact::BOUNCED,
            'commentFilter' => 'soft',
            'isAllDnc'      => false,
        ];
        
        yield [
            'field'         => 'dnc_spam_bounce',
            'channel'       => 'email',
            'type'          => DoNotContact::BOUNCED,
            'commentFilter' => 'spam',
            'isAllDnc'      => false,
        ];
    }
}
