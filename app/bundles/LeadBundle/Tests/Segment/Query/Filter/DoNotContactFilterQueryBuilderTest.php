<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Segment\Query\Filter;

use Doctrine\DBAL\Connection;
use Mautic\CoreBundle\Test\Doctrine\MockedConnectionTrait;
use Mautic\LeadBundle\Segment\ContactSegmentFilter;
use Mautic\LeadBundle\Segment\DoNotContact\DoNotContactParts;
use Mautic\LeadBundle\Segment\Query\Filter\DoNotContactFilterQueryBuilder;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\LeadBundle\Segment\RandomParameterName;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;

class DoNotContactFilterQueryBuilderTest extends TestCase
{
    use MockedConnectionTrait;

    public function testGetServiceId(): void
    {
        Assert::assertSame('mautic.lead.query.builder.special.dnc', DoNotContactFilterQueryBuilder::getServiceId());
    }

    /**
     * @dataProvider dataApplyQuery
     */
    public function testApplyQuery(string $operator, string $parameterValue, string $expectedQuery): void
    {
        $queryBuilder = new QueryBuilder($this->createConnection());
        $queryBuilder->select('1');
        $queryBuilder->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $filter             = $this->createFilter($operator, $parameterValue);
        $filterQueryBuilder = new DoNotContactFilterQueryBuilder(new RandomParameterName(), new EventDispatcher());

        $expectedQuery = str_replace('__MAUTIC_TABLE_PREFIX__', MAUTIC_TABLE_PREFIX, $expectedQuery);
        Assert::assertSame($queryBuilder, $filterQueryBuilder->applyQuery($queryBuilder, $filter));
        Assert::assertSame($expectedQuery, $queryBuilder->getDebugOutput());
    }

    /**
     * @return iterable<array<string>>
     */
    public function dataApplyQuery(): iterable
    {
        yield ['eq', '1', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id IN (SELECT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
        yield ['eq', '0', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
        yield ['neq', '1', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
        yield ['neq', '0', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id IN (SELECT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
    }

    private function createConnection(): Connection
    {
        return $this->getMockedConnection();
    }

    /**
     * @param array<string, mixed> $batchLimiters
     */
    private function createFilter(string $operator, string $parameterValue, array $batchLimiters = []): ContactSegmentFilter
    {
        return new class($operator, $parameterValue, $batchLimiters) extends ContactSegmentFilter {
            /**
             * @noinspection PhpMissingParentConstructorInspection
             */
            public function __construct(
                private string $operator,
                private string $parameterValue,
                /**
                 * @var array<string, mixed>
                 */
                private array $batchLimiters,
            ) {
            }

            public function getDoNotContactParts(): DoNotContactParts
            {
                return new DoNotContactParts('dnc_unsubscribed');
            }

            public function getOperator(): string
            {
                return $this->operator;
            }

            public function getParameterValue(): string
            {
                return $this->parameterValue;
            }

            public function getGlue(): string
            {
                return 'and';
            }

            public function getBatchLimiters(): array
            {
                return $this->batchLimiters;
            }
        };
    }
}
