<?php

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\ChannelSubscriptionChange;

/**
 * Class ChannelSubscriptionChangeTest.
 */
class ChannelSubscriptionChangeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @testdox Tests that getters returns same values as the contstruct
     */
    public function testGetterReturnConstruct()
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
    public function testGetStatusVerbReturnsUnsubscribedForUnrecognized()
    {
        $lead      = new Lead();
        $channel   = 'email';
        $oldStatus = DoNotContact::IS_CONTACTABLE;

        $event = new ChannelSubscriptionChange($lead, $channel, $oldStatus, 'foobar');

        $this->assertEquals('unsubscribed', $event->getNewStatusVerb());
    }
}
