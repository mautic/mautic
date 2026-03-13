<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Event;

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanySegment;
use Mautic\LeadBundle\Event\CompanySegmentChangeEvent;
use PHPUnit\Framework\TestCase;

class CompanySegmentChangeEventTest extends TestCase
{
    public function testGetCompanyReturnsCompany(): void
    {
        $company        = new Company();
        $companySegment = new CompanySegment();
        $event          = new CompanySegmentChangeEvent($company, $companySegment, true);

        $this->assertSame($company, $event->getCompany());
    }

    public function testGetCompanySegmentReturnsCompanySegment(): void
    {
        $company        = new Company();
        $companySegment = new CompanySegment();
        $event          = new CompanySegmentChangeEvent($company, $companySegment, true);

        $this->assertSame($companySegment, $event->getCompanySegment());
    }

    public function testWasAddedReturnsTrueWhenCompanyWasAdded(): void
    {
        $company        = new Company();
        $companySegment = new CompanySegment();
        $event          = new CompanySegmentChangeEvent($company, $companySegment, true);

        $this->assertTrue($event->wasAdded());
        $this->assertFalse($event->wasRemoved());
    }

    public function testWasRemovedReturnsTrueWhenCompanyWasRemoved(): void
    {
        $company        = new Company();
        $companySegment = new CompanySegment();
        $event          = new CompanySegmentChangeEvent($company, $companySegment, false);

        $this->assertFalse($event->wasAdded());
        $this->assertTrue($event->wasRemoved());
    }

    public function testGetDateReturnsProvidedDate(): void
    {
        $company        = new Company();
        $companySegment = new CompanySegment();
        $date           = new \DateTime('2024-01-01 12:00:00');
        $event          = new CompanySegmentChangeEvent($company, $companySegment, true, $date);

        $this->assertSame($date, $event->getDate());
    }

    public function testGetDateReturnsNullWhenNoDateProvided(): void
    {
        $company        = new Company();
        $companySegment = new CompanySegment();
        $event          = new CompanySegmentChangeEvent($company, $companySegment, true);

        $this->assertNull($event->getDate());
    }
}
