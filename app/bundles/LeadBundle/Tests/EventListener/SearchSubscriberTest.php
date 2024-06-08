<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\Doctrine\MockedConnectionTrait;
use Mautic\EmailBundle\Entity\EmailRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Event\LeadBuildSearchEvent;
use Mautic\LeadBundle\EventListener\SearchSubscriber;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class SearchSubscriberTest extends TestCase
{
    use MockedConnectionTrait;

    /**
     * Tests emailread search command.
     */
    public function testOnBuildSearchCommands(): void
    {
        $contactRepository = $this->createMock(LeadRepository::class);
        $emailRepository   = $this->createMock(EmailRepository::class);
        $connection        = $this->getMockedConnection();
        $mockPlatform      = $this->createMock(AbstractPlatform::class);
        $leadModel         = $this->createMock(LeadModel::class);
        $translator        = $this->createMock(TranslatorInterface::class);
        $security          = $this->createMock(CorePermissions::class);
        $twig              = $this->createMock(Environment::class);

        $contactRepository->method('applySearchQueryRelationship')
            ->willReturnCallback(
                function (QueryBuilder $q, array $tables, $innerJoinTables, $whereExpression = null, $having = null): void {
                    // the following code is taken from LeadRepository class
                    $primaryTable = $tables[0];
                    unset($tables[0]);
                    $joinType = ($innerJoinTables) ? 'join' : 'leftJoin';
                    $joins    = $q->getQueryPart('join');
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

        $connection->method('getExpressionBuilder')
            ->willReturn(new ExpressionBuilder($connection));

        $mockPlatform->method('getName')
            ->willReturn('mysql');

        $contactRepository->method('getEntity')
            ->willReturn(null);

        $contactRepository->method('createQueryBuilder')
            ->willReturn(new QueryBuilder($connection));

        $leadModel->method('getRepository')
            ->willReturn($contactRepository);

        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($key) {
                return preg_replace('/^.*\.([^\.]*)$/', '\1', $key); // return command name
            });

        $subscriber = new SearchSubscriber(
            $leadModel,
            $emailRepository,
            $translator,
            $security,
            $twig
        );

        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber($subscriber);

        $alias = 'mytestalias';

        $qb = new QueryBuilder($connection);
        $qb->from('lead', 'l');

        // test email read
        $event = new LeadBuildSearchEvent('1', 'email_read', $alias, false, $qb);
        $dispatcher->dispatch($event, LeadEvents::LEAD_BUILD_SEARCH_COMMANDS);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  FROM lead l INNER JOIN test_email_stats es ON l.id = es.lead_id WHERE (es.email_id = ?) AND (es.is_read = ?) GROUP BY l.id', $sql);

        // test email sent
        $qb = new QueryBuilder($connection);
        $qb->from('lead', 'l');

        $event = new LeadBuildSearchEvent('1', 'email_sent', $alias, false, $qb);
        $dispatcher->dispatch($event, LeadEvents::LEAD_BUILD_SEARCH_COMMANDS);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  FROM lead l INNER JOIN test_email_stats es ON l.id = es.lead_id WHERE es.email_id = ? GROUP BY l.id', $sql);

        // test email pending
        $qb = new QueryBuilder($connection);
        $qb->from('lead', 'l');

        $event = new LeadBuildSearchEvent('1', 'email_pending', $alias, false, $qb);
        $dispatcher->dispatch($event, LeadEvents::LEAD_BUILD_SEARCH_COMMANDS);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  FROM lead l INNER JOIN test_message_queue mq ON l.id = mq.lead_id WHERE (mq.channel_id = ?) AND (mq.channel = ?) AND (mq.status = ?) GROUP BY l.id', $sql);

        // test email queued
        $qb = new QueryBuilder($connection);
        $qb->from('lead', 'l');

        $event = new LeadBuildSearchEvent('1', 'email_queued', $alias, false, $qb);
        $dispatcher->dispatch($event, LeadEvents::LEAD_BUILD_SEARCH_COMMANDS);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  FROM lead l INNER JOIN test_message_queue mq ON l.id = mq.lead_id WHERE (mq.channel_id = ?) AND (mq.channel = ?) AND (mq.status IN (?, ?)) GROUP BY l.id', $sql);

        // test sms sent
        $qb = new QueryBuilder($connection);
        $qb->from('lead', 'l');

        $event = new LeadBuildSearchEvent('1', 'sms_sent', $alias, false, $qb);
        $dispatcher->dispatch($event, LeadEvents::LEAD_BUILD_SEARCH_COMMANDS);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  FROM lead l INNER JOIN test_sms_message_stats ss ON l.id = ss.lead_id WHERE ss.sms_id = ? GROUP BY l.id', $sql);

        // test web sent
        $qb = new QueryBuilder($connection);
        $qb->from('lead', 'l');

        $event = new LeadBuildSearchEvent('1', 'web_sent', $alias, false, $qb);
        $dispatcher->dispatch($event, LeadEvents::LEAD_BUILD_SEARCH_COMMANDS);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  FROM lead l INNER JOIN test_push_notification_stats ns ON l.id = ns.lead_id INNER JOIN test_push_notifications pn ON pn.id = ns.notification_id WHERE (pn.id = ?) AND (pn.mobile = ?) GROUP BY l.id', $sql);

        // test mobile sent
        $qb = new QueryBuilder($connection);
        $qb->from('lead', 'l');

        $event = new LeadBuildSearchEvent('1', 'mobile_sent', $alias, false, $qb);
        $dispatcher->dispatch($event, LeadEvents::LEAD_BUILD_SEARCH_COMMANDS);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  FROM lead l INNER JOIN test_push_notification_stats ns ON l.id = ns.lead_id INNER JOIN test_push_notifications pn ON pn.id = ns.notification_id WHERE (pn.id = ?) AND (pn.mobile = ?) GROUP BY l.id', $sql);

        // test import id
        $qb = new QueryBuilder($connection);
        $qb->from('lead', 'l');

        $event = new LeadBuildSearchEvent('1', 'import_id', $alias, false, $qb);
        $dispatcher->dispatch($event, LeadEvents::LEAD_BUILD_SEARCH_COMMANDS);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  FROM lead l INNER JOIN test_lead_event_log lel ON l.id = lel.lead_id WHERE (lel.object_id = ?) AND (lel.object = ?) GROUP BY l.id', $sql);

        // test import action
        $qb = new QueryBuilder($connection);
        $qb->from('lead', 'l');

        $event = new LeadBuildSearchEvent('1', 'import_action', $alias, false, $qb);
        $dispatcher->dispatch($event, LeadEvents::LEAD_BUILD_SEARCH_COMMANDS);
        $sql = preg_replace('/:\w+/', '?', $event->getQueryBuilder()->getSQL());
        $this->assertEquals('SELECT  FROM lead l INNER JOIN test_lead_event_log lel ON l.id = lel.lead_id WHERE lel.action = ? GROUP BY l.id', $sql);
    }
}
