<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
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
use Symfony\Component\Translation\TranslatorInterface;

class ForeignValueFilterQueryBuilderTest extends TestCase
{
    /**
     * @var RandomParameterName
     */
    private $randomParameter;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private $dispatcher;

    /**
     * @var ForeignValueFilterQueryBuilder
     */
    private $queryBuilder;

    /**
     * @var Connection|MockObject
     */
    private $connectionMock;

    public function setUp(): void
    {
        parent::setUp();
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
        $this->randomParameter     = new RandomParameterName();
        $this->dispatcher          = $this->createMock(EventDispatcherInterface::class);
        $this->connectionMock      = $this->createMock(Connection::class);
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

    public function dataApplyQuery(): iterable
    {
        yield ['regexp', '.com$', "SELECT 1 FROM leads l WHERE EXISTS(SELECT NULL FROM page_hits par1 WHERE par1.url REGEXP '.com$')"];
        yield ['notRegexp', '.com$', "SELECT 1 FROM leads l WHERE EXISTS(SELECT NULL FROM page_hits par1 WHERE par1.url NOT REGEXP '.com$')"];
        yield ['eq', 'https://acquia.com', "SELECT 1 FROM leads l WHERE l.id IN (SELECT par1.lead_id FROM page_hits par1 WHERE par1.url = 'https://acquia.com')"];
        yield ['neq', 'https://acquia.com', "SELECT 1 FROM leads l WHERE NOT EXISTS(SELECT NULL FROM page_hits par1 WHERE (par1.lead_id = l.id) AND ((par1.url = 'https://acquia.com') OR (par1.url IS NULL)))"];
        yield ['empty', '1', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par1.lead_id FROM page_hits par1 WHERE par1.url IS NULL)'];
        yield ['notEmpty', '1', 'SELECT 1 FROM leads l WHERE l.id IN (SELECT par1.lead_id FROM page_hits par1 WHERE par1.url IS NOT NULL)'];
        yield ['like', '%.com', "SELECT 1 FROM leads l WHERE l.id IN (SELECT par1.lead_id FROM page_hits par1 WHERE par1.url LIKE '%.com')"];
        yield ['notLike', '%.com', "SELECT 1 FROM leads l WHERE NOT EXISTS(SELECT NULL FROM page_hits par1 WHERE (par1.lead_id = l.id) AND ((par1.url IS NULL) OR (par1.url LIKE '%.com')))"];
        yield ['contains', '.com', "SELECT 1 FROM leads l WHERE l.id IN (SELECT par1.lead_id FROM page_hits par1 WHERE par1.url LIKE '%.com%')"];
        yield ['startsWith', 'https://', "SELECT 1 FROM leads l WHERE l.id IN (SELECT par1.lead_id FROM page_hits par1 WHERE par1.url LIKE 'https://%')"];
        yield ['endsWith', '.com', "SELECT 1 FROM leads l WHERE l.id IN (SELECT par1.lead_id FROM page_hits par1 WHERE par1.url LIKE '%.com')"];
    }

    /**
     * @dataProvider dataApplyQuery
     */
    public function testApplyQuery(string $operator, string $parameterValue, string $expectedQuery): void
    {
        $queryBuilder = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $filter = $this->getContactSegmentFilter($operator, $parameterValue);

        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        Assert::assertSame($expectedQuery, $queryBuilder->getDebugOutput());
    }

    private function getContactSegmentFilter(string $operator, string $parameterValue, array $batchLimiters = []): ContactSegmentFilter
    {
        return new ContactSegmentFilter(
            new ContactSegmentFilterCrate(
                [
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
                ]
            ),
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
