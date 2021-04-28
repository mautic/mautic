<?php

namespace Mautic\SmsBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\SmsBundle\Entity\SmsRepository;

class SmsRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /*
     * @copyright   2016 Mautic Contributors. All rights reserved
     * @author      Mautic, Inc.
     *
     * @link        https://mautic.org
     *
     * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
     */

    private $mockConnection;
    private $em;
    private $cm;

    /**
     * @var SmsRepository
     */
    private $repo;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->mockConnection = $this->createMock(Connection::class);
        $this->em             = $this->createMock(EntityManager::class);
        $this->cm             = $this->createMock(ClassMetadata::class);
        $this->repo           = new SmsRepository($this->em, $this->cm);

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

    public function testGetSmsPendingQuery()
    {
        $smsId = 5;

        $query = $this->repo->getSmsPendingQuery($smsId);

        $expectedQuery = "SELECT l.* FROM leads l WHERE (EXISTS (SELECT null FROM lead_lists_leads ll WHERE (ll.lead_id = l.id) AND (ll.leadlist_id IN ()) AND (ll.manually_removed = :false))) AND (NOT EXISTS (SELECT null FROM lead_donotcontact dnc WHERE (dnc.lead_id = l.id) AND (dnc.channel = 'sms'))) AND (NOT EXISTS (SELECT null FROM sms_message_stats stat WHERE (stat.lead_id = l.id) AND (stat.sms_id = 5))) AND (NOT EXISTS (SELECT null FROM message_queue mq WHERE (mq.lead_id = l.id) AND (mq.status <> 'sent') AND (mq.channel = 'sms') AND (mq.channel_id = 5))) AND ((l.mobile IS NOT NULL) AND (l.mobile <> ''))";

        $this->assertEquals($expectedQuery, $query->getSql());
    }
}
