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

    #[\PHPUnit\Framework\Attributes\DataProvider('dataApplyQuery')]
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
    public static function dataApplyQuery(): iterable
    {
        // Standard DNC filters
        yield ['eq', '1', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id IN (SELECT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
        yield ['eq', '0', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
        yield ['neq', '1', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id NOT IN (SELECT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
        yield ['neq', '0', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id IN (SELECT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0 WHERE (par0.reason = 1) AND (par0.channel = \'email\'))'];
        
        // New DNC filter types will be tested here
        // These are placeholder test cases - actual implementation would verify the correct SQL is generated
        // for each of our new filter types
        
        // All DNC filter
        yield ['eq', '1', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id IN (SELECT DISTINCT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0)'];
        
        // Hard bounce filter
        yield ['eq', '1', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id IN (SELECT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0 WHERE (par0.reason = 2) AND (par0.channel = \'email\') AND ((par0.comments LIKE \'%unrecognized address%\') OR (par0.comments LIKE \'5%\') OR (par0.comments LIKE \'%5._.__%\') OR (par0.comments LIKE \'%maildir delivery failed%\') OR (par0.comments LIKE \'%invalid%\') OR (par0.comments LIKE \'%Bounced Address%\') OR (par0.comments LIKE \'%Spam reporting address%\') OR (par0.comments LIKE \'%does not exist%\') OR (par0.comments LIKE \'%unknown%\') OR (par0.comments LIKE \'%Incorrectly formatted email address%\') OR (par0.comments LIKE \'%BOGON%\') OR (par0.comments LIKE \'%User unsubscribed%\') OR (par0.comments LIKE \'%Message delivery failed%\') OR (par0.comments LIKE \'%not found%\')) AND (par0.reason = 2))'];
        
        // Soft bounce filter
        yield ['eq', '1', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id IN (SELECT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0 WHERE (par0.reason = 2) AND (par0.channel = \'email\') AND ((par0.comments LIKE \'4%\') OR (par0.comments LIKE \'%4._.__%\') OR (par0.comments LIKE \'%timeout%\') OR (par0.comments LIKE \'%connection refused%\') OR (par0.comments LIKE \'%Connection reset by peer%\') OR (par0.comments LIKE \'%Unable to parse reason from bounce report%\')) AND (par0.reason = 2))'];
        
        // Spam bounce filter
        yield ['eq', '1', 'SELECT 1 FROM __MAUTIC_TABLE_PREFIX__leads l WHERE l.id IN (SELECT par0.lead_id FROM __MAUTIC_TABLE_PREFIX__lead_donotcontact par0 WHERE (par0.reason = 2) AND (par0.channel = \'email\') AND ((par0.comments LIKE \'%spam%\') OR (par0.comments LIKE \'%rejected%\')) AND (par0.reason = 2))'];
    }

    private function createConnection(): Connection
    {
        return $this->getMockedConnection();
    }

    /**
     * @param array<string, mixed> $batchLimiters
     */
    private function createFilter(string $operator, string $parameterValue, array $batchLimiters = [], string $filterType = 'dnc_unsubscribed'): ContactSegmentFilter
    {
        return new class($operator, $parameterValue, $batchLimiters, $filterType) extends ContactSegmentFilter {
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
                private string $filterType,
            ) {
            }

            public function getDoNotContactParts(): DoNotContactParts
            {
                return new DoNotContactParts($this->filterType);
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
