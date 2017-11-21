<?php
/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Tests\CommonMocks;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\LeadBuildSearchEvent;
use Mautic\LeadBundle\EventListener\SearchSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventDispatcher;

/**
 * Class SearchSubscriberTest.
 */
class SearchSubscriberTest extends CommonMocks
{
    /**
     * Tests emailread search command.
     */
    public function testOnBuildSearchCommands()
    {
        list($leadRepo, $connection, $em) = $this->getReflectedMethod();

        $leadModel = $this->getMockBuilder(LeadModel::class)
                          ->disableOriginalConstructor()
                          ->setMethods(['getRepository'])
                          ->getMock();
        $leadModel->method('getRepository')
                  ->willReturn($leadRepo);

        $translator = $this->getTranslatorMock();
        $translator->expects($this->any())
                   ->method('trans')
                   ->willReturnCallback(function ($key) {
                       return preg_replace('/^.*\.([^\.]*)$/', '\1', $key); // return command name
                   });

        $subscriber = new SearchSubscriber($leadModel, $em);
        $subscriber->setTranslator($translator);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);

        $alias = 'mytestalias';

        // test email read
        $qb    = $connection->createQueryBuilder();
        $event = new LeadBuildSearchEvent('1', 'email_read', $alias, false, $qb);
        $dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  WHERE (es.email_id = ?) AND (es.is_read = ?) GROUP BY l.id', $sql);

        // test email sent
        $qb    = $connection->createQueryBuilder();
        $event = new LeadBuildSearchEvent('1', 'email_sent', $alias, false, $qb);
        $dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  WHERE es.email_id = ? GROUP BY l.id', $sql);

        // test email pending
        $qb    = $connection->createQueryBuilder();
        $event = new LeadBuildSearchEvent('1', 'email_pending', $alias, false, $qb);
        $dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  WHERE (mq.channel_id = ?) AND (mq.channel = ?) AND (mq.status = ?) GROUP BY l.id', $sql);

        // test email queued
        $qb    = $connection->createQueryBuilder();
        $event = new LeadBuildSearchEvent('1', 'email_queued', $alias, false, $qb);
        $dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  WHERE (mq.channel_id = ?) AND (mq.channel = ?) AND (mq.status = ?) GROUP BY l.id', $sql);

        // test sms sent
        $qb    = $connection->createQueryBuilder();
        $event = new LeadBuildSearchEvent('1', 'sms_sent', $alias, false, $qb);
        $dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  WHERE ss.sms_id = ? GROUP BY l.id', $sql);

        // test web sent
        $qb    = $connection->createQueryBuilder();
        $event = new LeadBuildSearchEvent('1', 'web_sent', $alias, false, $qb);
        $dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  WHERE (pn.id = ?) AND (pn.mobile = ?) GROUP BY l.id', $sql);

        // test mobile sent
        $qb    = $connection->createQueryBuilder();
        $event = new LeadBuildSearchEvent('1', 'mobile_sent', $alias, false, $qb);
        $dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  WHERE (pn.id = ?) AND (pn.mobile = ?) GROUP BY l.id', $sql);
    }

    /**
     * @return array
     */
    private function getReflectedMethod()
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
        $mockRepository = $this->getMockBuilder(LeadRepository::class)
                               ->disableOriginalConstructor()
                               ->setMethods(['getEntityManager', 'applySearchQueryRelationship', 'getEntity'])
                               ->getMock();
        $mockRepository->method('applySearchQueryRelationship')
                       ->willReturnCallback(
                            function (QueryBuilder $q, array $tables, $innerJoinTables, $whereExpression = null, $having = null) {
                                // the following code is taken from LeadRepository class
                                $primaryTable = $tables[0];
                                unset($tables[0]);
                                $joinType = ($innerJoinTables) ? 'join' : 'leftJoin';
                                $joins = $q->getQueryPart('join');
                                if (!array_key_exists($primaryTable['alias'], $joins)) {
                                    $q->$joinType(
                                        $primaryTable['from_alias'],
                                        MAUTIC_TABLE_PREFIX.$primaryTable['table'],
                                        $primaryTable['alias'],
                                        $primaryTable['condition']
                                    );
                                    foreach ($tables as $table) {
                                        $q->$joinType($table['from_alias'], MAUTIC_TABLE_PREFIX.$table['table'], $table['alias'], $table['condition']);
                                    }
                                    if ($whereExpression) {
                                        $q->andWhere($whereExpression);
                                    }
                                    if ($having) {
                                        $q->andHaving($having);
                                    }
                                    $q->groupBy('l.id');
                                }
                            }
                       );

        $mockConnection = $this->getMockBuilder(Connection::class)
                               ->disableOriginalConstructor()
                               ->getMock();
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

        $mockSchemaManager = $this->getMockBuilder(MySqlSchemaManager::class)
                                  ->disableOriginalConstructor()
                                  ->getMock();

        $mockConnection->method('getSchemaManager')
                       ->willReturn($mockSchemaManager);

        $mockPlatform = $this->getMockBuilder(AbstractPlatform::class)
                             ->disableOriginalConstructor()
                             ->getMock();
        $mockPlatform->method('getName')
                     ->willReturn('mysql');
        $mockConnection->method('getDatabasePlatform')
                       ->willReturn($mockPlatform);

        $mockEntityManager = $this->getMockBuilder(EntityManager::class)
                                  ->disableOriginalConstructor()
                                  ->setMethods(['getConnection', 'getRepository'])
                                  ->getMock();
        $mockEntityManager->method('getConnection')
                          ->willReturn($mockConnection);
        $mockEntityManager->method('getRepository')
                          ->willReturn($mockRepository);

        $mockRepository->method('getEntityManager')
                       ->willReturn($mockEntityManager);
        $mockRepository->method('getEntity')
                       ->willReturn(null);

        $mockConnection->method('createQueryBuilder')
            ->willReturnCallback(
                function () use ($mockConnection) {
                    return new QueryBuilder($mockConnection);
                }
            );

        return [$mockRepository, $mockConnection, $mockEntityManager];
    }
}
