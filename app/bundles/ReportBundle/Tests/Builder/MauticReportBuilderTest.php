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
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class MauticReportBuilderTest extends TestCase
{
    use MockedConnectionTrait;

    /**
     * @var MockObject|Connection
     */
    private MockObject $connection;

    private ChannelListHelper $channelListHelper;

    protected function setUp(): void
    {
        parent::setUp();
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
        $this->assertSame('SELECT `a`.`b`, `b`.`c`', $query->getSql());
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
        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', "
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
        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
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

        $this->assertSame('SELECT `a`.`someField` WHERE a.isPublished = :i0caisPublished', $query->getSql());
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

        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
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

        $this->assertSame('SELECT `a`.`someField` WHERE a.isPublished = :i0caisPublished', $query->getSql());
    }

    public function testGroupByWithCountOmitsNonGroupedSelectColumns(): void
    {
        $report = new Report();
        $report->setColumns(['ph.url_title', 'ph.url', 'ph.id']);
        $report->setGroupBy(['ph.url', 'ph.url_title']);
        $report->setAggregators([
            [
                'column'   => 'ph.id',
                'function' => 'COUNT',
            ],
        ]);

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'ph.url_title' => [],
                'ph.url'       => [],
                'ph.id'        => [],
            ],
            'groupBy' => ['ph.url', 'ph.url_title'],
        ]);

        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `ph`.`url_title`, `ph`.`url`, COUNT(ph.id) AS \'COUNT ph.id\' GROUP BY ph.url, ph.url_title
        ')), $query->getSql());
        $this->assertStringNotContainsString('`ph`.`id`', $query->getSql());
    }

    public function testGroupByCompletesMissingSelectColumnInsteadOfDroppingIt(): void
    {
        $report = new Report();
        $report->setColumns(['ph.url', 'p.title']);
        $report->setGroupBy(['ph.url']);

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'ph.url'  => [],
                'p.title' => [],
            ],
            'groupBy' => ['ph.url'],
        ]);

        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `ph`.`url`, `p`.`title` GROUP BY ph.url, p.title
        ')), $query->getSql());
    }

    public function testGroupByCompletesFunctionallyDependentSelectColumn(): void
    {
        $report = new Report();
        $report->setColumns(['e.id', 'e.subject']);
        $report->setGroupBy(['e.id']);

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'e.id'      => [],
                'e.subject' => [],
            ],
            'groupBy' => ['e.id'],
        ]);

        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `e`.`id`, `e`.`subject` GROUP BY e.id, e.subject
        ')), $query->getSql());
    }

    public function testGroupByCompletesMissingOrderByColumn(): void
    {
        $report = new Report();
        $report->setColumns(['ph.url']);
        $report->setGroupBy(['ph.url']);

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'ph.url' => [],
            ],
            'order'   => ['p.title', 'DESC'],
            'groupBy' => ['ph.url'],
        ]);

        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `ph`.`url` GROUP BY ph.url, p.title ORDER BY p.title DESC
        ')), $query->getSql());
    }

    public function testGroupByKeepsAggregatorTargetOutOfGroupByWhenOrdered(): void
    {
        $report = new Report();
        $report->setColumns(['ph.url']);
        $report->setGroupBy(['ph.url']);
        $report->setAggregators([
            [
                'column'   => 'ph.id',
                'function' => 'COUNT',
            ],
        ]);

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'ph.url' => [],
            ],
            'order'   => ['ph.id', 'DESC'],
            'groupBy' => ['ph.url'],
        ]);

        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `ph`.`url`, COUNT(ph.id) AS \'COUNT ph.id\' GROUP BY ph.url ORDER BY ph.id DESC
        ')), $query->getSql());
    }

    public function testGroupByDeduplicatesColumnsPreRegisteredByListener(): void
    {
        $report = new Report();
        $report->setColumns(['ph.url']);
        $report->setGroupBy(['ph.url']);

        // Report subscribers may register GROUP BY columns of their own before the
        // builder runs; the builder's own GROUP BY must merge with them, not append
        // a second copy of the same column.
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(
            ReportEvents::REPORT_ON_GENERATE,
            function (ReportGeneratorEvent $event): void {
                $event->getQueryBuilder()->addGroupBy('ph.url');
            }
        );

        $builder = new MauticReportBuilder($dispatcher, $this->connection, $report, $this->channelListHelper);
        $query   = $builder->getQuery([
            'columns' => [
                'ph.url' => [],
            ],
            'groupBy' => ['ph.url'],
        ]);

        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `ph`.`url` GROUP BY ph.url
        ')), $query->getSql());
    }

    public function testGroupByCompletesBaseColumnsOfFormulaSelect(): void
    {
        $report = new Report();
        $report->setColumns(['ph.url', 'ph.date_hit']);
        $report->setGroupBy(['ph.url']);

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'ph.url'      => [],
                'ph.date_hit' => ['formula' => 'DATE(ph.date_hit)'],
            ],
            'groupBy' => ['ph.url'],
        ]);

        // The formula stays in SELECT; its base column is what gets grouped.
        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `ph`.`url`, DATE(ph.date_hit) GROUP BY ph.url, ph.date_hit
        ')), $query->getSql());
    }

    public function testGroupByDoesNotDuplicateQuotedVariantOfGroupedColumn(): void
    {
        $report = new Report();
        $report->setColumns(['ph.url']);
        $report->setGroupBy(['ph.url']);

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'ph.url' => ['groupByFormula' => '`ph`.`url`'],
            ],
            'order'   => ['ph.url', 'ASC'],
            'groupBy' => ['ph.url'],
        ]);

        // ORDER BY ph.url is already covered by the (quoted) GROUP BY expression.
        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `ph`.`url` GROUP BY `ph`.`url` ORDER BY ph.url ASC
        ')), $query->getSql());
    }

    public function testGroupByDoesNotPullAggregateArgumentsOrOrderByAggregates(): void
    {
        $report = new Report();
        $report->setColumns(['ph.url', 't.hits']);
        $report->setGroupBy(['ph.url']);
        $report->setAggregators([
            [
                'column'   => 'ph.id',
                'function' => 'COUNT',
            ],
        ]);

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'ph.url' => [],
                't.hits' => ['formula' => 'MAX(t.hits)'],
            ],
            'order'   => ['COUNT(ph.id)', 'DESC'],
            'groupBy' => ['ph.url'],
        ]);

        // MAX(t.hits) is already aggregated, so t.hits must not be grouped; the
        // COUNT(ph.id) sort column is an aggregator expression, not a column.
        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
            SELECT `ph`.`url`, MAX(t.hits), COUNT(ph.id) AS \'COUNT ph.id\' GROUP BY ph.url ORDER BY COUNT(ph.id) DESC
        ')), $query->getSql());
    }

    public function testGroupByIgnoresIdentifiersInsideCorrelatedSubqueryFormulas(): void
    {
        $report = new Report();
        $report->setColumns(['e.id', 'unsubscribed_ratio']);
        $report->setGroupBy(['e.id']);

        // Shape of the email ratio columns: a correlated subquery with its own
        // aggregate over the do-not-contact table.
        $formula = "IFNULL((SELECT ROUND((SUM(IF(dnc.id IS NOT NULL AND dnc.channel_id=e.id AND dnc.reason=1, 1, 0))/e.sent_count)*100, 1) FROM lead_donotcontact dnc), '0.0')";

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'e.id'                => [],
                'unsubscribed_ratio' => ['formula' => $formula],
            ],
            'groupBy' => ['e.id'],
        ]);

        // Nothing from inside the correlated subquery may leak into the outer
        // GROUP BY: its identifiers are aggregated or already covered by the
        // outer grouping key.
        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', "
            SELECT `e`.`id`, $formula GROUP BY e.id
        ")), $query->getSql());
    }

    public function testGroupByIgnoresDoubleQuotedStringLiteralsInFormulas(): void
    {
        $report = new Report();
        $report->setColumns(['e.id', 'focus_views']);
        $report->setGroupBy(['e.id']);

        // Shape of the FocusBundle report columns: a CASE expression whose
        // condition compares a column to a double-quoted string literal.
        $formula = 'CASE WHEN fs.type = "view" THEN 1 ELSE 0 END';

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'e.id'        => [],
                'focus_views' => ['formula' => $formula],
            ],
            'groupBy' => ['e.id'],
        ]);

        // The contents of the double-quoted literal ("view") are a string, not
        // a column: only the CASE's real column reference may be completed into
        // the outer GROUP BY.
        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', "
            SELECT `e`.`id`, $formula GROUP BY e.id, fs.type
        ")), $query->getSql());
    }

    public function testGroupByStripDoesNotSwallowSubqueryAfterDoubleQuotedLiteral(): void
    {
        $report = new Report();
        $report->setColumns(['e.id', 'hit_count']);
        $report->setGroupBy(['e.id']);

        // Shape of the FocusBundle hit_count column: the CASE condition's
        // double-quoted literal is followed by a parenthesized subquery that
        // itself contains GROUP BY. A strip pattern that spans from one
        // double-quoted literal to the next would swallow the subquery's
        // opening parenthesis, and its GROUP BY keywords would then leak into
        // the outer GROUP BY as identifiers (invalid SQL).
        $formula = 'CASE WHEN fs.type = "view" THEN (
                        SELECT COUNT(fs2.id)
                        FROM test_focus_stats fs2
                        WHERE fs2.type = "view"
                        AND fs2.focus_id = fs.focus_id
                        GROUP BY fs2.focus_id
                    ) ELSE MAX(fs.hits) END';

        $builder = $this->buildBuilder($report);
        $query   = $builder->getQuery([
            'columns' => [
                'e.id'       => [],
                'hit_count'  => ['formula' => $formula],
            ],
            'groupBy' => ['e.id'],
        ]);

        $sql = $query->getSql();
        $this->assertStringNotContainsString('fs2.focus_id, GROUP', $sql);
        $this->assertStringNotContainsString('GROUP BY GROUP', $sql);
        // The CASE's base column is completed; nothing from the subquery scope is.
        $this->assertSame(
            trim(preg_replace('/\s{2,}/', ' ', "
            SELECT `e`.`id`, $formula GROUP BY e.id, fs.type
        ")),
            trim(preg_replace('/\s{2,}/', ' ', $sql))
        );
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

        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
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

        $this->assertSame(trim(preg_replace('/\s{2,}/', ' ', '
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
        $this->assertSame('(l.id IN (SELECT DISTINCT lead_id FROM '.MAUTIC_TABLE_PREFIX.'lead_tags_xref ltx WHERE ltx.tag_id IN (1, 2))) AND (l.id NOT IN (SELECT DISTINCT lead_id FROM '.MAUTIC_TABLE_PREFIX.'lead_tags_xref ltx WHERE ltx.tag_id IN (3)))', $groupExpr->__toString());
        $this->assertNull($builder->getTagCondition($filters[2]));
    }

    private function buildBuilder(Report $report): MauticReportBuilder
    {
        return new MauticReportBuilder(
            $this->createStub(EventDispatcherInterface::class),
            $this->connection,
            $report,
            $this->channelListHelper
        );
    }

    /**
     * @param array<int, array<string, string>> $filters
     */
    private function buildReportWithFilters(array $filters): Report
    {
        $report = new Report();
        $report->setColumns(['a.someField']);
        $report->setFilters($filters);

        return $report;
    }

    /**
     * @param array<string, array<string, string>> $filterDefinitions
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
