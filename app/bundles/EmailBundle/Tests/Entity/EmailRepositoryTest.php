<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Mautic\EmailBundle\Entity\EmailRepository;

class EmailRepositoryTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $mockConnection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

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

        $this->em = $this
            ->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cm = $this->getMockBuilder(ClassMetadata::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->em->method('getConnection')
            ->willReturn($mockConnection);

        $this->repo = new EmailRepository($this->em, $this->cm);
    }

    public function testGetEmailPendingQueryForSimpleCount()
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

        $expectedQuery = "SELECT count(distinct(l.id)) as count FROM leads l INNER JOIN lead_lists_leads ll ON (ll.leadlist_id IN (22, 33)) AND (ll.lead_id = l.id) AND (ll.manually_removed = :false) WHERE (l.id NOT IN (SELECT dnc.lead_id FROM lead_donotcontact dnc WHERE dnc.channel = 'email')) AND (l.id NOT IN (SELECT stat.lead_id FROM email_stats stat WHERE stat.email_id = 5)) AND (l.id NOT IN (SELECT mq.lead_id FROM message_queue mq WHERE (mq.channel = 'email') AND (mq.status <> 'sent') AND (mq.channel_id = 5))) AND ((l.email IS NOT NULL) AND (l.email <> ''))";
        $this->assertEquals($expectedQuery, $query->getSql());
        $this->assertEquals(['false' => false], $query->getParameters());
    }

    public function testGetEmailPendingQueryForMaxMinIdCount()
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

        $expectedQuery = "SELECT count(distinct(l.id)) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM leads l INNER JOIN lead_lists_leads ll ON (ll.leadlist_id IN (22, 33)) AND (ll.lead_id = l.id) AND (ll.manually_removed = :false) WHERE (l.id NOT IN (SELECT dnc.lead_id FROM lead_donotcontact dnc WHERE dnc.channel = 'email')) AND (l.id NOT IN (SELECT stat.lead_id FROM email_stats stat WHERE stat.email_id = 5)) AND (l.id NOT IN (SELECT mq.lead_id FROM message_queue mq WHERE (mq.channel = 'email') AND (mq.status <> 'sent') AND (mq.channel_id = 5))) AND ((l.email IS NOT NULL) AND (l.email <> ''))";
        $this->assertEquals($expectedQuery, $query->getSql());
        $this->assertEquals(['false' => false], $query->getParameters());
    }

    public function testGetEmailPendingQueryForMaxMinIdCountWithMaxMinIdsDefined()
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

        $expectedQuery = "SELECT count(distinct(l.id)) as count, MIN(l.id) as min_id, MAX(l.id) as max_id FROM leads l INNER JOIN lead_lists_leads ll ON (ll.leadlist_id IN (22, 33)) AND (ll.lead_id = l.id) AND (ll.manually_removed = :false) WHERE (l.id NOT IN (SELECT dnc.lead_id FROM lead_donotcontact dnc WHERE (dnc.channel = 'email') AND (dnc.lead_id >= :minContactId) AND (dnc.lead_id <= :maxContactId))) AND (l.id NOT IN (SELECT stat.lead_id FROM email_stats stat WHERE (stat.email_id = 5) AND (stat.lead_id >= :minContactId) AND (stat.lead_id <= :maxContactId))) AND (l.id NOT IN (SELECT mq.lead_id FROM message_queue mq WHERE (mq.channel = 'email') AND (mq.status <> 'sent') AND (mq.channel_id = 5) AND (mq.lead_id >= :minContactId) AND (mq.lead_id <= :maxContactId))) AND (l.id >= :minContactId) AND (l.id <= :maxContactId) AND ((l.email IS NOT NULL) AND (l.email <> ''))";

        $expectedParams = [
            'false'        => false,
            'minContactId' => 10,
            'maxContactId' => 1000,
        ];

        $this->assertEquals($expectedQuery, $query->getSql());
        $this->assertEquals($expectedParams, $query->getParameters());
    }
}
