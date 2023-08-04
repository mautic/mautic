<?php

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\CategoryBundle\Entity\Category;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Event\LeadListEvent;

class LeadListEventTest extends \PHPUnit\Framework\TestCase
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

        $isNew = true;
        $event = new LeadListEvent($segment, $isNew);

        $category = new Category();
        $category->setTitle('Segment Category 1');
        $category->setAlias('segment-category-1');
        $category->setBundle('segment');

        $segment3 = new LeadList();
        $segment3->setName('Segment 1');
        $segment3->setCategory($category);
        $event->setList($segment3);
        $this->assertEquals($segment3, $event->getList());
    }
}
