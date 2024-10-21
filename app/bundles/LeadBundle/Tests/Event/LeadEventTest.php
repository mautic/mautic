<?php

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadEvent;

class LeadEventTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructGettersSetters(): void
    {
        $lead  = new Lead();
        $event = new LeadEvent($lead);

        $this->assertEquals($lead, $event->getLead());
        $this->assertFalse($event->isNew());

        $event = new LeadEvent($lead, false);
        $this->assertFalse($event->isNew());

        $event = new LeadEvent($lead, true);
        $this->assertTrue($event->isNew());
    }
}
