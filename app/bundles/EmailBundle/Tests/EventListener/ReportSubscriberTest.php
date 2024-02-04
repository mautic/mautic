<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Doctrine\Provider\GeneratedColumnsProviderInterface;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Test\Doctrine\MockedConnectionTrait;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\EventListener\ReportSubscriber;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ReportSubscriberTest extends \PHPUnit\Framework\TestCase
{
    use MockedConnectionTrait;
    /**
     * @var MockObject|Connection
     */
    private \PHPUnit\Framework\MockObject\MockObject $connectionMock;

    /**
     * @var MockObject|CompanyReportData
     */
    private \PHPUnit\Framework\MockObject\MockObject $companyReportDataMock;

    /**
     * @var MockObject|StatRepository
     */
    private \PHPUnit\Framework\MockObject\MockObject $statRepository;

    /**
     * @var MockObject&GeneratedColumnsProviderInterface
     */
    private \PHPUnit\Framework\MockObject\MockObject $generatedColumnsProvider;

    /**
     * @var MockObject|Report
     */
    private \PHPUnit\Framework\MockObject\MockObject $report;

    private \Mautic\ChannelBundle\Helper\ChannelListHelper $channelListHelper;

    /**
     * @var MockObject|QueryBuilder
     */
    private \Doctrine\DBAL\Query\QueryBuilder $queryBuilder;

    private ReportSubscriber $subscriber;

    /**
     * @var MockObject|FieldsBuilder
     */
    private \PHPUnit\Framework\MockObject\MockObject $fieldsBuilderMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connectionMock           = $this->getMockedConnection();
        $this->companyReportDataMock    = $this->createMock(CompanyReportData::class);
        $this->statRepository           = $this->createMock(StatRepository::class);
        $this->generatedColumnsProvider = $this->createMock(GeneratedColumnsProviderInterface::class);
        $this->fieldsBuilderMock        = $this->createMock(FieldsBuilder::class);
        $this->subscriber               = new ReportSubscriber(
            $this->connectionMock,
            $this->companyReportDataMock,
            $this->statRepository,
            $this->generatedColumnsProvider,
            $this->fieldsBuilderMock
        );

        $this->report             = $this->createMock(Report::class);
        $this->channelListHelper  = new ChannelListHelper($this->createMock(EventDispatcherInterface::class), $this->createMock(Translator::class));
        $this->queryBuilder       = new QueryBuilder($this->connectionMock);
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
            'SELECT  FROM '.MAUTIC_TABLE_PREFIX.'email_stats es LEFT JOIN '.MAUTIC_TABLE_PREFIX."lead_donotcontact dnc ON es.email_id = dnc.channel_id AND dnc.channel='email' AND es.lead_id = dnc.lead_id WHERE es.date_sent IS NULL OR (es.date_sent BETWEEN :dateFrom AND :dateTo) GROUP BY es.id",
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
            'SELECT  FROM '.MAUTIC_TABLE_PREFIX.'email_stats es LEFT JOIN '.MAUTIC_TABLE_PREFIX.'emails e ON e.id = es.email_id LEFT JOIN '.MAUTIC_TABLE_PREFIX.'emails vp ON vp.id = e.variant_parent_id WHERE es.date_sent IS NULL OR (es.date_sent BETWEEN :dateFrom AND :dateTo) GROUP BY es.id',
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

        $this->connectionMock->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                new QueryBuilder($this->connectionMock),
                new QueryBuilder($this->connectionMock)
            );

        $event = new ReportGeneratorEvent(
            $this->report,
            [],
            $this->queryBuilder,
            $this->channelListHelper
        );

        $this->subscriber->onReportGenerate($event);

        $this->assertSame(
            'SELECT  FROM '.MAUTIC_TABLE_PREFIX.'email_stats es LEFT JOIN (SELECT COUNT(ph.id) AS hits, COUNT(DISTINCT(ph.redirect_id)) AS unique_hits, cut2.channel_id, ph.lead_id FROM '.MAUTIC_TABLE_PREFIX.'channel_url_trackables cut2 INNER JOIN '.MAUTIC_TABLE_PREFIX."page_hits ph ON cut2.redirect_id = ph.redirect_id AND cut2.channel_id = ph.source_id WHERE cut2.channel = 'email' AND ph.source = 'email' GROUP BY cut2.channel_id, ph.lead_id) cut ON es.email_id = cut.channel_id AND es.lead_id = cut.lead_id WHERE es.date_sent IS NULL OR (es.date_sent BETWEEN :dateFrom AND :dateTo) GROUP BY es.id",
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

        $this->connectionMock->expects($this->exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                new QueryBuilder($this->connectionMock),
                new QueryBuilder($this->connectionMock)
            );

        $event = new ReportGeneratorEvent(
            $this->report,
            [],
            $this->queryBuilder,
            $this->channelListHelper
        );

        $this->subscriber->onReportGenerate($event);

        $this->assertSame(
            'SELECT  FROM '.MAUTIC_TABLE_PREFIX.'email_stats es LEFT JOIN '.MAUTIC_TABLE_PREFIX.'leads l ON l.id = es.lead_id LEFT JOIN '.MAUTIC_TABLE_PREFIX."campaign_lead_event_log clel ON clel.channel='email' AND es.email_id = clel.channel_id AND clel.lead_id = l.id LEFT JOIN ".MAUTIC_TABLE_PREFIX.'campaigns cmp ON cmp.id = clel.campaign_id WHERE es.date_sent IS NULL OR (es.date_sent BETWEEN :dateFrom AND :dateTo) GROUP BY es.id',
            $this->queryBuilder->getSQL()
        );
    }

    public function testOnReportGraphGenerateForEmailContextWithEmailGraph(): void
    {
        $eventMock         = $this->createMock(ReportGraphEvent::class);
        $queryBuilderMock  = $this->createMock(QueryBuilder::class);
        $chartQueryMock    = $this->createMock(ChartQuery::class);
        $resultMock        = $this->createMock(Result::class);
        $translatorMock    = $this->createMock(TranslatorInterface::class);

        $queryBuilderMock->method('execute')->willReturn($resultMock);
        $resultMock->method('fetchOne')->willReturn([]);

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
                MAUTIC_TABLE_PREFIX.'lead_donotcontact',
                ReportSubscriber::DNC_PREFIX,
                'e.id = dnc.channel_id AND dnc.channel=\'email\''
            );

        $this->subscriber->onReportGraphGenerate($eventMock);
    }

    public function testOnReportBuilderWithEmailSentContext(): void
    {
        $translatorMock     = $this->createMock(TranslatorInterface::class);
        $reportHelper       = new ReportHelper($this->createMock(EventDispatcherInterface::class));

        $this->companyReportDataMock
            ->expects($this->any())
            ->method('getCompanyData')
            ->willReturn([
                'comp.companyname' => [
                    'label' => 'Company Company Name',
                    'type'  => 'string',
                ],
            ]);

        $this->fieldsBuilderMock
            ->expects($this->any())
            ->method('getLeadFilter')
            ->willReturn([
                'tag' => [
                    'label'     => 'mautic.core.filter.tags',
                    'type'      => 'multiselect',
                    'list'      => ['A', 'B', 'C'],
                    'operators' => [
                        'in'       => 'mautic.core.operator.in',
                        'notIn'    => 'mautic.core.operator.notin',
                        'empty'    => 'mautic.core.operator.isempty',
                        'notEmpty' => 'mautic.core.operator.isnotempty',
                    ],
                ],
            ]);

        $event = new ReportBuilderEvent($translatorMock, $this->channelListHelper, ReportSubscriber::CONTEXT_EMAIL_STATS, [], $reportHelper);
        $this->subscriber->onReportBuilder($event);
        $tables = $event->getTables();

        $this->assertArrayHasKey('emails', $tables);
        $this->assertArrayHasKey('email.stats', $tables);

        $emailStatsColsAndFilters = [
            'e.subject' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'subject',
            ],
            'e.lang' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'lang',
            ],
            'e.sent_count' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'sent_count',
            ],
            'e.revision' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'revision',
            ],
            'e.variant_start_date' => [
                'label'          => null,
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(e.variant_start_date)',
                'alias'          => 'variant_start_date',
            ],
            'c.id' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'category_id',
            ],
            'c.title' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'category_title',
            ],
            'unsubscribed' => [
                'alias'   => 'unsubscribed',
                'label'   => null,
                'type'    => 'bool',
                'formula' => 'IF(dnc.id IS NOT NULL AND dnc.reason=1, 1, 0)',
            ],
            'bounced' => [
                'alias'   => 'bounced',
                'label'   => null,
                'type'    => 'bool',
                'formula' => 'IF(dnc.id IS NOT NULL AND dnc.reason=2, 1, 0)',
            ],
            'vp.id' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'id',
            ],
            'vp.subject' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'subject',
            ],
            'hits' => [
                'alias'   => 'hits',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IFNULL(cut.hits, 0)',
            ],
            'unique_hits' => [
                'alias'   => 'unique_hits',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IFNULL(cut.unique_hits, 0)',
            ],
            'is_hit' => [
                'alias'   => 'is_hit',
                'label'   => null,
                'type'    => 'bool',
                'formula' => 'IF(cut.hits is NULL, 0, 1)',
            ],
            'read_delay' => [
                'alias'   => 'read_delay',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IF(es.date_read IS NOT NULL, TIMEDIFF(es.date_read, es.date_sent), \'-\')',
            ],
            'es.email_address' => [
                'label' => null,
                'type'  => 'email',
                'alias' => 'email_address',
            ],
            'es.date_sent' => [
                'label'          => null,
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(es.date_sent)',
                'alias'          => 'date_sent',
            ],
            'es.is_read' => [
                'label' => null,
                'type'  => 'bool',
                'alias' => 'is_read',
            ],
            'es.is_failed' => [
                'label' => null,
                'type'  => 'bool',
                'alias' => 'is_failed',
            ],
            'es.viewed_in_browser' => [
                'label' => null,
                'type'  => 'bool',
                'alias' => 'viewed_in_browser',
            ],
            'es.date_read' => [
                'label'          => null,
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(es.date_read)',
                'alias'          => 'date_read',
            ],
            'es.retry_count' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'retry_count',
            ],
            'es.source' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'source',
            ],
            'es.source_id' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'source_id',
            ],
            'clel.campaign_id' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'campaign_id',
            ],
            'cmp.name' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'name',
            ],
            'l.id' => [
                'label' => null,
                'type'  => 'int',
                'link'  => 'mautic_contact_action',
                'alias' => 'contactId',
            ],
            'i.ip_address' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'ip_address',
            ],
            'comp.companyname' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'companyname',
            ],
        ];

        $emailStatsCols = [
            'e.subject' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'subject',
            ],
            'e.lang' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'lang',
            ],
            'e.read_count' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'read_count',
            ],
            'read_ratio' => [
                'alias'   => 'read_ratio',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IFNULL(ROUND((e.read_count/e.sent_count)*100, 1), \'0.0\')',
                'suffix'  => '%',
            ],
            'e.sent_count' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'sent_count',
            ],
            'e.revision' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'revision',
            ],
            'e.variant_start_date' => [
                'label'          => null,
                'type'           => 'datetime',
                'groupByFormula' => 'DATE(e.variant_start_date)',
                'alias'          => 'variant_start_date',
            ],
            'e.variant_sent_count' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'variant_sent_count',
            ],
            'e.variant_read_count' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'variant_read_count',
            ],
            'c.id' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'category_id',
            ],
            'c.title' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'category_title',
            ],
            'unsubscribed' => [
                'alias'   => 'unsubscribed',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IFNULL((SELECT ROUND(SUM(IF(dnc.id IS NOT NULL AND dnc.channel_id=e.id AND dnc.reason=1 , 1, 0)), 1) FROM '.MAUTIC_TABLE_PREFIX.'lead_donotcontact dnc), 0)',
            ],
            'unsubscribed_ratio' => [
                'alias'   => 'unsubscribed_ratio',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IFNULL((SELECT ROUND((SUM(IF(dnc.id IS NOT NULL AND dnc.channel_id=e.id AND dnc.reason=1 , 1, 0))/e.sent_count)*100, 1) FROM '.MAUTIC_TABLE_PREFIX.'lead_donotcontact dnc), \'0.0\')',
                'suffix'  => '%',
            ],
            'bounced' => [
                'alias'   => 'bounced',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IFNULL((SELECT ROUND(SUM(IF(dnc.id IS NOT NULL AND dnc.channel_id=e.id AND dnc.reason=2 , 1, 0)), 1) FROM '.MAUTIC_TABLE_PREFIX.'lead_donotcontact dnc), 0)',
            ],
            'bounced_ratio' => [
                'alias'   => 'bounced_ratio',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IFNULL((SELECT ROUND((SUM(IF(dnc.id IS NOT NULL AND dnc.channel_id=e.id AND dnc.reason=2 , 1, 0))/e.sent_count)*100, 1) FROM '.MAUTIC_TABLE_PREFIX.'lead_donotcontact dnc), \'0.0\')',
                'suffix'  => '%',
            ],
            'vp.id' => [
                'label' => null,
                'type'  => 'int',
                'alias' => 'id',
            ],
            'vp.subject' => [
                'label' => null,
                'type'  => 'string',
                'alias' => 'subject',
            ],
            'hits' => [
                'alias'   => 'hits',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IFNULL(cut.hits, 0)',
            ],
            'unique_hits' => [
                'alias'   => 'unique_hits',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IFNULL(cut.unique_hits, 0)',
            ],
            'hits_ratio' => [
                'alias'   => 'hits_ratio',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IFNULL(ROUND(cut.hits/(e.sent_count)*100, 1), \'0.0\')',
                'suffix'  => '%',
            ],
            'unique_ratio' => [
                'alias'   => 'unique_ratio',
                'label'   => null,
                'type'    => 'string',
                'formula' => 'IFNULL(ROUND(cut.unique_hits/(e.sent_count)*100, 1), \'0.0\')',
                'suffix'  => '%',
            ],
        ];

        foreach ($emailStatsColsAndFilters as $key => $val) {
            $this->assertSame($val, $tables['email.stats']['columns'][$key]);
            $this->assertSame($val, $tables['email.stats']['filters'][$key]);
        }

        $this->assertSame([
            'label'     => null,
            'type'      => 'multiselect',
            'list'      => ['A', 'B', 'C'],
            'operators' => [
                'in'       => 'mautic.core.operator.in',
                'notIn'    => 'mautic.core.operator.notin',
                'empty'    => 'mautic.core.operator.isempty',
                'notEmpty' => 'mautic.core.operator.isnotempty',
            ],
            'alias' => 'tag',
        ], $tables['email.stats']['filters']['tag']);

        foreach ($emailStatsCols as $key => $val) {
            $this->assertSame($val, $tables['emails']['columns'][$key]);
        }
    }
}
