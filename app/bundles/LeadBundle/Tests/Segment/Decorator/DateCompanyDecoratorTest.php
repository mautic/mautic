<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Decorator;

use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\Decorator\DateCompanyDecorator;
use Mautic\LeadBundle\Segment\Decorator\FilterDecoratorInterface;
use Mautic\LeadBundle\Segment\Query\Filter\AnyCompanyRelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\PrimaryCompanyRelationValueFilterQueryBuilder;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class DateCompanyDecoratorTest extends TestCase
{
    public function testGetQueryTypeReturnsAnyCompanyServiceForCompanyAllObject(): void
    {
        $dateCompanyDecorator = $this->createDateCompanyDecorator();

        $crate = new ContactSegmentFilterCrate([
            'object' => ContactSegmentFilterCrate::COMPANY_ALL_OBJECT,
        ]);

        Assert::assertSame(
            AnyCompanyRelationValueFilterQueryBuilder::getServiceId(),
            $dateCompanyDecorator->getQueryType($crate)
        );
    }

    public function testGetQueryTypeReturnsPrimaryCompanyServiceForPrimaryObject(): void
    {
        $dateCompanyDecorator = $this->createDateCompanyDecorator();

        $crate = new ContactSegmentFilterCrate([
            'object' => ContactSegmentFilterCrate::COMPANY_OBJECT,
        ]);

        Assert::assertSame(
            PrimaryCompanyRelationValueFilterQueryBuilder::getServiceId(),
            $dateCompanyDecorator->getQueryType($crate)
        );
    }

    public function testGetQueryTypeReturnsComplexServiceForLeadObject(): void
    {
        $dateCompanyDecorator = $this->createDateCompanyDecorator();

        $crate = new ContactSegmentFilterCrate([
            'object' => ContactSegmentFilterCrate::CONTACT_OBJECT,
        ]);

        Assert::assertSame(
            ComplexRelationValueFilterQueryBuilder::getServiceId(),
            $dateCompanyDecorator->getQueryType($crate)
        );
    }

    private function createDateCompanyDecorator(): DateCompanyDecorator
    {
        $dateDecorator = $this->createMock(FilterDecoratorInterface::class);

        return new DateCompanyDecorator($dateDecorator);
    }
}
