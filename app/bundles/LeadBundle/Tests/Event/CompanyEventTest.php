<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Event\CompanyPostSaveEvent;
use Mautic\LeadBundle\Entity\Company;

final class CompanyEventTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructGettersSetters(): void
    {
        $company = new Company();
        $isNew   = false;
        $score   = 1;
        $event   = new CompanyPostSaveEvent($company, $isNew, $score);

        $this->assertEquals($company, $event->getCompany());
        $this->assertEquals($isNew, $event->isNew());
        $this->assertSame($score, $event->getScore());

        $isNew = true;
        $event = new CompanyPostSaveEvent($company, $isNew, $score);
        $this->assertEquals($isNew, $event->isNew());

        $company2 = new Company();
        $company2->setName('otherCompany');
        $event->setCompany($company2);
        $this->assertEquals($company2, $event->getCompany());

        $secondScore = 2;
        $event->changeScore($secondScore);
        $this->assertSame($secondScore, $event->getScore());
    }
}
