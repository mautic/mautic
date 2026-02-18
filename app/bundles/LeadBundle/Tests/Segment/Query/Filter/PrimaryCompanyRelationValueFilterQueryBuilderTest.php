<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Mautic\LeadBundle\Segment\Query\Filter\PrimaryCompanyRelationValueFilterQueryBuilder;
use PHPUnit\Framework\Assert;

class PrimaryCompanyRelationValueFilterQueryBuilderTest extends AbstractRelationValueFilterQueryBuilderTestCase
{
    private PrimaryCompanyRelationValueFilterQueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queryBuilder = new PrimaryCompanyRelationValueFilterQueryBuilder(
            $this->randomParameter,
            $this->dispatcher
        );
    }

    public function testGetServiceId(): void
    {
        Assert::assertSame(
            'mautic.lead.query.builder.complex_relation.primary_company',
            $this->queryBuilder::getServiceId()
        );
    }

    public function testApplyQueryAllowsMissingCompanyForEmptyOperator(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'rel1',
            'cmp1',
            'rel2'
        );

        $filter = $this->createFilter('empty', 'ignored');
        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        $debugOutput = $queryBuilder->getDebugOutput();

        Assert::assertStringContainsString(
            'EXISTS(SELECT 1 FROM '.MAUTIC_TABLE_PREFIX.'companies_leads rel1 LEFT JOIN '.MAUTIC_TABLE_PREFIX.'companies cmp1',
            $debugOutput
        );
        Assert::assertStringContainsString('(rel1.lead_id = l.id) AND (rel1.is_primary = 1)', $debugOutput);
        Assert::assertStringContainsString(
            '((cmp1.company_name IS NULL) OR (cmp1.company_name',
            $debugOutput
        );
        Assert::assertStringContainsString(
            '(EXISTS(SELECT 1 FROM '.MAUTIC_TABLE_PREFIX.'companies_leads rel1 LEFT JOIN '.MAUTIC_TABLE_PREFIX.'companies cmp1 ON cmp1.id = rel1.company_id WHERE',
            $debugOutput
        );
        Assert::assertStringContainsString(
            'OR (NOT EXISTS(SELECT 1 FROM '.MAUTIC_TABLE_PREFIX.'companies_leads rel2 WHERE (rel2.lead_id = l.id) AND (rel2.is_primary = 1))',
            $debugOutput
        );
    }

    public function testApplyQueryAppliesNotEmptyOperatorCondition(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'rel1',
            'cmp1',
            'rel2'
        );

        $filter = $this->createFilter('notEmpty', 'Acme');
        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        $debugOutput = $queryBuilder->getDebugOutput();

        Assert::assertStringContainsString(
            'WHERE (rel1.lead_id = l.id) AND (rel1.is_primary = 1) AND ((cmp1.company_name IS NOT NULL) AND (cmp1.company_name',
            $debugOutput
        );
    }

    public function testApplyQueryAppliesNotEqualOperatorWithNullCondition(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'rel1',
            'cmp1',
            'rel2'
        );

        $filter = $this->createFilter('neq', 'Acme');
        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        $debugOutput = $queryBuilder->getDebugOutput();

        Assert::assertStringContainsString('((cmp1.company_name IS NULL) OR (cmp1.company_name', $debugOutput);
        Assert::assertStringContainsString(')', $debugOutput);
    }

    public function testApplyQueryAppliesStartsWithOperator(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'rel1',
            'cmp1'
        );

        $filter = $this->createFilter('startsWith', 'Acme');
        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        $debugOutput = $queryBuilder->getDebugOutput();

        Assert::assertStringContainsString("(cmp1.company_name LIKE 'Acme')", $debugOutput);
    }

    public function testApplyQueryAppliesGtOperator(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'rel1',
            'cmp1'
        );

        $filter = $this->createFilter('gt', 5);
        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        $debugOutput = $queryBuilder->getDebugOutput();

        Assert::assertStringContainsString('(cmp1.company_name > 5)', $debugOutput);
    }

    public function testApplyQueryAppliesNotInOperatorAndMissingRelationHandling(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'par2',
            'rel1',
            'cmp1',
            'rel2'
        );

        $filter = $this->createFilter('notIn', [1, 2]);
        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        $debugOutput = $queryBuilder->getDebugOutput();

        Assert::assertStringContainsString('(cmp1.company_name NOT IN (1, 2)', $debugOutput);
        Assert::assertStringContainsString('cmp1.company_name IS NULL', $debugOutput);
        Assert::assertStringContainsString(
            'OR (NOT EXISTS(SELECT 1 FROM '.MAUTIC_TABLE_PREFIX.'companies_leads rel2 WHERE (rel2.lead_id = l.id) AND (rel2.is_primary = 1))',
            $debugOutput
        );
    }

    public function testApplyQueryAppliesNegatedMultiselectOperator(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'par2',
            'rel1',
            'cmp1',
            'rel2'
        );

        $filter = $this->createFilter('!multiselect', ['alpha', 'beta']);
        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        $debugOutput = $queryBuilder->getDebugOutput();

        Assert::assertStringContainsString("NOT cmp1.company_name REGEXP 'alpha'", $debugOutput);
        Assert::assertStringContainsString("NOT cmp1.company_name REGEXP 'beta'", $debugOutput);
    }

    public function testApplyQuerySupportsEqualOperator(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'rel1',
            'cmp1'
        );

        $filter = $this->createFilter('eq', 'Acme');
        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        $debugOutput = $queryBuilder->getDebugOutput();

        Assert::assertStringContainsString("cmp1.company_name = 'Acme'", $debugOutput);
        Assert::assertStringNotContainsString('OR NOT EXISTS', $debugOutput);
    }

    public function testApplyQueryThrowsOnUnsupportedOperator(): void
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->randomParameter->method('generateRandomParameterName')->willReturnOnConsecutiveCalls(
            'par1',
            'rel1',
            'cmp1'
        );

        $filter = $this->createFilter('unsupported', 'value');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported filter operator "unsupported".');

        $this->queryBuilder->applyQuery($queryBuilder, $filter);
    }
}
