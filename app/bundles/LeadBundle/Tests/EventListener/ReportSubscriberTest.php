<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\PointsChangeLogRepository;
use Mautic\LeadBundle\EventListener\ReportSubscriber;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ColumnCollectEvent;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\StageBundle\Model\StageModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ReportSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|LeadModel
     */
    private MockObject $leadModelMock;

    /**
     * @var MockObject|FieldModel
     */
    private MockObject $leadFieldModelMock;

    /**
     * @var MockObject|StageModel
     */
    private MockObject $stageModelMock;

    /**
     * @var MockObject|CampaignModel
     */
    private MockObject $campaignModelMock;

    /**
     * @var MockObject|EventCollector
     */
    private MockObject $eventCollectorMock;

    /**
     * @var MockObject|CompanyModel
     */
    private MockObject $companyModelMock;

    /**
     * @var MockObject|CompanyReportData
     */
    private MockObject $companyReportDataMock;

    /**
     * @var MockObject|FieldsBuilder
     */
    private MockObject $fieldsBuilderMock;

    /**
     * @var MockObject|Translator
     */
    private MockObject $translatorMock;

    /**
     * @var MockObject|ReportGeneratorEvent
     */
    private MockObject $reportGeneratorEventMock;

    private ChannelListHelper $channelListHelperMock;

    private ReportHelper $reportHelperMock;

    /**
     * @var MockObject|CampaignRepository
     */
    private MockObject $campaignRepositoryMock;

    /**
     * @var MockObject|ReportBuilderEvent
     */
    private MockObject $reportBuilderEventMock;

    /**
     * @var MockObject|QueryBuilder
     */
    private MockObject $queryBuilderMock;

    /**
     * @var MockObject|ExpressionBuilder
     */
    private MockObject $expressionBuilderMock;

    /**
     * @var MockObject|ReportGraphEvent
     */
    private MockObject $reportGraphEventMock;

    /**
     * @var MockObject|CompanyRepository
     */
    private MockObject $companyRepositoryMock;

    /**
     * @var MockObject|PointsChangeLogRepository
     */
    private MockObject $pointsChangeLogRepositoryMock;

    /**
     * @var MockObject&Report
     */
    private MockObject $reportMock;

    /**
     * @var MockObject&ReportDataEvent
     */
    private MockObject $reportDataEventMock;

    private ReportSubscriber $reportSubscriber;

    /**
     * @var array
     */
    private $leadColumns = [
        'xx.yy' => [
            'label' => '',
            'type'  => 'bool',
            'alias' => 'first',
        ],
    ];

    /**
     * @var array
     */
    private $leadFilters = [
        'filter' => [
            'label' => 'second',
            'type'  => 'text',
        ],
    ];

    /**
     * @var array
     */
    private $companyColumns = [
        'comp.name' => [
            'label' => 'company_name',
            'type'  => 'text',
        ],
    ];

    protected function setUp(): void
    {
        $this->leadModelMock                    = $this->createMock(LeadModel::class);
        $this->leadFieldModelMock               = $this->createMock(FieldModel::class);
        $this->stageModelMock                   = $this->createMock(StageModel::class);
        $this->campaignModelMock                = $this->createMock(CampaignModel::class);
        $this->eventCollectorMock               = $this->createMock(EventCollector::class);
        $this->companyModelMock                 = $this->createMock(CompanyModel::class);
        $this->companyReportDataMock            = $this->createMock(CompanyReportData::class);
        $this->fieldsBuilderMock                = $this->createMock(FieldsBuilder::class);
        $this->translatorMock                   = $this->createMock(Translator::class);
        $this->reportGeneratorEventMock         = $this->createMock(ReportGeneratorEvent::class);
        $this->reportDataEventMock              = $this->createMock(ReportDataEvent::class);
        $this->channelListHelperMock            = new ChannelListHelper($this->createMock(EventDispatcherInterface::class), $this->createMock(Translator::class));
        $this->reportHelperMock                 = new ReportHelper($this->createMock(EventDispatcherInterface::class));
        $this->campaignRepositoryMock           = $this->createMock(CampaignRepository::class);
        $this->reportBuilderEventMock           = $this->createMock(ReportBuilderEvent::class);
        $this->queryBuilderMock                 = $this->createMock(QueryBuilder::class);
        $this->expressionBuilderMock            = $this->createMock(ExpressionBuilder::class);
        $this->reportGraphEventMock             = $this->createMock(ReportGraphEvent::class);
        $this->companyRepositoryMock            = $this->createMock(CompanyRepository::class);
        $this->pointsChangeLogRepositoryMock    = $this->createMock(PointsChangeLogRepository::class);
        $this->reportMock                       = $this->createMock(Report::class);
        $this->reportSubscriber                 = new ReportSubscriber(
            $this->leadModelMock,
            $this->leadFieldModelMock,
            $this->stageModelMock,
            $this->campaignModelMock,
            $this->eventCollectorMock,
            $this->companyModelMock,
            $this->companyReportDataMock,
            $this->fieldsBuilderMock,
            $this->translatorMock
        );

        $this->queryBuilderMock->expects($this->any())
                ->method('expr')
                ->willReturn($this->expressionBuilderMock);

        $this->queryBuilderMock->expects($this->any())
            ->method('resetQueryParts')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects($this->any())
            ->method('getQueryPart')
            ->willReturnCallback(function ($input) {
                if ('join' === $input) {
                    return [
                        'lp' => [[
                            'joinType'      => 'left',
                            'joinTable'     => 'leads',
                            'joinAlias'     => 'l',
                            'joinCondition' => 'l.id = lp.lead_id',
                        ]],
                        'l' => [[
                            'joinType'      => 'inner',
                            'joinTable'     => 'lead_list_leads',
                            'joinAlias'     => 's',
                            'joinCondition' => 's.lead_id = l.id',
                        ]],
                    ];
                }

                if ('where' === $input) {
                    return '(lp.date_added IS NULL OR (lp.date_added BETWEEN :dateFrom AND :dateTo)) AND (s.leadlist_id = :i3csleadlistid))';
                }

                return [['alias' => 'lp']];
            });

        $this->queryBuilderMock->expects($this->any())
            ->method('from')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects($this->any())
            ->method('leftJoin')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects($this->any())
            ->method('join')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects($this->any())
            ->method('select')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects($this->any())
            ->method('setParameters')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects($this->any())
            ->method('getParameters')
            ->willReturn([]);

        $this->queryBuilderMock->expects($this->any())
            ->method('setMaxResults')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->method('andWhere')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects($this->any())
            ->method('groupBy')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects($this->any())
            ->method('orderBy')
            ->willReturn($this->queryBuilderMock);

        $this->campaignModelMock->method('getRepository')->willReturn($this->campaignRepositoryMock);

        $this->eventCollectorMock->expects($this->any())
            ->method('getEventsArray')
            ->willReturn(
                [
                    'action' => [
                        'email.send' => [
                            'label'           => 'Send email',
                            'description'     => 'Send the selected email to the contact.',
                            'batchEventName'  => 'mautic.email.on_campaign_batch_action',
                            'formType'        => \Mautic\EmailBundle\Form\Type\EmailSendType::class,
                            'formTypeOptions' => [
                                'update_select'    => 'campaignevent_properties_email',
                                'with_email_types' => true,
                            ],
                            'formTheme'      => "MauticEmailBundle:FormTheme\EmailSendList",
                            'channel'        => 'email',
                            'channelIdField' => 'email',
                        ],
                    ],
                    'decision' => [
                        'email.click' => [
                            'label'                  => 'Clicks email',
                            'description'            => 'Trigger actions when an email is clicked. Connect a Send Email action to the top of this decision.',
                            'eventName'              => 'mautic.email.on_campaign_trigger_decision',
                            'formType'               => \Mautic\EmailBundle\Form\Type\EmailClickDecisionType::class,
                            'connectionRestrictions' => [
                                'source' => [
                                    'action' => [
                                        'email.send',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);

        $this->translatorMock->expects($this->any())
            ->method('hasId')
            ->willReturn(false);

        $this->stageModelMock->expects($this->any())
            ->method('getUserStages')
            ->willReturn([
                'stage' => [
                    'id'   => '1',
                    'name' => 'Stage One',
                ],
            ]);

        parent::setUp();
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function eventDataProvider(): array
    {
        return [
            ['leads'],
            ['contact.frequencyrules'],
            ['lead.pointlog'],
            ['contact.attribution.first'],
            ['contact.attribution.multi'],
            ['contact.attribution.last'],
            ['companies'],
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public static function reportGraphEventDataProvider(): array
    {
        return [
            ['leads'],
            ['lead.pointlog'],
            ['contact.attribution.multi'],
            ['companies'],
        ];
    }

    public function testNotRelevantContextBuilder(): void
    {
        $this->reportBuilderEventMock->method('checkContext')
            ->withConsecutive(
                [
                    [
                        'leads',
                        'lead.pointlog',
                        'contact.attribution.multi',
                        'contact.attribution.first',
                        'contact.attribution.last',
                        'contact.frequencyrules',
                    ],
                ]
            )->willReturn(false);

        $this->reportBuilderEventMock->expects($this->never())
            ->method('addTable');

        $this->reportSubscriber->onReportBuilder($this->reportBuilderEventMock);
    }

    public function testNotRelevantContextGenerate(): void
    {
        $this->reportGeneratorEventMock->method('checkContext')
            ->withConsecutive(
                [
                    [
                        'leads',
                        'lead.pointlog',
                        'contact.attribution.multi',
                        'contact.attribution.first',
                        'contact.attribution.last',
                        'contact.frequencyrules',
                    ],
                ],
                [
                    ['companies'],
                ]
            )->willReturn(false);

        $this->reportGeneratorEventMock->expects($this->never())
            ->method('getQueryBuilder');

        $this->reportSubscriber->onReportGenerate($this->reportGeneratorEventMock);
    }

    /**
     * @dataProvider eventDataProvider
     */
    public function testOnReportBuilder(string $event): void
    {
        if ('companies' != $event) {
            $this->fieldsBuilderMock->expects($this->once())
                ->method('getLeadFieldsColumns')
                ->with('l.')
                ->willReturn($this->leadColumns);

            $this->fieldsBuilderMock->expects($this->once())
                ->method('getLeadFilter')
                ->with('l.', 's.')
                ->willReturn($this->leadFilters);

            $this->companyReportDataMock->expects($this->once())
                ->method('getCompanyData')
                ->willReturn($this->companyColumns);
        } else {
            $this->fieldsBuilderMock->expects($this->once())
                ->method('getCompanyFieldsColumns')
                ->with('comp.')
                ->willReturn($this->companyColumns);
        }

        $reportBuilderEvent = new ReportBuilderEvent($this->translatorMock, $this->channelListHelperMock, $event, [], $this->reportHelperMock);

        $this->reportSubscriber->onReportBuilder($reportBuilderEvent);

        $expected = [
            'leads' => [
                'display_name' => 'mautic.lead.leads',
                'columns'      => [
                    'xx.yy' => [
                        'label' => '',
                        'type'  => 'bool',
                        'alias' => 'first',
                    ],
                    'comp.name' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'name',
                    ],
                ],
                'filters' => [
                    'filter' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'filter',
                    ],
                    'comp.name' => [
                        'label' => '',
                        'type'  => 'text',
                        'alias' => 'name',
                    ],
                ],
                'group' => 'contacts',
            ],
        ];
        switch ($event) {
            case 'leads':
                $expected['leads']['columns']['l.stage_id'] = [
                    'label' => '',
                    'type'  => 'int',
                    'alias' => 'stage_id',
                ];
                $expected['leads']['columns']['ss.name'] = [
                    'alias' => 'stage_name',
                    'label' => '',
                    'type'  => 'string',
                ];
                $expected['leads']['columns']['ss.date_added'] = [
                    'alias'   => 'stage_date_added',
                    'label'   => null,
                    'type'    => 'string',
                    'formula' => sprintf('(SELECT MAX(stage_log.date_added) FROM %slead_stages_change_log stage_log WHERE stage_log.stage_id = l.stage_id AND stage_log.lead_id = l.id)', MAUTIC_TABLE_PREFIX),
                ];
                break;
            case 'contact.frequencyrules':
                $expected['contact.frequencyrules'] = [
                    'display_name' => 'mautic.lead.report.frequency.messages',
                    'columns'      => [
                        'xx.yy' => [
                            'label' => '',
                            'type'  => 'bool',
                            'alias' => 'first',
                        ],
                        'comp.name' => [
                            'label' => '',
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                        'lf.frequency_number' => [
                            'label' => '',
                            'type'  => 'int',
                            'alias' => 'frequency_number',
                        ],
                        'lf.frequency_time' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'frequency_time',
                        ],
                        'lf.channel' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'channel',
                        ],
                        'lf.preferred_channel' => [
                            'label' => '',
                            'type'  => 'boolean',
                            'alias' => 'preferred_channel',
                        ],
                        'lf.pause_from_date' => [
                            'label' => '',
                            'type'  => 'datetime',
                            'alias' => 'pause_from_date',
                        ],
                        'lf.pause_to_date' => [
                            'label' => '',
                            'type'  => 'datetime',
                            'alias' => 'pause_to_date',
                        ],
                        'lf.date_added' => [
                            'label'          => null,
                            'type'           => 'datetime',
                            'groupByFormula' => 'DATE(lf.date_added)',
                            'alias'          => 'date_added',
                        ],
                    ],
                    'filters' => [
                        'filter' => [
                            'label' => '',
                            'type'  => 'text',
                            'alias' => 'filter',
                        ],
                        'comp.name' => [
                            'label' => '',
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                        'lf.frequency_number' => [
                            'label' => '',
                            'type'  => 'int',
                            'alias' => 'frequency_number',
                        ],
                        'lf.frequency_time' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'frequency_time',
                        ],
                        'lf.channel' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'channel',
                        ],
                        'lf.preferred_channel' => [
                            'label' => '',
                            'type'  => 'boolean',
                            'alias' => 'preferred_channel',
                        ],
                        'lf.pause_from_date' => [
                            'label' => '',
                            'type'  => 'datetime',
                            'alias' => 'pause_from_date',
                        ],
                        'lf.pause_to_date' => [
                            'label' => '',
                            'type'  => 'datetime',
                            'alias' => 'pause_to_date',
                        ],
                        'lf.date_added' => [
                            'label'          => null,
                            'type'           => 'datetime',
                            'groupByFormula' => 'DATE(lf.date_added)',
                            'alias'          => 'date_added',
                        ],
                    ],
                    'group' => 'contacts',
                ];
                break;
            case 'lead.pointlog':
                $expected['lead.pointlog'] = [
                    'display_name' => 'mautic.lead.report.points.table',
                    'columns'      => [
                        'xx.yy' => [
                            'label' => '',
                            'type'  => 'bool',
                            'alias' => 'first',
                        ],
                        'comp.name' => [
                            'label' => '',
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                        'lp.id' => [
                            'label' => '',
                            'type'  => 'int',
                            'alias' => 'id',
                        ],
                        'lp.type' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'type',
                        ],
                        'lp.event_name' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'event_name',
                        ],
                        'lp.action_name' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'action_name',
                        ],
                        'lp.delta' => [
                            'label' => '',
                            'type'  => 'int',
                            'alias' => 'delta',
                        ],
                        'lp.date_added' => [
                            'label'          => null,
                            'type'           => 'datetime',
                            'groupByFormula' => 'DATE(lp.date_added)',
                            'alias'          => 'date_added',
                        ],
                        'pl.id' => [
                            'alias' => 'group_id',
                            'label' => '',
                            'type'  => 'int',
                        ],
                        'pl.name' => [
                            'alias' => 'group_name',
                            'label' => '',
                            'type'  => 'string',
                        ],
                        'i.ip_address' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'ip_address',
                        ],
                    ],
                    'filters' => [
                        'filter' => [
                            'label' => '',
                            'type'  => 'text',
                            'alias' => 'filter',
                        ],
                        'comp.name' => [
                            'label' => '',
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                        'lp.id' => [
                            'label' => '',
                            'type'  => 'int',
                            'alias' => 'id',
                        ],
                        'lp.type' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'type',
                        ],
                        'lp.event_name' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'event_name',
                        ],
                        'lp.action_name' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'action_name',
                        ],
                        'lp.delta' => [
                            'label' => '',
                            'type'  => 'int',
                            'alias' => 'delta',
                        ],
                        'lp.date_added' => [
                            'label'          => null,
                            'type'           => 'datetime',
                            'groupByFormula' => 'DATE(lp.date_added)',
                            'alias'          => 'date_added',
                        ],
                        'pl.id' => [
                            'alias' => 'group_id',
                            'label' => '',
                            'type'  => 'int',
                        ],
                        'pl.name' => [
                            'alias' => 'group_name',
                            'label' => '',
                            'type'  => 'string',
                        ],
                    ],
                    'group' => 'contacts',
                ];
                break;
            case 'contact.attribution.first':
            case 'contact.attribution.last':
            case 'contact.attribution.multi':
                $displayName      = 'mautic.lead.report.attribution.'.explode('.', $event)[2];
                $expected[$event] = [
                    'display_name' => $displayName,
                    'columns'      => [
                        'xx.yy' => [
                            'label' => '',
                            'type'  => 'bool',
                            'alias' => 'first',
                        ],
                        'comp.name' => [
                            'label' => '',
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                        'cat.id' => [
                            'label' => '',
                            'type'  => 'int',
                            'alias' => 'category_id',
                        ],
                        'cat.title' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'category_title',
                        ],
                        'log.campaign_id' => [
                            'label' => '',
                            'type'  => 'int',
                            'link'  => 'mautic_campaign_action',
                            'alias' => 'campaign_id',
                        ],
                        'log.date_triggered' => [
                            'label'          => '',
                            'type'           => 'datetime',
                            'groupByFormula' => 'DATE(log.date_triggered)',
                            'alias'          => 'date_triggered',
                        ],
                        'c.name' => [
                            'alias' => 'campaign_name',
                            'label' => '',
                            'type'  => 'string',
                        ],
                        'l.stage_id' => [
                            'label' => '',
                            'type'  => 'int',
                            'alias' => 'stage_id',
                        ],
                        'ss.name' => [
                            'alias' => 'stage_name',
                            'label' => '',
                            'type'  => 'string',
                        ],
                        'channel' => [
                            'alias'   => 'channel',
                            'formula' => 'SUBSTRING_INDEX(e.type, \'.\', 1)',
                            'label'   => '',
                            'type'    => 'string',
                        ],
                        'channel_action' => [
                            'alias'   => 'channel_action',
                            'formula' => 'SUBSTRING_INDEX(e.type, \'.\', -1)',
                            'label'   => '',
                            'type'    => 'string',
                        ],
                        'e.name' => [
                            'alias' => 'action_name',
                            'label' => '',
                            'type'  => 'string',
                        ],
                    ],
                    'filters' => [
                        'filter' => [
                            'label' => '',
                            'type'  => 'text',
                            'alias' => 'filter',
                        ],
                        'comp.name' => [
                            'label' => '',
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                        'cat.id' => [
                            'label' => '',
                            'type'  => 'int',
                            'alias' => 'category_id',
                        ],
                        'cat.title' => [
                            'label' => '',
                            'type'  => 'string',
                            'alias' => 'category_title',
                        ],
                        'log.campaign_id' => [
                            'label' => '',
                            'type'  => 'select',
                            'list'  => [],
                            'alias' => 'campaign_id',
                        ],
                        'log.date_triggered' => [
                            'label'          => null,
                            'type'           => 'datetime',
                            'groupByFormula' => 'DATE(log.date_triggered)',
                            'alias'          => 'date_triggered',
                        ],
                        'c.name' => [
                            'alias' => 'campaign_name',
                            'label' => '',
                            'type'  => 'string',
                        ],
                        'l.stage_id' => [
                            'label' => '',
                            'type'  => 'select',
                            'list'  => [
                                1 => 'Stage One',
                            ],
                            'alias' => 'stage_id',
                        ],
                        'ss.name' => [
                            'alias' => 'stage_name',
                            'label' => '',
                            'type'  => 'string',
                        ],
                        'channel' => [
                            'label' => '',
                            'type'  => 'select',
                            'list'  => [
                                'email' => 'Email',
                            ],
                            'alias' => 'channel',
                        ],
                        'channel_action' => [
                            'label' => '',
                            'type'  => 'select',
                            'list'  => [
                                'click' => 'email: click',
                            ],
                            'alias' => 'channel_action',
                        ],
                        'e.name' => [
                            'alias' => 'action_name',
                            'label' => '',
                            'type'  => 'string',
                        ],
                    ],
                    'group' => 'contacts',
                ];

                break;
            case 'companies':
                unset($expected['leads']);
                $expected['companies'] = [
                    'display_name' => 'mautic.lead.lead.companies',
                    'columns'      => [
                        'comp.name' => [
                            'label' => '',
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                    ],
                    'filters' => [
                        'comp.name' => [
                            'label' => '',
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                    ],
                    'group' => 'companies',
                ];
                break;
        }

        $this->assertEquals($expected, $reportBuilderEvent->getTables());
    }

    /**
     * @dataProvider eventDataProvider
     */
    public function testReportGenerate(string $context): void
    {
        $this->reportGeneratorEventMock->method('checkContext')
            ->withConsecutive(
                [
                    [
                        'leads',
                        'lead.pointlog',
                        'contact.attribution.multi',
                        'contact.attribution.first',
                        'contact.attribution.last',
                        'contact.frequencyrules',
                    ],
                ]
            )->willReturn(true);

        $this->reportGeneratorEventMock->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $this->reportGeneratorEventMock->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilderMock);

        $this->reportSubscriber->onReportGenerate($this->reportGeneratorEventMock);
    }

    /**
     * @dataProvider ReportGraphEventDataProvider
     */
    public function testonReportGraphGenerate(string $event): void
    {
        $this->reportGraphEventMock->expects($this->once())
            ->method('checkContext')
            ->willReturn($event);

        $this->reportGraphEventMock->expects($this->once())
            ->method('getRequestedGraphs')
            ->willReturn([
                'mautic.lead.graph.line.leads',
                'mautic.lead.table.top.actions',
                'mautic.lead.table.top.cities',
                'mautic.lead.table.top.countries',
                'mautic.lead.table.top.events',
                'mautic.lead.graph.line.points',
                'mautic.lead.table.most.points',
            ]);

        $this->leadModelMock->expects($this->once())
            ->method('getPointLogRepository')
            ->willReturn($this->pointsChangeLogRepositoryMock);

        $this->companyModelMock->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->companyRepositoryMock);

        $mockStmt = $this->getMockBuilder(Result::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchAllAssociative'])
            ->getMock();

        $this->reportGraphEventMock->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilderMock);

        $mockChartQuery = $this->getMockBuilder(ChartQuery::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'modifyCountQuery',
                'modifyTimeDataQuery',
                'loadAndBuildTimeData',
                'fetchCount',
                'fetchCountDateDiff',
            ])
            ->getMock();

        $mockChartQuery->expects($this->any())
            ->method('loadAndBuildTimeData')
            ->willReturn(['a', 'b', 'c']);

        $mockChartQuery->expects($this->any())
            ->method('fetchCount')
            ->willReturn(2);

        $mockChartQuery->expects($this->any())
            ->method('fetchCountDateDiff')
            ->willReturn(2);

        $graphOptions = [
            'chartQuery' => $mockChartQuery,
            'translator' => $this->translatorMock,
            'dateFrom'   => new \DateTime(),
            'dateTo'     => new \DateTime(),
        ];

        $this->reportGraphEventMock->expects($this->any())
            ->method('getOptions')
            ->willReturn($graphOptions);

        $this->reportGraphEventMock->expects($this->any())
            ->method('getOptions')
            ->willReturn($graphOptions);

        $this->reportSubscriber->onReportGraphGenerate($this->reportGraphEventMock);
    }

    /**
     * @dataProvider ReportGraphEventDataProvider
     */
    public function testOnReportDisplay(string $event): void
    {
        $this->reportBuilderEventMock->expects($this->any())
        ->method('checkContext')
        ->willReturn($event);

        $this->fieldsBuilderMock->expects($this->any())
    ->method('getLeadFieldsColumns')
    ->with('l.')
    ->willReturn($this->leadColumns);

        $this->fieldsBuilderMock->expects($this->any())
        ->method('getLeadFilter')
        ->with('l.', 's.')
        ->willReturn($this->leadFilters);

        $this->companyReportDataMock->expects($this->any())
    ->method('getCompanyData')
    ->willReturn($this->companyColumns);

        $this->reportBuilderEventMock->expects($this->any())
        ->method('getCategoryColumns')
        ->willReturn([
            'c.id' => [
                'label' => 'mautic.report.field.category_id',
                'type'  => 'int',
                'alias' => 'category_id',
            ],
            'c.title' => [
                'label' => 'mautic.report.field.category_name',
                'type'  => 'string',
                'alias' => 'category_title',
            ],
        ]);
        $this->reportBuilderEventMock->expects($this->any())
        ->method('getIpColumn')
        ->willReturn(
            [
                'i.ip_address' => [
                    'label' => 'mautic.core.ipaddress',
                    'type'  => 'string',
                ],
            ]
        );
        $this->reportBuilderEventMock->expects($this->any())
        ->method('addGraph')
        ->willReturn($this->reportBuilderEventMock);

        $this->reportSubscriber->onReportBuilder($this->reportBuilderEventMock);

        $this->reportDataEventMock->expects($this->once())
            ->method('checkContext')
            ->willReturn($event);
        $this->reportDataEventMock->expects($this->once())
            ->method('getData')
            ->willReturn([[
                'channel'        => 'email',
                'channel_action' => 'click',
                'activity_count' => 10,
            ]]);
        $this->reportSubscriber->onReportBuilder($this->reportBuilderEventMock);
        $this->reportSubscriber->onReportDisplay($this->reportDataEventMock);
    }

    public function testOnReportColumnCollectForCompany(): void
    {
        $companyFields  = [
            'comp.id'   => [
                'alias' => 'comp_id',
                'label' => 'mautic.lead.report.company.company_id',
                'type'  => 'int',
                'link'  => 'mautic_company_action',
            ],
            'companies_lead.is_primary' => [
                'label' => 'mautic.lead.report.company.is_primary',
                'type'  => 'bool',
            ],
            'companies_lead.date_added' => [
                'label' => 'mautic.lead.report.company.date_added',
                'type'  => 'datetime',
            ],
        ];

        $columns        = [
            'comp.id'   => [
                'alias' => 'comp_id',
                'label' => 'mautic.lead.report.company.company_id',
                'type'  => 'int',
                'link'  => 'mautic_company_action',
            ],
        ];

        $columnCollectEvent = new ColumnCollectEvent('company');

        $this->companyReportDataMock->expects($this->once())
            ->method('getCompanyData')
            ->willReturn($companyFields);

        $this->reportSubscriber->onReportColumnCollect($columnCollectEvent);

        Assert::assertSame($columns, $columnCollectEvent->getColumns());
    }

    public function testOnReportColumnCollectForContact(): void
    {
        $publishedFields = [
            [
                'label'  => 'Email',
                'type'   => 'string',
                'alias'  => 'email',
            ],
            [
                'label'  => 'Firstname',
                'type'   => 'string',
                'alias'  => 'firstname',
            ],
        ];

        $columns          = [
            'l.email'     => [
                'label'   => '',
                'type'    => 'string',
                'alias'   => 'email',
            ],
            'l.firstname' => [
                'label'   => '',
                'type'    => 'string',
                'alias'   => 'firstname',
            ],
            'l.id'        => [
                'label'   => 'mautic.lead.report.contact_id',
                'type'    => 'int',
                'link'    => 'mautic_contact_action',
                'alias'   => 'contactId',
            ],
        ];

        $columnCollectEvent = new ColumnCollectEvent('contact');

        $this->leadFieldModelMock->expects($this->once())
            ->method('getPublishedFieldArrays')
            ->willReturn($publishedFields);

        $this->reportSubscriber->onReportColumnCollect($columnCollectEvent);

        Assert::assertSame($columns, $columnCollectEvent->getColumns());
    }
}
