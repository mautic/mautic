<?php

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\EmailBundle\Entity\EmailRepository;

class EmailRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockConnection;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $em;

    /**
     * @var ClassMetadata<EmailRepository>|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cm;

    /**
     * @var EmailRepository
     */
    private $repo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockConnection = $this->createMock(Connection::class);
        $this->em             = $this->createMock(EntityManager::class);
        $this->cm             = $this->createMock(ClassMetadata::class);
        $this->repo           = new EmailRepository($this->em, $this->cm);

        $this->mockConnection->method('createQueryBuilder')
            ->willReturnCallback(
                function () {
                    return new QueryBuilder($this->mockConnection);
                }
            );

        $this->mockConnection->method('getExpressionBuilder')
            ->willReturnCallback(
                function () {
                    return new ExpressionBuilder($this->mockConnection);
                }
            );

        $this->mockConnection->method('quote')
            ->willReturnCallback(
                function ($value) {
                    return "'$value'";
                }
            );

        $this->em->method('getConnection')
            ->willReturn($this->mockConnection);
    }

    public function testGetEmailPendingQueryForSimpleCount(): void
    {
        $emailId         = 5;
        $variantIds      = null;
        $listIds         = [22, 33];
        $countOnly       = true;
        $limit           = null;
        $minContactId    = null;
        $maxContactId    = null;
        $countWithMaxMin = false;

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

        $expectedQuery = 'SELECT count(*) as count FROM '.MAUTIC_TABLE_PREFIX.'leads l WHERE (EXISTS (SELECT null FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads ll WHERE (ll.lead_id = l.id) AND (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (NOT EXISTS (SELECT null FROM '.MAUTIC_TABLE_PREFIX."lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = 'email'))) AND (NOT EXISTS (SELECT null FROM ".MAUTIC_TABLE_PREFIX.'email_stats stat WHERE (stat.lead_id = l.id) AND (stat.email_id = 5))) AND (NOT EXISTS (SELECT null FROM '.MAUTIC_TABLE_PREFIX."message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND ((l.email IS NOT NULL) AND (l.email <> ''))";
        $this->assertEquals($expectedQuery, $query->getSql());
        $this->assertEquals(['false' => false], $query->getParameters());
    }

    public function testGetEmailPendingQueryForMaxMinIdCount(): void
    {
        $emailId         = 5;
        $variantIds      = null;
        $listIds         = [22, 33];
        $countOnly       = true;
        $limit           = null;
        $minContactId    = null;
        $maxContactId    = null;
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

        $expectedQuery = 'SELECT count(*) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM '.MAUTIC_TABLE_PREFIX.'leads l WHERE (EXISTS (SELECT null FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads ll WHERE (ll.lead_id = l.id) AND (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (NOT EXISTS (SELECT null FROM '.MAUTIC_TABLE_PREFIX."lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = 'email'))) AND (NOT EXISTS (SELECT null FROM ".MAUTIC_TABLE_PREFIX.'email_stats stat WHERE (stat.lead_id = l.id) AND (stat.email_id = 5))) AND (NOT EXISTS (SELECT null FROM '.MAUTIC_TABLE_PREFIX."message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND ((l.email IS NOT NULL) AND (l.email <> ''))";
        $this->assertEquals($expectedQuery, $query->getSql());
        $this->assertEquals(['false' => false], $query->getParameters());
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

        $expectedQuery = 'SELECT count(*) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM '.MAUTIC_TABLE_PREFIX.'leads l WHERE (EXISTS (SELECT null FROM '.MAUTIC_TABLE_PREFIX.'lead_lists_leads ll WHERE (ll.lead_id = l.id) AND (ll.leadlist_id IN (22, 33)) AND (ll.manually_removed = :false))) AND (NOT EXISTS (SELECT null FROM '.MAUTIC_TABLE_PREFIX."lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = 'email'))) AND (NOT EXISTS (SELECT null FROM ".MAUTIC_TABLE_PREFIX.'email_stats stat WHERE (stat.lead_id = l.id) AND (stat.email_id = 5))) AND (NOT EXISTS (SELECT null FROM '.MAUTIC_TABLE_PREFIX."message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> 'sent') AND (mq.channel = 'email') AND (mq.channel_id = 5))) AND (l.id >= :minContactId) AND (l.id <= :maxContactId) AND ((l.email IS NOT NULL) AND (l.email <> ''))";

        $expectedParams = [
            'false'        => false,
            'minContactId' => 10,
            'maxContactId' => 1000,
        ];

        $this->assertEquals($expectedQuery, $query->getSql());
        $this->assertEquals($expectedParams, $query->getParameters());
    }
}
