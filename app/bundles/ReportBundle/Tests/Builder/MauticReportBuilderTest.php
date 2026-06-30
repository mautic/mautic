<?php

declare(strict_types=1);

namespace Mautic\ReportBundle\Tests\Builder;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Test\Doctrine\MockedConnectionTrait;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\ReportBundle\Builder\MauticReportBuilder;
use Mautic\ReportBundle\Entity\Report;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class MauticReportBuilderTest extends TestCase
{
    use MockedConnectionTrait;
    /**
     * @var \PHPUnit\Framework\MockObject\Stub|EventDispatcherInterface
     */
    private \PHPUnit\Framework\MockObject\Stub $dispatcher;

    /**
     * @var MockObject|Connection
     */
    private MockObject $connection;

    private ChannelListHelper $channelListHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher          = $this->createStub(EventDispatcherInterface::class);
        $this->connection          = $this->getMockedConnection();
        $this->channelListHelper   = new ChannelListHelper($this->createStub(EventDispatcher::class), $this->createStub(Translator::class));

        $this->connection->method('createQueryBuilder')->willReturnOnConsecutiveCalls(
            new QueryBuilder($this->connection),
            new QueryBuilder($this->connection),
            new QueryBuilder($this->connection),
        );
        $this->connection->method('getExpressionBuilder')->willReturn(new ExpressionBuilder($this->connection));
        $this->connection->method('quote')->willReturnMap([['', null, "''"]]);
    }

    public function testColumnSanitization(): void
    {
        $report = new Report();
        $report->setColumns(['a.b', 'b.c']);
        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => ['a.b' => [], 'b.c' => []],
        ]);
        Assert::assertSame('SELECT `a`.`b`, `b`.`c`', $query->getSql());
    }

    public function testFiltersWithEmptyAndNotEmptyDateTypes(): void
    {
        $report = $this->buildReportWithFilters([
            $this->buildFilter('a.emptyDate', 'empty'),
            $this->buildFilter('a.notEmptyDate', 'notEmpty'),
            $this->buildFilter('a.emptyDateTime', 'empty'),
            $this->buildFilter('a.notEmptyDateTime', 'notEmpty'),
            $this->buildFilter('a.emptyString', 'empty'),
            $this->buildFilter('a.notEmptyString', 'notEmpty'),
        ]);
        $query = $this->buildQueryWithFilters($report, [
            'a.emptyDate'        => $this->buildFilterDefinition('Empty date', 'date', 'emptyDate'),
            'a.notEmptyDate'     => $this->buildFilterDefinition('Not empty date', 'date', 'notEmptyDate'),
            'a.emptyDateTime'    => $this->buildFilterDefinition('Empty date time', 'datetime', 'emptyDateTime'),
            'a.notEmptyDateTime' => $this->buildFilterDefinition('Not empty date time', 'datetime', 'notEmptyDateTime'),
            'a.emptyString'      => $this->buildFilterDefinition('Empty string', 'string', 'emptyString'),
            'a.notEmptyString'   => $this->buildFilterDefinition('Not empty string', 'string', 'notEmptyString'),
        ]);
        Assert::assertSame(trim(preg_replace('/\s{2,}/', ' ', "
            SELECT
                `a`.`someField`
            WHERE
                (a.emptyDate IS NULL)
                AND (a.notEmptyDate IS NOT NULL)
                AND (a.emptyDateTime IS NULL)
                AND (a.notEmptyDateTime IS NOT NULL)
                AND ((a.emptyString IS NULL) OR (a.emptyString = ''))
                AND (a.notEmptyString IS NOT NULL) AND (a.notEmptyString <> '')
        ")), $query->getSql());
    }

    public function testFiltersWithEmptyAndNotEmptyDateTypes2(): void
    {
        $report = new Report();
        $report->setColumns(['a.someField']);
        $report->setFilters([
            [
                'column'    => 'a.notEqualString',
                'glue'      => 'and',
                'value'     => '',
                'condition' => 'neq',
            ],
        ]);
        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => ['a.someField' => []],
            'filters' => [
                'a.notEqualString' => [
                    'label' => 'Not equal string',
                    'type'  => 'string',
                    'alias' => 'notEqualString',
                ],
            ],
        ]);
        Assert::assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `a`.`someField` WHERE (a.notEqualString IS NULL) OR (a.notEqualString <> :i0canotEqualString)
        ')), $query->getSql());
    }

    public function testEmptyOrFilterValueDoesNotCreateEmptyOrGroup(): void
    {
        $report = $this->buildReportWithFilters([
            $this->buildFilter('a.isPublished', 'eq', '1'),
            $this->buildFilter('a.name', 'contains', '', 'or'),
        ]);
        $query = $this->buildQueryWithFilters($report, $this->buildPublishedAndNameFilterDefinitions());

        Assert::assertSame('SELECT `a`.`someField` WHERE a.isPublished = :i0caisPublished', $query->getSql());
    }

    public function testOrFiltersKeepRemainingAndGroup(): void
    {
        $report = $this->buildReportWithFilters([
            $this->buildFilter('a.isPublished', 'eq', '1'),
            $this->buildFilter('a.name', 'contains', 'John', 'or'),
            $this->buildFilter('a.email', 'contains', 'example.com'),
        ]);
        $query = $this->buildQueryWithFilters($report, [
            'a.isPublished' => $this->buildFilterDefinition('Is published', 'bool', 'isPublished'),
            'a.name'        => $this->buildFilterDefinition('Name', 'string', 'name'),
            'a.email'       => $this->buildFilterDefinition('Email', 'email', 'email'),
        ]);

        Assert::assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `a`.`someField` WHERE (a.isPublished = :i0caisPublished) OR ((a.name LIKE :i1caname) AND (a.email LIKE :i2caemail))
        ')), $query->getSql());
    }

    public function testSingleOrGroupIsAppliedWithoutExtraOrExpression(): void
    {
        $report = $this->buildReportWithFilters([
            $this->buildFilter('a.isPublished', 'eq', '1'),
            $this->buildFilter('a.reset', 'eq', '2', 'or'),
        ]);
        $query = $this->buildQueryWithFilters($report, [
            'a.isPublished' => $this->buildFilterDefinition('Is published', 'bool', 'isPublished'),
            'a.reset'       => $this->buildFilterDefinition('Reset', 'bool', 'reset'),
        ]);

        Assert::assertSame('SELECT `a`.`someField` WHERE a.isPublished = :i0caisPublished', $query->getSql());
    }

    public function testReportWithPreciseAvg(): void
    {
        $report = new Report();
        $report->setColumns(['a.id']);
        $report->setGroupBy(['a.id']);
        $report->setAggregators([
            [
                'column'    => 'a.bounced',
                'function'  => 'AVG',
            ],
        ]);

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'a.id'      => [],
                'a.bounced' => [
                    'formula' => 'IF(dnc.id IS NOT NULL AND dnc.reason=2, 1, 0)',
                ],
            ],
            'aggregators' => [
                'a.bounced' => [
                    'label' => 'AVG bounced',
                    'type'  => 'float',
                    'alias' => 'avgBounced',
                ],
            ],
            'groupBy' => ['a.id'],
        ]);

        Assert::assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `a`.`id`, AVG(IF(dnc.id IS NOT NULL AND dnc.reason=2, 1, 0)) AS \'AVG a.bounced\' GROUP BY a.id
        ')), $query->getSql());
    }

    public function testFiltersWithTag(): void
    {
        $report = new Report();
        $report->setSource('leads');
        $report->setColumns([
            'l.id',
            'l.email',
        ]);
        $report->setFilters([
            [
                'column'    => 'tag',
                'glue'      => 'and',
                'value'     => ['1', '2'],
                'condition' => 'in',
            ],
            [
                'column'    => 'tag',
                'glue'      => 'and',
                'value'     => ['3'],
                'condition' => 'notIn',
            ],
        ]);
        $builder = $this->buildBuilder($report);

        $query   = $builder->getQuery([
            'columns' => [
                'l.id'    => [],
                'l.email' => [],
            ],
            'filters' => [
                'tag' => [
                    'label' => 'Tag',
                    'type'  => 'multiselect',
                    'list'  => [
                        1 => 'A',
                        2 => 'B',
                        3 => 'C',
                    ],
                    'operators' => [
                        'in'    => 'mautic.core.operator.in',
                        'notIn' => 'mautic.core.operator.notin',
                    ],
                    'alias' => 'tag',
                ],
            ],
        ]);

        Assert::assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `l`.`id`, `l`.`email` WHERE (l.id IN (SELECT DISTINCT lead_id FROM '.MAUTIC_TABLE_PREFIX.'lead_tags_xref ltx WHERE ltx.tag_id IN (1, 2))) AND (l.id NOT IN (SELECT DISTINCT lead_id FROM '.MAUTIC_TABLE_PREFIX.'lead_tags_xref ltx WHERE ltx.tag_id IN (3)))
        ')), $query->getSql());
    }

    public function testApplyTagFilter(): void
    {
        $filters = [
            [
                'column'    => 'tag',
                'glue'      => 'and',
                'value'     => ['1', '2'],
                'condition' => 'in',
            ],
            [
                'column'    => 'tag',
                'glue'      => 'and',
                'value'     => ['3'],
                'condition' => 'notIn',
            ],
            [
                'column'    => 'unicorn',
                'glue'      => 'and',
                'value'     => ['3'],
                'condition' => 'notIn',
            ],
        ];

        $builder   = $this->buildBuilder(new Report());
        $groupExpr = CompositeExpression::and($builder->getTagCondition($filters[0]), $builder->getTagCondition($filters[1]));
        Assert::assertSame('(l.id IN (SELECT DISTINCT lead_id FROM '.MAUTIC_TABLE_PREFIX.'lead_tags_xref ltx WHERE ltx.tag_id IN (1, 2))) AND (l.id NOT IN (SELECT DISTINCT lead_id FROM '.MAUTIC_TABLE_PREFIX.'lead_tags_xref ltx WHERE ltx.tag_id IN (3)))', $groupExpr->__toString());
        Assert::assertNull($builder->getTagCondition($filters[2]));
    }

    private function buildBuilder(Report $report): MauticReportBuilder
    {
        return new MauticReportBuilder(
            $this->dispatcher,
            $this->connection,
            $report,
            $this->channelListHelper
        );
    }

    /**
     * @param mixed[] $filters
     */
    private function buildReportWithFilters(array $filters): Report
    {
        $report = new Report();
        $report->setColumns(['a.someField']);
        $report->setFilters($filters);

        return $report;
    }

    /**
     * @param mixed[] $filterDefinitions
     */
    private function buildQueryWithFilters(Report $report, array $filterDefinitions): QueryBuilder
    {
        return $this->buildBuilder($report)->getQuery([
            'columns' => ['a.someField' => []],
            'filters' => $filterDefinitions,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function buildFilter(string $column, string $condition, string $value = '', string $glue = 'and'): array
    {
        return [
            'column'    => $column,
            'glue'      => $glue,
            'value'     => $value,
            'condition' => $condition,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildFilterDefinition(string $label, string $type, string $alias): array
    {
        return [
            'label' => $label,
            'type'  => $type,
            'alias' => $alias,
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function buildPublishedAndNameFilterDefinitions(): array
    {
        return [
            'a.isPublished' => $this->buildFilterDefinition('Is published', 'bool', 'isPublished'),
            'a.name'        => $this->buildFilterDefinition('Name', 'string', 'name'),
        ];
    }
}
