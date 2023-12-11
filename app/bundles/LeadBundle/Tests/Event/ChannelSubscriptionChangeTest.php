<?php

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\ChannelSubscriptionChange;

class ChannelSubscriptionChangeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox Tests that getters returns same values as the contstruct
     */
    public function testGetterReturnConstruct(): void
    {
        $lead      = new Lead();
        $channel   = 'email';
        $oldStatus = DoNotContact::IS_CONTACTABLE;
        $newStatus = DoNotContact::UNSUBSCRIBED;

        $event = new ChannelSubscriptionChange($lead, $channel, $oldStatus, $newStatus);

        $this->assertEquals($lead, $event->getLead());
        $this->assertEquals($channel, $event->getChannel());
        $this->assertEquals($oldStatus, $event->getOldStatus());
        $this->assertEquals($newStatus, $event->getNewStatus());
        $this->assertEquals('contactable', $event->getOldStatusVerb());
        $this->assertEquals('unsubscribed', $event->getNewStatusVerb());
    }

    /**
     * @testdox Test that the default verb is unsubscribed if not recongized
     */
    public function testGetStatusVerbReturnsUnsubscribedForUnrecognized(): void
    {
        $lead      = new Lead();
        $channel   = 'email';
        $oldStatus = DoNotContact::IS_CONTACTABLE;

        $event = new ChannelSubscriptionChange($lead, $channel, $oldStatus, 456);

        $this->assertEquals('unsubscribed', $event->getNewStatusVerb());
    }
}
