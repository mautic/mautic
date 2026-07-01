<?php

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\ChannelSubscriptionChange;

final class ChannelSubscriptionChangeTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Tests that getters returns same values as the contstruct')]
    public function testGetterReturnConstruct(): void
    {
        $lead      = new Lead();
        $channel   = 'email';
        $oldStatus = DoNotContact::IS_CONTACTABLE;
        $newStatus = DoNotContact::UNSUBSCRIBED;

        $event = new ChannelSubscriptionChange($lead, $channel, $oldStatus, $newStatus);

        $this->assertEquals($lead, $event->getLead());
        $this->assertEquals($channel, $event->getChannel());
        $this->assertSame($oldStatus, $event->getOldStatus());
        $this->assertSame($newStatus, $event->getNewStatus());
        $this->assertSame('contactable', $event->getOldStatusVerb());
        $this->assertSame('unsubscribed', $event->getNewStatusVerb());
    }

    #[\PHPUnit\Framework\Attributes\TestDox('Test that the default verb is unsubscribed if not recongized')]
    public function testGetStatusVerbReturnsUnsubscribedForUnrecognized(): void
    {
        $lead      = new Lead();
        $channel   = 'email';
        $oldStatus = DoNotContact::IS_CONTACTABLE;

        $event = new ChannelSubscriptionChange($lead, $channel, $oldStatus, 456);

        $this->assertSame('unsubscribed', $event->getNewStatusVerb());
    }
}
