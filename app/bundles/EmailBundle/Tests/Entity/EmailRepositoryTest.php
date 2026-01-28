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
use Symfony\Contracts\Translation\TranslatorInterface;

class EmailRepositoryTest extends TestCase
{
    use RepositoryConfiguratorTrait;

    private EmailRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repo = $this->configureRepository(Email::class);
        $this->connection->method('createQueryBuilder')->willReturnCallback(fn () => new QueryBuilder($this->connection));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(fn ($id) => match ($id) {
            'mautic.email.email.searchcommand.isexpired' => 'is:expired',
            'mautic.email.email.searchcommand.ispending' => 'is:pending',
            default                                      => $id,
        });
        $this->repo->setTranslator($translator);
    }

    /**
     * @param int[] $variantIds
     * @param int[] $excludedListIds
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataGetEmailPendingQueryForCount')]
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
    public static function dataGetEmailPendingQueryForCount(): iterable
    {
        yield [null, false, [], "SELECT count(*) as count FROM test_leads l WHERE (l.id IN (SELECT ll.lead_id FROM test_lead_lists_leads ll WHERE (ll.lead_id = l.id) AND (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM test_lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = 'email'))) AND (l.id NOT IN (SELECT stat.lead_id FROM test_email_stats stat WHERE (stat.lead_id IS NOT NULL) AND (stat.email_id = 5))) AND (l.id NOT IN (SELECT mq.lead_id FROM test_message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND (l.id NOT IN (SELECT lc.lead_id FROM test_lead_categories lc INNER JOIN test_emails e ON e.category_id = lc.category_id WHERE (e.id = 5) AND (lc.manually_removed = 1))) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
        yield [[6], false, [16], "SELECT count(*) as count FROM test_leads l WHERE (l.id IN (SELECT ll.lead_id FROM test_lead_lists_leads ll WHERE (ll.lead_id = l.id) AND (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM test_lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = 'email'))) AND (l.id NOT IN (SELECT stat.lead_id FROM test_email_stats stat WHERE (stat.lead_id IS NOT NULL) AND (stat.email_id IN (6, 5)))) AND (l.id NOT IN (SELECT mq.lead_id FROM test_message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id IN (6, 5)))) AND (l.id NOT IN (SELECT lc.lead_id FROM test_lead_categories lc INNER JOIN test_emails e ON e.category_id = lc.category_id WHERE (e.id = 5) AND (lc.manually_removed = 1))) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
        yield [null, true, [9, 7], "SELECT count(*) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM test_leads l WHERE (l.id IN (SELECT ll.lead_id FROM test_lead_lists_leads ll WHERE (ll.lead_id = l.id) AND (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM test_lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = 'email'))) AND (l.id NOT IN (SELECT stat.lead_id FROM test_email_stats stat WHERE (stat.lead_id IS NOT NULL) AND (stat.email_id = 5))) AND (l.id NOT IN (SELECT mq.lead_id FROM test_message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND (l.id NOT IN (SELECT lc.lead_id FROM test_lead_categories lc INNER JOIN test_emails e ON e.category_id = lc.category_id WHERE (e.id = 5) AND (lc.manually_removed = 1))) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
    }

    /**
     * @param int[] $excludedListIds
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataGetEmailPendingQueryForMaxMinIdCountWithMaxMinIdsDefined')]
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
    public static function dataGetEmailPendingQueryForMaxMinIdCountWithMaxMinIdsDefined(): iterable
    {
        yield [[], "SELECT count(*) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM test_leads l WHERE (l.id IN (SELECT ll.lead_id FROM test_lead_lists_leads ll WHERE (ll.lead_id = l.id) AND (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM test_lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = 'email'))) AND (l.id NOT IN (SELECT stat.lead_id FROM test_email_stats stat WHERE (stat.lead_id IS NOT NULL) AND (stat.email_id = 5))) AND (l.id NOT IN (SELECT mq.lead_id FROM test_message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND (l.id NOT IN (SELECT lc.lead_id FROM test_lead_categories lc INNER JOIN test_emails e ON e.category_id = lc.category_id WHERE (e.id = 5) AND (lc.manually_removed = 1))) AND (l.id >= :minContactId) AND (l.id <= :maxContactId) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
        yield [[96, 98, 103], "SELECT count(*) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM test_leads l WHERE (l.id IN (SELECT ll.lead_id FROM test_lead_lists_leads ll WHERE (ll.lead_id = l.id) AND (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM test_lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = 'email'))) AND (l.id NOT IN (SELECT stat.lead_id FROM test_email_stats stat WHERE (stat.lead_id IS NOT NULL) AND (stat.email_id = 5))) AND (l.id NOT IN (SELECT mq.lead_id FROM test_message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND (l.id NOT IN (SELECT lc.lead_id FROM test_lead_categories lc INNER JOIN test_emails e ON e.category_id = lc.category_id WHERE (e.id = 5) AND (lc.manually_removed = 1))) AND (l.id >= :minContactId) AND (l.id <= :maxContactId) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
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

    public function testAddSearchCommandWhereClauseHandlesExpirationFilters(): void
    {
        $qb     = $this->connection->createQueryBuilder();
        $filter = (object) ['command' => 'is:expired', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(EmailRepository::class, 'addSearchCommandWhereClause');
        $method->setAccessible(true);

        [$expr, $params] = $method->invoke($this->repo, $qb, $filter);

        self::assertSame(
            '(e.isPublished = :par1 AND e.publishDown IS NOT NULL AND e.publishDown <> \'\' AND e.publishDown < CURRENT_TIMESTAMP())',
            (string) $expr
        );
        self::assertSame(['par1' => true], $params);
    }

    public function testAddSearchCommandWhereClauseHandlesPendingFilters(): void
    {
        $qb     = $this->connection->createQueryBuilder();
        $filter = (object) ['command' => 'is:pending', 'string' => '', 'not' => false, 'strict' => false];

        $method = new \ReflectionMethod(EmailRepository::class, 'addSearchCommandWhereClause');
        $method->setAccessible(true);

        [$expr, $params] = $method->invoke($this->repo, $qb, $filter);

        self::assertSame(
            '(e.isPublished = :par1 AND e.publishUp IS NOT NULL AND e.publishUp <> \'\' AND e.publishUp > CURRENT_TIMESTAMP())',
            (string) $expr
        );
        self::assertSame(['par1' => true], $params);
    }

    public function testGetSearchCommandsContainsExpirationFilters(): void
    {
        $commands = $this->repo->getSearchCommands();
        self::assertContains('mautic.email.email.searchcommand.isexpired', $commands);
        self::assertContains('mautic.email.email.searchcommand.ispending', $commands);
    }
}
