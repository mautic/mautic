<?php

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Event\CompanyMergeEvent;
use PHPUnit\Framework\TestCase;

class CompanyMergeEventTest extends TestCase
{
    public function testConstructGettersSetters()
    {
        $victor = new Company();
        $loser = new Company();
        $event   = new CompanyMergeEvent($victor, $loser);

        $this->assertEquals($victor, $event->getVictor());
        $this->assertEquals($loser, $event->getLoser());
    }
}
