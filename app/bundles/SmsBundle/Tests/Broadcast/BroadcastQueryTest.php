<?php

namespace Mautic\SmsBundle\Tests\Broadcast;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\LeadBundle\Segment\Query\QueryBuilder;
use Mautic\SmsBundle\Broadcast\BroadcastQuery;
use Mautic\SmsBundle\Entity\Sms;
use Mautic\SmsBundle\Entity\SmsRepository;
use Mautic\SmsBundle\Model\SmsModel;

class BroadcastQueryTest extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->classMetadata = $this->createMock(ClassMetadata::class);
        $this->em            = $this->createMock(EntityManager::class);
        $this->smsModel      = $this->createMock(SmsModel::class);
        $this->sms           = $this->createMock(Sms::class);

        $this->broadcastQuery = new BroadcastQuery($this->em, $this->smsModel);

        $this->connection = $this->createMock(Connection::class);

        $this->connection->method('createQueryBuilder')
            ->willReturnCallback(function () {
                return new QueryBuilder($this->connection);
            });
        $this->em->method('getConnection')
            ->willReturn($this->connection);

        $this->sms->method('getId')
            ->willReturn(200);

        $smsRepository = new SmsRepository($this->em, $this->classMetadata);
        $this->smsModel->method('getRepository')
            ->willReturn($smsRepository);

        $platform = $this->createMock(AbstractPlatform::class);
        $this->connection->method('getDatabasePlatform')
            ->willReturn($platform);
    }

    public function testGetPendingContactsQuery()
    {
        $limiter = new ContactLimiter(100, 0, 0, 0);

        $res = $this->broadcastQuery->getPendingContactsQuery($this->sms, $limiter);

        $expect_query = 'SELECT DISTINCT l.id, ll.id as listId FROM sms_message_list_xref sml INNER JOIN lead_lists ll ON ll.id = sml.leadlist_id and ll.is_published = 1 INNER JOIN lead_lists_leads lll ON lll.leadlist_id = sml.leadlist_id and lll.manually_removed = 0 INNER JOIN leads l ON lll.lead_id = l.id WHERE (sml.sms_id = :smsId) AND (((l.mobile IS NOT NULL) OR (l.mobile <> )) OR ((l.phone IS NOT NULL) OR (l.phone <> ))) AND (NOT EXISTS (SELECT null FROM sms_message_stats stat WHERE (stat.lead_id = l.id) AND (stat.sms_id = 200))) AND (NOT EXISTS (SELECT null FROM lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = ))) AND (NOT EXISTS (SELECT null FROM message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> ) AND (mq.channel = ))) GROUP BY l.id ORDER BY l.id ASC LIMIT 100';

        $this->assertEquals($expect_query, $res->getSQL());

        $this->assertEquals(['smsId' => 200], $res->getParameters());
    }
}
