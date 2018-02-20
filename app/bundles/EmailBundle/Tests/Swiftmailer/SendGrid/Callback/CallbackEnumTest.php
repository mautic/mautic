<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Swiftmailer\SendGrid\Callback;

use Mautic\EmailBundle\Swiftmailer\SendGrid\Callback\CallbackEnum;
use Mautic\LeadBundle\Entity\DoNotContact;

class CallbackEnumTest extends \PHPUnit_Framework_TestCase
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
