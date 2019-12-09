<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Event;

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
    }
}
