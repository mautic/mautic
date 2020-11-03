<?php

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\EmailBundle\Entity\EmailRepository;
use PHPUnit\Framework\TestCase;

class EmailRepositoryTest extends TestCase
{
    /**
     * @var EmailRepository
     */
    private $repo;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $mockConnection = $this->createMock(Connection::class);
        $entityManager  = $this->createMock(EntityManager::class);
        $classMetadata  = $this->createMock(ClassMetadata::class);
        $this->repo     = new EmailRepository($entityManager, $classMetadata);

        $mockConnection->method('createQueryBuilder')
            ->willReturnCallback(
                function () use ($mockConnection) {
                    return new QueryBuilder($mockConnection);
                }
            );

        $mockConnection->method('getExpressionBuilder')
            ->willReturnCallback(
                function () use ($mockConnection) {
                    return new ExpressionBuilder($mockConnection);
                }
            );

        $mockConnection->method('quote')
            ->willReturnCallback(
                function ($value) {
                    return "'$value'";
                }
            );

        $entityManager->method('getConnection')
            ->willReturn($mockConnection);
    }

    /**
     * @dataProvider dataGetEmailPendingQueryForCount
     */
    public function testGetEmailPendingQueryForCount(bool $countWithMaxMin, string $expectedQuery): void
    {
        $emailId         = 5;
        $variantIds      = null;
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

        $this->assertEquals($expectedQuery, $query->getSql());
        $this->assertEquals(['false' => false], $query->getParameters());
    }

    public function dataGetEmailPendingQueryForCount(): iterable
    {
        yield [false, "SELECT count(*) as count FROM leads l WHERE (l.id IN (SELECT ll.lead_id FROM lead_lists_leads ll WHERE (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM lead_donotcontact dnc WHERE dnc.channel = 'email')) AND (l.id NOT IN (SELECT stat.lead_id FROM email_stats stat WHERE stat.email_id = 5)) AND (l.id NOT IN (SELECT mq.lead_id FROM message_queue mq WHERE (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
        yield [true, "SELECT count(*) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM leads l WHERE (l.id IN (SELECT ll.lead_id FROM lead_lists_leads ll WHERE (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM lead_donotcontact dnc WHERE dnc.channel = 'email')) AND (l.id NOT IN (SELECT stat.lead_id FROM email_stats stat WHERE stat.email_id = 5)) AND (l.id NOT IN (SELECT mq.lead_id FROM message_queue mq WHERE (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND ((l.email IS NOT NULL) AND (l.email <> ''))"];
    }

    public function testGetEmailPendingQueryForMaxMinIdCountWithMaxMinIdsDefined(): void
    {
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

        $expectedQuery = "SELECT count(*) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM leads l WHERE (l.id IN (SELECT ll.lead_id FROM lead_lists_leads ll WHERE (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (l.id NOT IN (SELECT dnc.lead_id FROM lead_donotcontact dnc WHERE dnc.channel = 'email')) AND (l.id NOT IN (SELECT stat.lead_id FROM email_stats stat WHERE stat.email_id = 5)) AND (l.id NOT IN (SELECT mq.lead_id FROM message_queue mq WHERE (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND (l.id >= :minContactId) AND (l.id <= :maxContactId) AND ((l.email IS NOT NULL) AND (l.email <> ''))";

        $expectedParams = [
            'false'        => false,
            'minContactId' => 10,
            'maxContactId' => 1000,
        ];

        $this->assertEquals($expectedQuery, $query->getSql());
        $this->assertEquals($expectedParams, $query->getParameters());
    }
}
