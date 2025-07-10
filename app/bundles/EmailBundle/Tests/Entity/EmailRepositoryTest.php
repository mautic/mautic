<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Mautic\CoreBundle\Test\Doctrine\RepositoryConfiguratorTrait;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\LeadBundle\Entity\DoNotContact;
use PHPUnit\Framework\TestCase;

class EmailRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private EmailRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->configureRepository(Email::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn () => new QueryBuilder($this->connection));
    }

    /**
     * @dataProvider dataGetEmailPendingQueryForCount
     *
     * @param int[] $variantIds
     * @param int[] $excludedListIds
     */
    public function testGetEmailPendingQueryForCount(?array $variantIds, bool $countWithMaxMin, array $excludedListIds, string $expectedQuery): void
    {
        $this->mockExcludedListIds($excludedListIds);

        $emailId         = 5;
        $listIds         = [22, 33];
        $countOnly       = true;
        $limit           = null;
        $minContactId    = null;
        $maxContactId    = null;

        $query = $this->repo->getEmailPendingQuery(
            $emailId,
            $variantIds,
            $listIds,
            $countOnly,
            $limit,
            $minContactId,
            $maxContactId,
            $countWithMaxMin
        );

        $this->assertEquals($this->replaceQueryPrefix($expectedQuery), $query->getSql());
        $this->assertEquals(['false' => false], $query->getParameters());
    }

    /**
     * @return iterable<mixed[]>
     */
    public function dataGetEmailPendingQueryForCount(): iterable
    {
        yield [null, false, [], "SELECT count(*) as count FROM {prefix}leads l WHERE (l.id IN (SELECT ll.lead_id FROM {prefix}lead_lists_leads ll WHERE (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM {prefix}lead_donotcontact dnc WHERE dnc.channel = 'email')) AND (l.id NOT IN (SELECT stat.lead_id FROM {prefix}email_stats stat WHERE (stat.lead_id IS NOT NULL) AND (stat.email_id = 5))) AND (l.id NOT IN (SELECT mq.lead_id FROM {prefix}message_queue mq WHERE (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND (l.id NOT IN (SELECT lc.lead_id FROM {prefix}lead_categories lc INNER JOIN {prefix}emails e ON e.category_id = lc.category_id WHERE (e.id = 5) AND (lc.manually_removed = 1))) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
        yield [[6], false, [16], "SELECT count(*) as count FROM {prefix}leads l WHERE (l.id IN (SELECT ll.lead_id FROM {prefix}lead_lists_leads ll WHERE (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM {prefix}lead_donotcontact dnc WHERE dnc.channel = 'email')) AND (l.id NOT IN (SELECT stat.lead_id FROM {prefix}email_stats stat WHERE (stat.lead_id IS NOT NULL) AND (stat.email_id IN (6, 5)))) AND (l.id NOT IN (SELECT mq.lead_id FROM {prefix}message_queue mq WHERE (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id IN (6, 5)))) AND (l.id NOT IN (SELECT lc.lead_id FROM {prefix}lead_categories lc INNER JOIN {prefix}emails e ON e.category_id = lc.category_id WHERE (e.id = 5) AND (lc.manually_removed = 1))) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
        yield [null, true, [9, 7], "SELECT count(*) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM {prefix}leads l WHERE (l.id IN (SELECT ll.lead_id FROM {prefix}lead_lists_leads ll WHERE (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM {prefix}lead_donotcontact dnc WHERE dnc.channel = 'email')) AND (l.id NOT IN (SELECT stat.lead_id FROM {prefix}email_stats stat WHERE (stat.lead_id IS NOT NULL) AND (stat.email_id = 5))) AND (l.id NOT IN (SELECT mq.lead_id FROM {prefix}message_queue mq WHERE (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND (l.id NOT IN (SELECT lc.lead_id FROM {prefix}lead_categories lc INNER JOIN {prefix}emails e ON e.category_id = lc.category_id WHERE (e.id = 5) AND (lc.manually_removed = 1))) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
    }

    /**
     * @dataProvider dataGetEmailPendingQueryForMaxMinIdCountWithMaxMinIdsDefined
     *
     * @param int[] $excludedListIds
     */
    public function testGetEmailPendingQueryForMaxMinIdCountWithMaxMinIdsDefined(array $excludedListIds, string $expectedQuery): void
    {
        $this->mockExcludedListIds($excludedListIds);

        $emailId         = 5;
        $variantIds      = null;
        $listIds         = [22, 33];
        $countOnly       = true;
        $limit           = null;
        $minContactId    = 10;
        $maxContactId    = 1000;
        $countWithMaxMin = true;

        $query = $this->repo->getEmailPendingQuery(
            $emailId,
            $variantIds,
            $listIds,
            $countOnly,
            $limit,
            $minContactId,
            $maxContactId,
            $countWithMaxMin
        );

        $expectedParams = [
            'false'        => false,
            'minContactId' => 10,
            'maxContactId' => 1000,
        ];

        $this->assertEquals($this->replaceQueryPrefix($expectedQuery), $query->getSql());
        $this->assertEquals($expectedParams, $query->getParameters());
    }

    /**
     * @return iterable<mixed[]>
     */
    public function dataGetEmailPendingQueryForMaxMinIdCountWithMaxMinIdsDefined(): iterable
    {
        yield [[], "SELECT count(*) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM {prefix}leads l WHERE (l.id IN (SELECT ll.lead_id FROM {prefix}lead_lists_leads ll WHERE (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM {prefix}lead_donotcontact dnc WHERE dnc.channel = 'email')) AND (l.id NOT IN (SELECT stat.lead_id FROM {prefix}email_stats stat WHERE (stat.lead_id IS NOT NULL) AND (stat.email_id = 5))) AND (l.id NOT IN (SELECT mq.lead_id FROM {prefix}message_queue mq WHERE (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND (l.id NOT IN (SELECT lc.lead_id FROM {prefix}lead_categories lc INNER JOIN {prefix}emails e ON e.category_id = lc.category_id WHERE (e.id = 5) AND (lc.manually_removed = 1))) AND (l.id >= :minContactId) AND (l.id <= :maxContactId) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
        yield [[96, 98, 103], "SELECT count(*) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM {prefix}leads l WHERE (l.id IN (SELECT ll.lead_id FROM {prefix}lead_lists_leads ll WHERE (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM {prefix}lead_donotcontact dnc WHERE dnc.channel = 'email')) AND (l.id NOT IN (SELECT stat.lead_id FROM {prefix}email_stats stat WHERE (stat.lead_id IS NOT NULL) AND (stat.email_id = 5))) AND (l.id NOT IN (SELECT mq.lead_id FROM {prefix}message_queue mq WHERE (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND (l.id NOT IN (SELECT lc.lead_id FROM {prefix}lead_categories lc INNER JOIN {prefix}emails e ON e.category_id = lc.category_id WHERE (e.id = 5) AND (lc.manually_removed = 1))) AND (l.id >= :minContactId) AND (l.id <= :maxContactId) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
    }

    public function testGetUniqueCliks(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('SUM( tr.unique_hits) as `unique_clicks`')
            ->willReturnSelf();

        $resultMock = $this->createMock(Result::class);
        $queryBuilder->expects($this->once())
            ->method('executeQuery')
            ->willReturn($resultMock);

        $resultMock->expects($this->once())
            ->method('fetchOne')
            ->willReturn(10);

        $repository = $this->getMockBuilder(EmailRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addTrackableTablesForEmailStats'])
            ->getMock();

        $result = $repository->getUniqueClicks($queryBuilder);

        $this->assertEquals(10, $result);
    }

    public function testGetUnsubscribedCount(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('resetQueryParts')
            ->with(['join'])
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('e.id as email_id, dnc.lead_id')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('andWhere')
            ->with('dnc.reason='.DoNotContact::UNSUBSCRIBED)
            ->willReturnSelf();

        $resultMock = $this->createMock(Result::class);
        $queryBuilder->expects($this->once())
            ->method('executeQuery')
            ->willReturn($resultMock);

        $resultMock->expects($this->once())
            ->method('rowCount')
            ->willReturn(5);

        $repository = $this->getMockBuilder(EmailRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addDNCTableForEmails'])
            ->getMock();

        $result = $repository->getUnsubscribedCount($queryBuilder);

        $this->assertEquals(5, $result);
    }

    public function testGetSentReadNotReadCount(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('resetQueryPart')
            ->with('groupBy')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('resetQueryParts')
            ->with(['join'])
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('SUM( e.sent_count) as sent_count, SUM( e.read_count) as read_count')
            ->willReturnSelf();

        $resultMock = $this->createMock(Result::class);
        $queryBuilder->expects($this->once())
            ->method('executeQuery')
            ->willReturn($resultMock);

        $resultMock->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn([
                'sent_count' => '100',
                'read_count' => '60',
            ]);

        $result = $this->repo->getSentReadNotReadCount($queryBuilder);

        $this->assertEquals([
            'sent_count' => 100,
            'read_count' => 60,
            'not_read'   => 40,
        ], $result);
    }

    public function testGetSentReadNotReadCountEmptyResults(): void
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder->expects($this->once())
            ->method('resetQueryPart')
            ->with('groupBy')
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('resetQueryParts')
            ->with(['join'])
            ->willReturnSelf();

        $queryBuilder->expects($this->once())
            ->method('select')
            ->with('SUM( e.sent_count) as sent_count, SUM( e.read_count) as read_count')
            ->willReturnSelf();

        $resultMock = $this->createMock(Result::class);
        $queryBuilder->expects($this->once())
            ->method('executeQuery')
            ->willReturn($resultMock);

        $resultMock->expects($this->once())
            ->method('fetchAssociative')
            ->willReturn(false);

        $result = $this->repo->getSentReadNotReadCount($queryBuilder);

        $this->assertEquals([
            'sent_count' => 0,
            'read_count' => 0,
            'not_read'   => 0,
        ], $result);
    }

    /**
     * @param int[] $excludedListIds
     */
    private function mockExcludedListIds(array $excludedListIds): void
    {
        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')
            ->willReturn(array_map(fn (int $id) => [$id], $excludedListIds));
        $this->connection->method('executeQuery')
            ->willReturn($resultMock);
    }

    private function replaceQueryPrefix(string $query): string
    {
        return str_replace('{prefix}', MAUTIC_TABLE_PREFIX, $query);
    }
}
