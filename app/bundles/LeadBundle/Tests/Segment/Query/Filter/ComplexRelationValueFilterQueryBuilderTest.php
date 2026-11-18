<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\Query\Filter\ComplexRelationValueFilterQueryBuilder;

final class ComplexRelationValueFilterQueryBuilderTest extends AbstractRelationValueFilterQueryBuilderTestCase
{
    private ComplexRelationValueFilterQueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilder = new ComplexRelationValueFilterQueryBuilder(
            $this->randomParameter,
            $this->dispatcher
        );
    }

    public function testGetServiceId(): void
    {
        $this->assertSame('mautic.lead.query.builder.complex_relation.value', $this->queryBuilder::getServiceId());
    }

    public function testApplyQueryCreatesJoinAndReturnsExpectedExpression(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'cmp',
            'rel'
        );

        $filter = $this->createFilter('empty', 'ignored');
        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        $debugOutput = $queryBuilder->getDebugOutput();

        $this->assertStringContainsString('SELECT 1 FROM '.MAUTIC_TABLE_PREFIX.'leads l', (string) $debugOutput);
        $this->assertStringContainsString('LEFT JOIN '.MAUTIC_TABLE_PREFIX.'companies_leads rel ON rel.lead_id = l.id', (string) $debugOutput);
        $this->assertStringContainsString('LEFT JOIN '.MAUTIC_TABLE_PREFIX.'companies cmp ON cmp.id = rel.company_id', (string) $debugOutput);
        $this->assertStringContainsString('(cmp.company_name IS NULL) OR (cmp.company_name = )', (string) $debugOutput);
    }

    public function testApplyQueryThrowsOnUnsupportedOperator(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'cmp',
            'rel'
        );

        $filter = $this->createFilter('unsupported', 'value');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported filter operator "unsupported".');

        $this->queryBuilder->applyQuery($queryBuilder, $filter);
    }
}
