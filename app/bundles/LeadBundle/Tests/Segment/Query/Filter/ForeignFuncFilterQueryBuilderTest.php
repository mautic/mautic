<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Test\Doctrine\MockedConnectionTrait;
use Mautic\LeadBundle\Event\SegmentDictionaryGenerationEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Provider\FilterOperatorProvider;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterOperator;
use Mautic\LeadBundle\Segment\Decorator\CustomMappedDecorator;
use Mautic\LeadBundle\Segment\Query\Filter\ForeignFuncFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Mautic\LeadBundle\Segment\TableSchemaColumnsCache;
use Mautic\LeadBundle\Services\ContactSegmentFilterDictionary;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ForeignFuncFilterQueryBuilderTest extends TestCase
{
    use MockedConnectionTrait;
    private RandomParameterName $randomParameter;

    /**
     * @var EventDispatcherInterface&MockObject
     */
    private MockObject $dispatcher;

    private ForeignFuncFilterQueryBuilder $queryBuilder;

    /**
     * @var Connection&MockObject
     */
    private MockObject $connectionMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->randomParameter     = new RandomParameterName();
        $this->dispatcher          = $this->createMock(EventDispatcherInterface::class);
        $this->connectionMock      = $this->getMockedConnection();
        $this->queryBuilder        = new ForeignFuncFilterQueryBuilder(
            $this->randomParameter,
            $this->dispatcher
        );

        $this->connectionMock->method('getDatabase')->willReturn('test_mautic');
        $this->dispatcher->addListener(LeadEvents::SEGMENT_DICTIONARY_ON_GENERATE, [$this, 'onSegmentDictionaryGenerate']);
    }

    public function testGetServiceId(): void
    {
        $this->assertEquals(
            'mautic.lead.query.builder.foreign.func',
            $this->queryBuilder::getServiceId()
        );
    }

    /**
     * @return array<mixed>
     */
    public function dataApplyQuery(): iterable
    {
        yield ['gt', '2', 'SELECT count(DISTINCT par1.id) FROM test_table par1 WHERE (l.id=par1.contact_id) AND (some_bool = 1) HAVING count(DISTINCT par1.id) > 0'];
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
            'glue'       => 'and',
            'field'      => 'contact_id',
            'object'     => 'lead',
            'type'       => 'number',
            'operator'   => $operator,
            'properties' => [
                'filter' => $parameterValue,
            ],
            'filter'  => '0',
            'display' => null,
        ]);

        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        Assert::assertSame($expectedQuery, $queryBuilder->getDebugOutput());
    }

    /**
     * @param array<string, mixed> $filter
     * @param array<string, mixed> $batchLimiters
     */
    private function getContactSegmentFilter(array $filter, array $batchLimiters = []): ContactSegmentFilter
    {
        $emMock = $this->createMock(EntityManager::class);
        $emMock->method('getConnection')->willReturn($this->connectionMock);
        /**
         * @var \Mautic\LeadBundle\Segment\TableSchemaColumnsCache&\PHPUnit\Framework\MockObject\MockObject $tableSchemaColumnsCacheMock
         */
        $tableSchemaColumnsCacheMock = $this->getMockBuilder(TableSchemaColumnsCache::class)
            ->setConstructorArgs([$emMock])
            ->getMock();
        $tableSchemaColumnsCacheMock->method('getColumns')->willReturn(['contact_id' => []]);

        return new ContactSegmentFilter(
            new ContactSegmentFilterCrate($filter),
            new CustomMappedDecorator(
                new ContactSegmentFilterOperator(
                    new FilterOperatorProvider($this->dispatcher, $this->createMock(TranslatorInterface::class))
                ),
                new ContactSegmentFilterDictionary($this->dispatcher)
            ),
            $tableSchemaColumnsCacheMock,
            $this->queryBuilder,
            $batchLimiters
        );
    }

    public function onSegmentDictionaryGenerate(SegmentDictionaryGenerationEvent $event): void
    {
        $event->addTranslation('mautic.lead.query.builder.foreign.func.test.translation', [
            'type'                => ForeignFuncFilterQueryBuilder::getServiceId(),
            'foreign_table'       => 'test_table',
            'foreign_table_field' => 'contact_id',
            'table'               => 'leads',
            'table_field'         => 'id',
            'func'                => 'count',
            'field'               => 'id',
            'where'               => 'some_bool = 1',
        ]);
    }
}
