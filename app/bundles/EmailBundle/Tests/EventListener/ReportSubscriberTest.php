<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\EventListener\ReportSubscriber;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Translation\TranslatorInterface;

class ReportSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|Connection
     */
    private $connectionMock;

    /**
     * @var MockObject|CompanyReportData
     */
    private $companyReportDataMock;

    /**
     * @var MockObject|StatRepository
     */
    private $statRepository;

    /**
     * @var MockObject|GeneratedColumnsProviderInterface
     */
    private $generatedColumnsProvider;

    /**
     * @var MockObject|Report
     */
    private $report;

    private $channelListHelper;

    /**
     * @var MockObject|ChannelListHelper
     */
    private $queryBuilder;

    /**
     * @var ReportSubscriber
     */
    private $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');

        $this->connectionMock           = $this->createMock(Connection::class);
        $this->companyReportDataMock    = $this->createMock(CompanyReportData::class);
        $this->statRepository           = $this->createMock(StatRepository::class);
        $this->generatedColumnsProvider = $this->createMock(GeneratedColumnsProviderInterface::class);
        $this->subscriber               = new ReportSubscriber(
            $this->connectionMock,
            $this->companyReportDataMock,
            $this->statRepository,
            $this->generatedColumnsProvider
        );

        $this->report            = $this->createMock(Report::class);
        $this->channelListHelper = $this->createMock(ChannelListHelper::class);
        $this->queryBuilder      = new QueryBuilder($this->connectionMock);
    }

    public function testOnReportGenerateForEmailStatsWhenDncIsUsed(): void
    {
        $this->report->expects($this->once())
            ->method('getSource')
            ->willReturn(ReportSubscriber::CONTEXT_EMAIL_STATS);

        $this->report->expects($this->any())
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn([
                'es.email_address',
                'bounced',
                'es.date_read',
            ]);

        $event = new ReportGeneratorEvent(
            $this->report,
            [],
            $this->queryBuilder,
            $this->channelListHelper
        );

        $this->subscriber->onReportGenerate($event);

        $this->assertSame(
            "SELECT  FROM email_stats es LEFT JOIN lead_donotcontact dnc ON es.email_id = dnc.channel_id AND dnc.channel='email' AND es.lead_id = dnc.lead_id WHERE es.date_sent IS NULL OR (es.date_sent BETWEEN :dateFrom AND :dateTo)",
            $this->queryBuilder->getSQL()
        );
    }

    public function testOnReportGenerateForEmailStatsWhenVariantIsUsed(): void
    {
        $this->report->expects($this->once())
            ->method('getSource')
            ->willReturn(ReportSubscriber::CONTEXT_EMAIL_STATS);

        $this->report->expects($this->any())
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['vp.subject']);

        $event = new ReportGeneratorEvent(
            $this->report,
            [],
            $this->queryBuilder,
            $this->channelListHelper
        );

        $this->subscriber->onReportGenerate($event);

        $this->assertSame(
            'SELECT  FROM email_stats es LEFT JOIN emails e ON e.id = es.email_id LEFT JOIN emails vp ON vp.id = e.variant_parent_id WHERE es.date_sent IS NULL OR (es.date_sent BETWEEN :dateFrom AND :dateTo)',
            $this->queryBuilder->getSQL()
        );
    }

    public function testOnReportGenerateForEmailStatsWhenClickIsUsed(): void
    {
        $this->report->expects($this->once())
            ->method('getSource')
            ->willReturn(ReportSubscriber::CONTEXT_EMAIL_STATS);

        $this->report->expects($this->any())
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['unique_hits']);

        $this->report->expects($this->any())
            ->method('getFilters')
            ->willReturn([]);

        $this->connectionMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn(new QueryBuilder($this->connectionMock));

        $event = new ReportGeneratorEvent(
            $this->report,
            [],
            $this->queryBuilder,
            $this->channelListHelper
        );

        $this->subscriber->onReportGenerate($event);

        $this->assertSame(
            "SELECT  FROM email_stats es LEFT JOIN (SELECT COUNT(ph.id) AS hits, COUNT(DISTINCT(ph.redirect_id)) AS unique_hits, cut2.channel_id, ph.lead_id FROM channel_url_trackables cut2 INNER JOIN page_hits ph ON cut2.redirect_id = ph.redirect_id AND cut2.channel_id = ph.source_id WHERE cut2.channel = 'email' AND ph.source = 'email' GROUP BY cut2.channel_id, ph.lead_id) cut ON es.email_id = cut.channel_id AND es.lead_id = cut.lead_id WHERE es.date_sent IS NULL OR (es.date_sent BETWEEN :dateFrom AND :dateTo)",
            $this->queryBuilder->getSQL()
        );
    }

    public function testOnReportGenerateForEmailStatsWhenCampaignIsUsed(): void
    {
        $this->report->expects($this->once())
            ->method('getSource')
            ->willReturn(ReportSubscriber::CONTEXT_EMAIL_STATS);

        $this->report->expects($this->any())
            ->method('getSelectAndAggregatorAndOrderAndGroupByColumns')
            ->willReturn(['cmp.name']);

        $this->report->expects($this->any())
            ->method('getFilters')
            ->willReturn([]);

        $this->connectionMock->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn(new QueryBuilder($this->connectionMock));

        $event = new ReportGeneratorEvent(
            $this->report,
            [],
            $this->queryBuilder,
            $this->channelListHelper
        );

        $this->subscriber->onReportGenerate($event);

        $this->assertSame(
            "SELECT  FROM email_stats es LEFT JOIN leads l ON l.id = es.lead_id LEFT JOIN campaign_lead_event_log clel ON clel.channel='email' AND es.email_id = clel.channel_id AND clel.lead_id = l.id LEFT JOIN campaigns cmp ON cmp.id = clel.campaign_id WHERE es.date_sent IS NULL OR (es.date_sent BETWEEN :dateFrom AND :dateTo)",
            $this->queryBuilder->getSQL()
        );
    }

    public function testOnReportGraphGenerateForEmailContextWithEmailGraph(): void
    {
        $eventMock        = $this->createMock(ReportGraphEvent::class);
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $chartQueryMock   = $this->createMock(ChartQuery::class);
        $statementMock    = $this->createMock(Statement::class);
        $translatorMock   = $this->createMock(TranslatorInterface::class);

        $queryBuilderMock->method('execute')->willReturn($statementMock);

        $eventMock->expects($this->once())
            ->method('getRequestedGraphs')
            ->willReturn(['mautic.email.graph.pie.read.ingored.unsubscribed.bounced']);

        $eventMock->method('checkContext')
            ->withConsecutive(
                [['email.stats', 'emails']],
                ['emails']
            )
            ->willReturn(true);

        $eventMock->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilderMock);

        $eventMock->expects($this->once())
            ->method('getOptions')
            ->willReturn(['chartQuery' => $chartQueryMock, 'translator' => $translatorMock]);

        $queryBuilderMock->expects($this->once())
            ->method('select')
            ->with('SUM(DISTINCT e.sent_count) as sent_count,
                        SUM(DISTINCT e.read_count) as read_count,
                        count(CASE WHEN dnc.id and dnc.reason = '.DoNotContact::UNSUBSCRIBED.' THEN 1 ELSE null END) as unsubscribed,
                        count(CASE WHEN dnc.id and dnc.reason = '.DoNotContact::BOUNCED.' THEN 1 ELSE null END) as bounced'
            );

        // Expect the DNC table has not been joined yet.
        $queryBuilderMock->expects($this->once())
            ->method('getQueryParts')
            ->willReturn(['join' => []]);

        $queryBuilderMock->expects($this->once())
            ->method('leftJoin')
            ->with(
                ReportSubscriber::EMAILS_PREFIX,
                'lead_donotcontact',
                ReportSubscriber::DNC_PREFIX,
                'e.id = dnc.channel_id AND dnc.channel=\'email\''
            );

        $this->subscriber->onReportGraphGenerate($eventMock);
    }
}
