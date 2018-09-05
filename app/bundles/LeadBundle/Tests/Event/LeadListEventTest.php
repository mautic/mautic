<?php

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadListEvent;

class LeadListEventTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructGettersSetters()
    {
        $segment = new LeadList();
        $event   = new LeadListEvent($segment);

        $this->assertEquals($segment, $event->getList());
        $this->assertEquals(false, $event->isNew());

        $isNew = false;
        $event = new LeadListEvent($segment, $isNew);
        $this->assertEquals($isNew, $event->isNew());

        $isNew = true;
        $event = new LeadListEvent($segment, $isNew);
        $this->assertEquals($isNew, $event->isNew());

        $segment2 = new LeadList();
        $segment2->setName('otherSegmentName');
        $event->setList($segment2);
        $this->assertEquals($segment2, $event->getList());
    }
}
