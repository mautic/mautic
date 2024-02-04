<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Test\Doctrine\MockedConnectionTrait;
use Mautic\LeadBundle\Provider\FilterOperatorProviderInterface;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\ContactSegmentFilterCrate;
use Mautic\LeadBundle\Segment\ContactSegmentFilterOperator;
use Mautic\LeadBundle\Segment\Decorator\BaseDecorator;
use Mautic\LeadBundle\Segment\Query\Filter\ChannelClickQueryBuilder;
use Mautic\LeadBundle\Segment\Query\Filter\FilterQueryBuilderInterface;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use Mautic\LeadBundle\Segment\TableSchemaColumnsCache;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ChannelClickQueryBuilderTest extends TestCase
{
    use MockedConnectionTrait;

    /**
     * @var MockObject|RandomParameterName
     */
    private \PHPUnit\Framework\MockObject\MockObject $randomParameterMock;

    /**
     * @var MockObject|EventDispatcherInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $dispatcherMock;

    /**
     * @var Connection|MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $connectionMock;

    private \Mautic\LeadBundle\Segment\Query\Filter\ChannelClickQueryBuilder $queryBuilder;

    public function setUp(): void
    {
        $this->randomParameterMock = $this->createMock(RandomParameterName::class);
        $this->dispatcherMock      = $this->createMock(EventDispatcherInterface::class);
        $this->connectionMock      = $this->getMockedConnection();
        $this->queryBuilder        = new ChannelClickQueryBuilder(
            $this->randomParameterMock,
            $this->dispatcherMock
        );

        $this->connectionMock->method('quote')
            ->willReturnArgument(0);
    }

    public function testGetServiceId(): void
    {
        $this->assertEquals(
            'mautic.lead.query.builder.channel_click.value',
            $this->queryBuilder::getServiceId()
        );
    }

    /**
     * @return array<mixed>
     */
    public function dataApplyQuery(): iterable
    {
        yield ['eq', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id NOT IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email))'];
        yield ['eq', '0', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email))'];
        yield ['neq', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id NOT IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email))'];
        yield ['neq', '0', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email))'];
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

        $filter = $this->getContactSegmentFilter($operator, $parameterValue);

        $this->randomParameterMock->method('generateRandomParameterName')
            ->willReturnOnConsecutiveCalls('queryAlias', 'para1', 'para2');

        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        Assert::assertSame($expectedQuery, $queryBuilder->getDebugOutput());
    }

    /**
     * @return array<mixed>
     */
    public function dataApplyQueryWithBatchLimitersMinMaxBoth(): iterable
    {
        yield [['minId' => 1, 'maxId' => 1], 'eq', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id NOT IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id BETWEEN 1 and 1))'];
        yield [['minId' => 1, 'maxId' => 1], 'eq', '0', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id BETWEEN 1 and 1))'];
        yield [['minId' => 1, 'maxId' => 1], 'neq', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id NOT IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id BETWEEN 1 and 1))'];
        yield [['minId' => 1, 'maxId' => 1], 'neq', '0', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id BETWEEN 1 and 1))'];

        yield [['minId' => 1], 'eq', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id NOT IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id >= 1))'];
        yield [['minId' => 1], 'eq', '0', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id >= 1))'];
        yield [['minId' => 1], 'neq', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id NOT IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id >= 1))'];
        yield [['minId' => 1], 'neq', '0', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id >= 1))'];

        yield [['maxId' => 1], 'eq', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id NOT IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id <= 1))'];
        yield [['maxId' => 1], 'eq', '0', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id <= 1))'];
        yield [['maxId' => 1], 'neq', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id NOT IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id <= 1))'];
        yield [['maxId' => 1], 'neq', '0', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id <= 1))'];

        yield [['lead_id' => 1], 'eq', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id NOT IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id = 1))'];
        yield [['lead_id' => 1], 'eq', '0', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id = 1))'];
        yield [['lead_id' => 1], 'neq', '1', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id NOT IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id = 1))'];
        yield [['lead_id' => 1], 'neq', '0', 'SELECT 1 FROM __PREFIX__leads l WHERE l.id IN (SELECT para1.lead_id FROM __PREFIX__page_hits para1 WHERE (para1.redirect_id IS NOT NULL) AND (para1.lead_id IS NOT NULL) AND (para1.source = email) AND (para1.lead_id = 1))'];
    }

    /**
     * @dataProvider dataApplyQueryWithBatchLimitersMinMaxBoth
     *
     *  @param array<string, mixed> $batchLimiters
     */
    public function testApplyQueryWithBatchLimitersMinMaxBoth(array $batchLimiters, string $operator, string $parameterValue, string $expectedQuery): void
    {
        $expectedQuery = str_replace('__PREFIX__', MAUTIC_TABLE_PREFIX, $expectedQuery);
        $queryBuilder  = new QueryBuilder($this->connectionMock);
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $filter = $this->getContactSegmentFilter($operator, $parameterValue, $batchLimiters);

        $this->randomParameterMock->method('generateRandomParameterName')
            ->willReturnOnConsecutiveCalls('queryAlias', 'para1', 'para2');

        $this->queryBuilder->applyQuery($queryBuilder, $filter);

        Assert::assertSame($expectedQuery, $queryBuilder->getDebugOutput());
    }

    /**
     *  @param array<string, mixed> $batchLimiters
     */
    private function getContactSegmentFilter(string $operator, string $parameterValue, array $batchLimiters = []): ContactSegmentFilter
    {
        return new ContactSegmentFilter(
            new ContactSegmentFilterCrate(
                [
                    'operator'   => $operator,
                    'glue'       => 'and',
                    'field'      => 'email_id',
                    'object'     => 'behaviors',
                    'type'       => 'boolean',
                    'properties' => [
                            'filter' => $parameterValue,
                        ],
                ]
            ),
            new BaseDecorator(new ContactSegmentFilterOperator(
                $this->createMock(FilterOperatorProviderInterface::class)
            )),
            new TableSchemaColumnsCache($this->createMock(EntityManager::class)),
            $this->createMock(FilterQueryBuilderInterface::class),
            $batchLimiters
        );
    }
}
