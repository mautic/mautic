<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\Query\Filter\AnyCompanyRelationValueFilterQueryBuilder;

final class AnyCompanyRelationValueFilterQueryBuilderTest extends AbstractRelationValueFilterQueryBuilderTestCase
{
    private AnyCompanyRelationValueFilterQueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilder = new AnyCompanyRelationValueFilterQueryBuilder(
            $this->randomParameter,
            $this->dispatcher
        );
    }

    public function testGetServiceId(): void
    {
        $this->assertSame(AnyCompanyRelationValueFilterQueryBuilder::class, $this->queryBuilder::getServiceId());
    }

    public function testApplyQueryMatchesAnyAssociatedCompanyButNotMissingCompanies(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'rel1',
            'cmp1'
        );

        $this->queryBuilder->applyQuery($queryBuilder, $this->createFilter('empty', 'ignored'));

        $debugOutput = (string) $queryBuilder->getDebugOutput();

        $this->assertStringContainsString('EXISTS(SELECT 1 FROM '.MAUTIC_TABLE_PREFIX.'companies_leads rel1', $debugOutput);
        $this->assertStringNotContainsString('is_primary', $debugOutput);
        $this->assertStringNotContainsString('NOT EXISTS', $debugOutput);
    }
}
