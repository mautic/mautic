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
     * @var MockObject|EventDispatcherInterface
     */
    private MockObject $dispatcher;

    /**
     * @var MockObject|Connection
     */
    private MockObject $connection;

    private ChannelListHelper $channelListHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dispatcher          = $this->createMock(EventDispatcherInterface::class);
        $this->connection          = $this->getMockedConnection();
        $this->channelListHelper   = new ChannelListHelper($this->createMock(EventDispatcher::class), $this->createMock(Translator::class));

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
        $report = new Report();
        $report->setColumns(['a.someField']);
        $report->setFilters([
            [
                'column'    => 'a.emptyDate',
                'glue'      => 'and',
                'value'     => '',
                'condition' => 'empty',
            ],
            [
                'column'    => 'a.notEmptyDate',
                'glue'      => 'and',
                'value'     => '',
                'condition' => 'notEmpty',
            ],
            [
                'column'    => 'a.emptyDateTime',
                'glue'      => 'and',
                'value'     => '',
                'condition' => 'empty',
            ],
            [
                'column'    => 'a.notEmptyDateTime',
                'glue'      => 'and',
                'value'     => '',
                'condition' => 'notEmpty',
            ],
            [
                'column'    => 'a.emptyString',
                'glue'      => 'and',
                'value'     => '',
                'condition' => 'empty',
            ],
            [
                'column'    => 'a.notEmptyString',
                'glue'      => 'and',
                'value'     => '',
                'condition' => 'notEmpty',
            ],
        ]);
        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => ['a.someField' => []],
            'filters' => [
                'a.emptyDate' => [
                    'label' => 'Empty date',
                    'type'  => 'date',
                    'alias' => 'emptyDate',
                ],
                'a.notEmptyDate' => [
                    'label' => 'Not empty date',
                    'type'  => 'date',
                    'alias' => 'notEmptyDate',
                ],
                'a.emptyDateTime' => [
                    'label' => 'Empty date time',
                    'type'  => 'datetime',
                    'alias' => 'emptyDateTime',
                ],
                'a.notEmptyDateTime' => [
                    'label' => 'Not empty date time',
                    'type'  => 'datetime',
                    'alias' => 'notEmptyDateTime',
                ],
                'a.emptyString' => [
                    'label' => 'Empty string',
                    'type'  => 'string',
                    'alias' => 'emptyString',
                ],
                'a.notEmptyString' => [
                    'label' => 'Not empty string',
                    'type'  => 'string',
                    'alias' => 'notEmptyString',
                ],
            ],
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
}
