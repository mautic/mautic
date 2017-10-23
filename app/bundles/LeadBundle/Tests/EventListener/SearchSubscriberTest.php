<?php
/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\MySqlSchemaManager;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\TextType;
use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Tests\CommonMocks;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\LeadBuildSearchEvent;
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
                       return preg_replace('/^.*\.email(.*)$/', 'email_\1', $key);
                   });

        $subscriber = new SearchSubscriber($leadModel);
        $subscriber->setTranslator($translator);

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);

        $alias = ':mytestalias';

        // test email read
        $qb    = $connection->createQueryBuilder();
        $event = new LeadBuildSearchEvent(['command' => 'email_read'], null, $alias, 'expr', $qb, $em);
        $dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
        $this->assertEquals('SELECT  WHERE (es.is_read = 1) AND (es.email_id = :mytestalias) GROUP BY l.id', $event->getQueryBuilder()->getSQL());

        // test email sent
        $qb    = $connection->createQueryBuilder();
        $event = new LeadBuildSearchEvent(['command' => 'email_sent'], null, $alias, 'expr', $qb, $em);
        $dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
        $this->assertEquals('SELECT  WHERE es.email_id = :mytestalias GROUP BY l.id', $event->getQueryBuilder()->getSQL());

        // test email pending
        $qb    = $connection->createQueryBuilder();
        $event = new LeadBuildSearchEvent(['command' => 'email_pending', 'string' => '1'], null, $alias, 'expr', $qb, $em);
        $dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
        $this->assertEquals("SELECT  WHERE (mq.channel = 'email' and mq.status = 'pending') AND (mq.channel_id = :mytestalias) GROUP BY l.id", $event->getQueryBuilder()->getSQL());

        // test email queued
        $qb    = $connection->createQueryBuilder();
        $event = new LeadBuildSearchEvent(['command' => 'email_queued'], null, $alias, 'expr', $qb, $em);
        $dispatcher->dispatch(LeadEvents::LEAD_BUILD_SEARCH_COMMANDS, $event);
        $this->assertEquals("SELECT  WHERE (mq.channel = 'email' and mq.status = 'sent') AND (mq.channel_id = :mytestalias) GROUP BY l.id", $event->getQueryBuilder()->getSQL());
    }

    /**
     * @return array
     */
    private function getReflectedMethod()
    {
        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
        $mockRepository = $this->getMockBuilder(LeadRepository::class)
                               ->disableOriginalConstructor()
                               ->setMethods(['getEntityManager', 'applySearchQueryRelationship', 'generateFilterExpression', 'getEntity'])
                               ->getMock();
        $mockRepository->method('applySearchQueryRelationship')
                       ->willReturnCallback(
                            function (QueryBuilder $q, array $tables, $innerJoinTables, $whereExpression = null, $having = null) {
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
                                        $q->$joinType($table['from_alias'], MAUTIC_TABLE_PREFIX.$table['table'], $table['alias'], $table['condition ']);
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
        $mockRepository->method('generateFilterExpression')
                       ->willReturnCallback(
                            function ($q, $column, $operator, $parameter, $includeIsNull, CompositeExpression $appendTo = null) {
                                if (!is_array($parameter) && 0 !== strpos($parameter, ':')) {
                                    $parameter = ":$parameter";
                                }
                                if (null === $includeIsNull) {
                                    $includeIsNull = in_array($operator, ['neq', 'notLike', 'notIn'], true);
                                }
                                if ($includeIsNull) {
                                    $expr = $q->expr()
                                             ->orX(
                                                 $q->expr()
                                                   ->$operator(
                                                       $column,
                                                       $parameter
                                                   ),
                                                 $q->expr()
                                                   ->isNull($column)
                                             );
                                } else {
                                    $expr = $q->expr()
                                             ->$operator(
                                                 $column,
                                                 $parameter
                                             );
                                }
                                if ($appendTo) {
                                    $appendTo->add($expr);

                                    return $appendTo;
                                }

                                return $expr;
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
        $mockSchemaManager->method('listTableColumns')
            ->willReturnCallback(
                function ($table) {
                    $mockType = $this->getMockBuilder(TextType::class)
                                     ->disableOriginalConstructor()
                                     ->getMock();
                    $name = '';
                    switch ($table) {
                        case 'leads':
                            $name = 'email';
                            break;
                        case 'companies':
                            $name = 'company_email';
                            break;
                    }
                    $column = new Column($name, $mockType);

                    return [
                        $name => $column,
                    ];
                }
            );

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

        $mockStatement = $this->getMockBuilder(Statement::class)
                              ->disableOriginalConstructor()
                              ->getMock();

        $filters = [
            [
                'id'      => 1,
                'filters' => serialize([]),
            ],
            [
                'id'      => 2,
                'filters' => serialize([]),
            ],
        ];
        $mockStatement->method('fetchAll')
                      ->willReturn($filters);

        $mockConnection->method('createQueryBuilder')
            ->willReturnCallback(
                function () use ($mockConnection, $mockStatement) {
                    $qb = $this->getMockBuilder(QueryBuilder::class)
                               ->setConstructorArgs([$mockConnection])
                               ->setMethods(['execute'])
                               ->getMock();

                    $qb->method('execute')
                       ->willReturn($mockStatement);

                    return $qb;
                }
            );

        return [$mockRepository, $mockConnection, $mockEntityManager];
    }
}
