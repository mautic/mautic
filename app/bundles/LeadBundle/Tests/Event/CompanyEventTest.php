<?php

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Event\CompanyEvent;

class CompanyEventTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructGettersSetters()
    {
        $company = new Company();
        $isNew   = false;
        $score   = 1;
        $event   = new CompanyEvent($company, $isNew, $score);

        $this->assertEquals($company, $event->getCompany());
        $this->assertEquals($isNew, $event->isNew());
        $this->assertEquals($score, $event->getScore());

        $isNew = true;
        $event = new CompanyEvent($company, $isNew, $score);
        $this->assertEquals($isNew, $event->isNew());

        $company2 = new Company();
        $company2->setName('otherCompany');
        $event->setCompany($company2);
        $this->assertEquals($company2, $event->getCompany());

        $secondScore = 2;
        $event->changeScore($secondScore);
        $this->assertEquals($secondScore, $event->getScore());
    }
}
