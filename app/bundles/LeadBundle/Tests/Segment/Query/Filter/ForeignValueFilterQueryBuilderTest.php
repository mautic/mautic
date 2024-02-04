<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Test\Doctrine\MockedConnectionTrait;
use Mautic\LeadBundle\Provider\FilterOperatorProvider;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterOperator;
use Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignValueFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Mautic\LeadBundle\Segment\TableSchemaColumnsCache;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ForeignValueFilterQueryBuilderTest extends TestCase
{
    use MockedConnectionTrait;
    private RandomParameterName $randomParameter;

    /**
     * @var EventDispatcherInterface&MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $dispatcher;

    private ForeignValueFilterQueryBuilder $queryBuilder;

    /**
     * @var Connection&MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $connectionMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->randomParameter     = new RandomParameterName();
        $this->dispatcher          = $this->createMock(EventDispatcherInterface::class);
        $this->connectionMock      = $this->getMockedConnection();
        $this->queryBuilder        = new ForeignValueFilterQueryBuilder(
            $this->randomParameter,
            $this->dispatcher
        );
    }

    public function testGetServiceId(): void
    {
        $this->assertEquals(
            'mautic.lead.query.builder.foreign.value',
            $this->queryBuilder::getServiceId()
        );
    }

    /**
     * @return array<mixed>
     */
    public function dataApplyQuery(): iterable
    {
        yield ['regexp', '.com$', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.url REGEXP '.com$')"];
        yield ['notRegexp', '.com$', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.url NOT REGEXP '.com$')"];
        yield ['eq', 'https://acquia.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.url = 'https://acquia.com')"];
        yield ['neq', 'https://acquia.com', "SELECT 1 FROM __PREFIX__leads l WHERE NOT EXISTS(SELECT NULL FROM __PREFIX__page_hits par1 WHERE (par1.lead_id = l.id) AND ((par1.url = 'https://acquia.com') OR (par1.url IS NULL)))"];
        yield ['empty', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id NOT IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1)'];
        yield ['notEmpty', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1)'];
        yield ['like', '%.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.url LIKE '%.com')"];
        yield ['notLike', '%.com', "SELECT 1 FROM __PREFIX__leads l WHERE NOT EXISTS(SELECT NULL FROM __PREFIX__page_hits par1 WHERE (par1.lead_id = l.id) AND ((par1.url IS NULL) OR (par1.url LIKE '%.com')))"];
        yield ['contains', '.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.url LIKE '%.com%')"];
        yield ['startsWith', 'https://', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.url LIKE 'https://%')"];
        yield ['endsWith', '.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.url LIKE '%.com')"];
    }

    /**
     * @dataProvider dataApplyQuery
     */
    public function testApplyQuery(string $operator, string $parameterValue, string $expectedQuery): void
    {
        $expectedQuery = str_replace('__PREFIX__', MAUTIC_TABLE_PREFIX, $expectedQuery);
        $queryBuilder  = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $filter = $this->getContactSegmentFilter([
            'object'     => 'behaviors',
            'glue'       => 'and',
            'field'      => 'hit_url',
            'type'       => 'text',
            'operator'   => $operator,
            'properties' => [
                'filter' => $parameterValue,
            ],
            'filter'  => null,
            'display' => null,
        ]);

        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        Assert::assertSame($expectedQuery, $queryBuilder->getDebugOutput());
    }

    /**
     * @return array<mixed>
     */
    public function dataApplyQueryAdditionalFilters(): iterable
    {
        yield ['in', [1, 2], 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par2.lead_id FROM __PREFIX__lead_categories par2 WHERE par2.category_id IN (1, 2))'];
        yield ['notIn', [1, 2], 'SELECT 1 FROM __PREFIX__leads l WHERE NOT EXISTS(SELECT NULL FROM __PREFIX__lead_categories par2 WHERE (par2.lead_id = l.id) AND (par2.category_id IN (1, 2)))'];
    }

    /**
     * @dataProvider dataApplyQueryAdditionalFilters
     *
     * @param array<string, mixed> $parameterValue
     */
    public function testApplyQueryAdditionalFilters(string $operator, array $parameterValue, string $expectedQuery): void
    {
        $expectedQuery = str_replace('__PREFIX__', MAUTIC_TABLE_PREFIX, $expectedQuery);
        $queryBuilder  = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $filter = $this->getContactSegmentFilter([
            'glue'       => 'and',
            'field'      => 'globalcategory',
            'object'     => 'lead',
            'type'       => 'globalcategory',
            'operator'   => $operator,
            'properties' => [
                'filter' => $parameterValue,
            ],
        ]);

        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        Assert::assertSame($expectedQuery, $queryBuilder->getDebugOutput());
    }

    /**
     * @return array<mixed>
     */
    public function dataApplyQueryWithBatchFilters(): iterable
    {
        yield [['minId' => 1, 'maxId' => 2], 'regexp', '.com$', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id BETWEEN 1 and 2) AND (par1.url REGEXP '.com$'))"];
        yield [['minId' => 1], 'regexp', '.com$', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id >= 1) AND (par1.url REGEXP '.com$'))"];
        yield [['maxId' => 2], 'regexp', '.com$', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id <= 2) AND (par1.url REGEXP '.com$'))"];
        yield [['lead_id' => 1], 'regexp', '.com$', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id = 1) AND (par1.url REGEXP '.com$'))"];

        yield [['minId' => 1, 'maxId' => 2], 'notRegexp', '.com$', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id BETWEEN 1 and 2) AND (par1.url NOT REGEXP '.com$'))"];
        yield [['minId' => 1], 'notRegexp', '.com$', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id >= 1) AND (par1.url NOT REGEXP '.com$'))"];
        yield [['maxId' => 2], 'notRegexp', '.com$', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id <= 2) AND (par1.url NOT REGEXP '.com$'))"];
        yield [['lead_id' => 1], 'notRegexp', '.com$', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id = 1) AND (par1.url NOT REGEXP '.com$'))"];

        yield [['minId' => 1, 'maxId' => 2], 'eq', 'https://acquia.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id BETWEEN 1 and 2) AND (par1.url = 'https://acquia.com'))"];
        yield [['minId' => 1], 'eq', 'https://acquia.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id >= 1) AND (par1.url = 'https://acquia.com'))"];
        yield [['maxId' => 2], 'eq', 'https://acquia.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id <= 2) AND (par1.url = 'https://acquia.com'))"];
        yield [['lead_id' => 1], 'eq', 'https://acquia.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id = 1) AND (par1.url = 'https://acquia.com'))"]; //        yield ['empty', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.url IS NULL)'];

        yield [['minId' => 1, 'maxId' => 2], 'notEmpty', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.lead_id BETWEEN 1 and 2)'];
        yield [['minId' => 1], 'notEmpty', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.lead_id >= 1)'];
        yield [['maxId' => 2], 'notEmpty', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.lead_id <= 2)'];
        yield [['lead_id' => 1], 'notEmpty', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE par1.lead_id = 1)'];

        yield [['minId' => 1, 'maxId' => 2], 'like', '%.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id BETWEEN 1 and 2) AND (par1.url LIKE '%.com'))"];
        yield [['minId' => 1], 'like', '%.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id >= 1) AND (par1.url LIKE '%.com'))"];
        yield [['maxId' => 2], 'like', '%.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id <= 2) AND (par1.url LIKE '%.com'))"];
        yield [['lead_id' => 1], 'like', '%.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id = 1) AND (par1.url LIKE '%.com'))"];

        yield [['minId' => 1, 'maxId' => 2], 'contains', '.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id BETWEEN 1 and 2) AND (par1.url LIKE '%.com%'))"];
        yield [['minId' => 1], 'contains', '.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id >= 1) AND (par1.url LIKE '%.com%'))"];
        yield [['maxId' => 2], 'contains', '.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id <= 2) AND (par1.url LIKE '%.com%'))"];
        yield [['lead_id' => 1], 'contains', '.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id = 1) AND (par1.url LIKE '%.com%'))"];

        yield [['minId' => 1, 'maxId' => 2], 'startsWith', 'https://', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id BETWEEN 1 and 2) AND (par1.url LIKE 'https://%'))"];
        yield [['minId' => 1], 'startsWith', 'https://', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id >= 1) AND (par1.url LIKE 'https://%'))"];
        yield [['maxId' => 2], 'startsWith', 'https://', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id <= 2) AND (par1.url LIKE 'https://%'))"];
        yield [['lead_id' => 1], 'startsWith', 'https://', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id = 1) AND (par1.url LIKE 'https://%'))"];

        yield [['minId' => 1, 'maxId' => 2], 'endsWith', '.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id BETWEEN 1 and 2) AND (par1.url LIKE '%.com'))"];
        yield [['minId' => 1], 'endsWith', '.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id >= 1) AND (par1.url LIKE '%.com'))"];
        yield [['maxId' => 2], 'endsWith', '.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id <= 2) AND (par1.url LIKE '%.com'))"];
        yield [['lead_id' => 1], 'endsWith', '.com', "SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par1.lead_id FROM __PREFIX__page_hits par1 WHERE (par1.lead_id = 1) AND (par1.url LIKE '%.com'))"];
    }

    /**
     * @dataProvider dataApplyQueryWithBatchFilters
     *
     *  @param array<string, mixed> $batchLimiters
     */
    public function testApplyQueryWithBatchFilters(array $batchLimiters, string $operator, string $parameterValue, string $expectedQuery): void
    {
        $expectedQuery = str_replace('__PREFIX__', MAUTIC_TABLE_PREFIX, $expectedQuery);
        $queryBuilder  = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $filter = $this->getContactSegmentFilter([
            'object'     => 'behaviors',
            'glue'       => 'and',
            'field'      => 'hit_url',
            'type'       => 'text',
            'operator'   => $operator,
            'properties' => [
                'filter' => $parameterValue,
            ],
            'filter'  => null,
            'display' => null,
        ], $batchLimiters);

        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        Assert::assertSame($expectedQuery, $queryBuilder->getDebugOutput());
    }

    /**
     * @return array<mixed>
     */
    public function dataApplyQueryAdditionalFiltersWithBatchLimiters(): iterable
    {
        yield [['minId' => 1, 'maxId' => 2], 'in', [1, 2], 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par2.lead_id FROM __PREFIX__lead_categories par2 WHERE (par2.lead_id BETWEEN 1 and 2) AND (par2.category_id IN (1, 2)))'];
        yield [['minId' => 1], 'in', [1, 2], 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par2.lead_id FROM __PREFIX__lead_categories par2 WHERE (par2.lead_id >= 1) AND (par2.category_id IN (1, 2)))'];
        yield [['maxId' => 2], 'in', [1, 2], 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par2.lead_id FROM __PREFIX__lead_categories par2 WHERE (par2.lead_id <= 2) AND (par2.category_id IN (1, 2)))'];
        yield [['lead_id' => 1], 'in', [1, 2], 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT par2.lead_id FROM __PREFIX__lead_categories par2 WHERE (par2.lead_id = 1) AND (par2.category_id IN (1, 2)))'];
    }

    /**
     * @dataProvider dataApplyQueryAdditionalFiltersWithBatchLimiters
     *
     * @param array<string, mixed> $batchLimiters
     * @param array<string, mixed> $parameterValue
     */
    public function testApplyQueryAdditionalFiltersWithBatchLimiters(array $batchLimiters, string $operator, array $parameterValue, string $expectedQuery): void
    {
        $expectedQuery = str_replace('__PREFIX__', MAUTIC_TABLE_PREFIX, $expectedQuery);
        $queryBuilder  = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $filter = $this->getContactSegmentFilter([
            'glue'       => 'and',
            'field'      => 'globalcategory',
            'object'     => 'lead',
            'type'       => 'globalcategory',
            'operator'   => $operator,
            'properties' => [
                'filter' => $parameterValue,
            ],
        ], $batchLimiters);

        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        Assert::assertSame($expectedQuery, $queryBuilder->getDebugOutput());
    }

    /**
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $batchLimiters
     */
    private function getContactSegmentFilter(array $filter, array $batchLimiters = []): ContactSegmentFilter
    {
        return new ContactSegmentFilter(
            new ContactSegmentFilterCrate($filter),
            new CustomMappedDecorator(
                new ContactSegmentFilterOperator(
                    new FilterOperatorProvider($this->dispatcher, $this->createMock(TranslatorInterface::class))
                ),
                new ContactSegmentFilterDictionary($this->dispatcher)
            ),
            new TableSchemaColumnsCache($this->createMock(EntityManager::class)),
            $this->queryBuilder,
            $batchLimiters
        );
    }
}
