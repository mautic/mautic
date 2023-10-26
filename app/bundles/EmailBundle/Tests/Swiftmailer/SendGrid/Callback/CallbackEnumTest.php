<?php

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Callback;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Callback\CallbackEnum;
use Mautic\LeadBundle\Entity\DoNotContact;

class CallbackEnumTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider supportedEventsProvider
     */
    public function testSupportedEvents($event)
    {
        $this->assertTrue(CallbackEnum::shouldBeEventProcessed($event));
    }

    public function supportedEventsProvider()
    {
        return [
            ['bounce'],
            ['dropped'],
            ['spamreport'],
            ['unsubscribe'],
            ['group_unsubscribe'],
        ];
    }

    /**
     * @dataProvider notSupportedEventsProvider
     */
    public function testNotSupportedEvents($event)
    {
        $this->assertFalse(CallbackEnum::shouldBeEventProcessed($event));
    }

    public function notSupportedEventsProvider()
    {
        return [
            ['processed'],
            ['delivered'],
            ['deferred'],
            ['open'],
            ['click'],
            ['group_resubscribe'],
        ];
    }

    public function testConvertEventToDncReason()
    {
        $this->assertSame(DoNotContact::BOUNCED, CallbackEnum::convertEventToDncReason('bounce'));
        $this->assertSame(DoNotContact::BOUNCED, CallbackEnum::convertEventToDncReason('dropped'));
        $this->assertSame(DoNotContact::BOUNCED, CallbackEnum::convertEventToDncReason('spamreport'));
        $this->assertSame(DoNotContact::UNSUBSCRIBED, CallbackEnum::convertEventToDncReason('unsubscribe'));
        $this->assertSame(DoNotContact::UNSUBSCRIBED, CallbackEnum::convertEventToDncReason('group_unsubscribe'));
    }
}
