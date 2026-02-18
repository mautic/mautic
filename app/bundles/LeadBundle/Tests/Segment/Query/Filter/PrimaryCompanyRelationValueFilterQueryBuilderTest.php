<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Test\Doctrine\MockedConnectionTrait;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\Query\Filter\PrimaryCompanyRelationValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PrimaryCompanyRelationValueFilterQueryBuilderTest extends TestCase
{
    use MockedConnectionTrait;

    /**
     * @var MockObject|RandomParameterName
     */
    private MockObject $randomParameter;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $dispatcher;

    /**
     * @var Connection&MockObject
     */
    private MockObject $connectionMock;

    private PrimaryCompanyRelationValueFilterQueryBuilder $queryBuilder;

    protected function setUp(): void
    {
        $this->randomParameter = $this->createMock(RandomParameterName::class);
        $this->dispatcher      = $this->createMock(EventDispatcherInterface::class);
        $this->connectionMock  = $this->getMockedConnection();

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
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

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
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

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
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

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
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

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
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

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
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

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
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

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
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

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
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

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

    /**
     * @param array<string, mixed> $batch
     */
    private function createFilter(string $operator, mixed $value, array $batch = []): ContactSegmentFilter
    {
        return new class($operator, $value, $batch) extends ContactSegmentFilter {
            /**
             * @param array<string, mixed> $batch
             */
            public function __construct(
                private string $operator,
                private mixed $value,
                private array $batch,
            ) {
            }

            public function getOperator(): string
            {
                return $this->operator;
            }

            public function getParameterValue(): mixed
            {
                return $this->value;
            }

            /**
             * @return array<string>|string
             */
            public function getParameterHolder(mixed $argument): array|string
            {
                if (is_array($argument)) {
                    return array_map(
                        static fn ($value) => ':'.$value,
                        $argument
                    );
                }

                return ':'.$argument;
            }

            public function getField(): string
            {
                return 'company_name';
            }

            public function getTable(): string
            {
                return MAUTIC_TABLE_PREFIX.'companies';
            }

            public function getRelationJoinTable(): string
            {
                return MAUTIC_TABLE_PREFIX.'companies_leads';
            }

            public function getRelationJoinTableField(): string
            {
                return 'company_id';
            }

            public function getGlue(): string
            {
                return 'and';
            }

            public function getBatchLimiters(): array
            {
                return $this->batch;
            }
        };
    }
}
