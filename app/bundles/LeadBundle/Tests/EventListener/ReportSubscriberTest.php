<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Query\Expression\ExpressionBuilder;
use Doctrine\DBAL\Query\QueryBuilder;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\ChannelBundle\Helper\ChannelListHelper;
use Mautic\CoreBundle\Helper\Chart\ChartQuery;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Form\Type\EmailClickDecisionType;
use Mautic\EmailBundle\Form\Type\EmailSendType;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\PointsChangeLogRepository;
use Mautic\LeadBundle\EventListener\ReportSubscriber;
use Mautic\LeadBundle\Model\CompanyReportData;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Report\DncReportService;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ColumnCollectEvent;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\StageBundle\Model\StageModel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[\PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations]
final class ReportSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject&LeadModel
     */
    private MockObject $leadModelMock;

    /**
     * @var MockObject&FieldModel
     */
    private MockObject $leadFieldModelMock;

    /**
     * @var MockObject&CompanyReportData
     */
    private MockObject $companyReportDataMock;

    /**
     * @var MockObject&FieldsBuilder
     */
    private MockObject $fieldsBuilderMock;

    /**
     * @var MockObject&Translator
     */
    private MockObject $translatorMock;

    /**
     * @var MockObject&ReportGeneratorEvent
     */
    private MockObject $reportGeneratorEventMock;

    private ChannelListHelper $channelListHelperMock;

    private ReportHelper $reportHelperMock;

    /**
     * @var MockObject&ReportBuilderEvent
     */
    private MockObject $reportBuilderEventMock;

    /**
     * @var MockObject&QueryBuilder
     */
    private MockObject $queryBuilderMock;

    /**
     * @var MockObject&ReportGraphEvent
     */
    private MockObject $reportGraphEventMock;

    /**
     * @var MockObject&ReportDataEvent
     */
    private MockObject $reportDataEventMock;

    private ReportSubscriber $reportSubscriber;

    /**
     * @var array<string, array<string, string>>
     */
    private array $leadColumns = [
        'xx.yy' => [
            'label' => '',
            'type'  => 'bool',
            'alias' => 'first',
        ],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private array $leadFilters = [
        'filter' => [
            'label' => 'second',
            'type'  => 'text',
        ],
    ];

    /**
     * @var array<string, array<string, string>>
     */
    private array $companyColumns = [
        'comp.name' => [
            'label' => 'company_name',
            'type'  => 'text',
        ],
    ];

    protected function setUp(): void
    {
        $this->leadModelMock                    = $this->createMock(LeadModel::class);
        $this->leadFieldModelMock               = $this->createMock(FieldModel::class);
        $stageModelMock                         = $this->createMock(StageModel::class);
        $eventCollectorMock                     = $this->createMock(EventCollector::class);
        $this->companyReportDataMock            = $this->createMock(CompanyReportData::class);
        $this->fieldsBuilderMock                = $this->createMock(FieldsBuilder::class);
        $this->translatorMock                   = $this->createMock(Translator::class);
        $this->reportGeneratorEventMock         = $this->createMock(ReportGeneratorEvent::class);
        $this->reportDataEventMock              = $this->createMock(ReportDataEvent::class);
        $this->channelListHelperMock            = new ChannelListHelper($this->createStub(EventDispatcherInterface::class), $this->createStub(Translator::class));
        $this->reportHelperMock                 = new ReportHelper($this->createStub(EventDispatcherInterface::class));
        $this->reportBuilderEventMock           = $this->createMock(ReportBuilderEvent::class);
        $this->queryBuilderMock                 = $this->createMock(QueryBuilder::class);
        $this->reportGraphEventMock             = $this->createMock(ReportGraphEvent::class);
        $this->reportSubscriber                 = new ReportSubscriber(
            $this->leadModelMock,
            $this->leadFieldModelMock,
            $stageModelMock,
            $eventCollectorMock,
            $this->companyReportDataMock,
            $this->fieldsBuilderMock,
            $this->translatorMock,
            $this->createStub(DncReportService::class),
            $this->createStub(CompanyRepository::class),
            $this->createStub(CampaignRepository::class)
        );

        $this->queryBuilderMock
                ->method('expr')
                ->willReturn($this->createStub(ExpressionBuilder::class));

        $this->queryBuilderMock
            ->method('resetQueryParts')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock
            ->method('getQueryPart')
            ->willReturnCallback(function ($input): array|string {
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

        $this->queryBuilderMock
            ->method('from')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock
            ->method('leftJoin')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock
            ->method('join')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock
            ->method('select')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock
            ->method('setParameters')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock
            ->method('getParameters')
            ->willReturn([]);

        $this->queryBuilderMock
            ->method('setMaxResults')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->method('andWhere')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock
            ->method('groupBy')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock
            ->method('orderBy')
            ->willReturn($this->queryBuilderMock);

        $eventCollectorMock
            ->method('getEventsArray')
            ->willReturn(
                [
                    'action' => [
                        'email.send' => [
                            'label'           => 'Send email',
                            'description'     => 'Send the selected email to the contact.',
                            'batchEventName'  => 'mautic.email.on_campaign_batch_action',
                            'formType'        => EmailSendType::class,
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
                            'formType'               => EmailClickDecisionType::class,
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

        $this->translatorMock
            ->method('hasId')
            ->willReturn(false);

        $stageModelMock
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
     * @return \Iterator<int, array<int, string>>
     */
    public static function eventDataProvider(): \Iterator
    {
        yield ['leads'];
        yield ['contact.frequencyrules'];
        yield ['lead.pointlog'];
        yield ['contact.attribution.first'];
        yield ['contact.attribution.multi'];
        yield ['contact.attribution.last'];
        yield ['companies'];
    }

    /**
     * @return \Iterator<int, array<int, string>>
     */
    public static function reportGraphEventDataProvider(): \Iterator
    {
        yield ['leads'];
        yield ['lead.pointlog'];
        yield ['contact.attribution.multi'];
        yield ['companies'];
    }

    public function testNotRelevantContextBuilder(): void
    {
        $matcher = new AnyInvokedCount();
        $this->reportBuilderEventMock->expects($matcher)->method('checkContext')
            ->willReturnCallback(
                function (...$parameters) use ($matcher): false {
                    if (1 === $matcher->numberOfInvocations()) {
                        $this->assertSame([
                            'leads',
                            'lead.pointlog',
                            'contact.attribution.multi',
                            'contact.attribution.first',
                            'contact.attribution.last',
                            'contact.frequencyrules',
                        ], $parameters[0]);
                    }

                    return false;
                }
            );

        $this->reportBuilderEventMock->expects($this->never())
            ->method('addTable');

        $this->reportSubscriber->onReportBuilder($this->reportBuilderEventMock);
    }

    public function testNotRelevantContextGenerate(): void
    {
        $matcher = $this->exactly(2);
        $this->reportGeneratorEventMock->expects($matcher)->method('checkContext')->willReturnCallback(function (...$parameters) use ($matcher): false {
            if (1 === $matcher->numberOfInvocations()) {
                $this->assertSame([
                    'leads',
                    'lead.pointlog',
                    'contact.attribution.multi',
                    'contact.attribution.first',
                    'contact.attribution.last',
                    'contact.frequencyrules',
                ], $parameters[0]);
            }
            if (2 === $matcher->numberOfInvocations()) {
                $this->assertSame(['companies'], $parameters[0]);
            }

            return false;
        });

        $this->reportGeneratorEventMock->expects($this->never())
            ->method('getQueryBuilder');

        $this->reportSubscriber->onReportGenerate($this->reportGeneratorEventMock);
    }

    #[DataProvider('eventDataProvider')]
    public function testOnReportBuilder(string $event): void
    {
        if ('companies' !== $event) {
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

    #[DataProvider('eventDataProvider')]
    public function testReportGenerate(string $context): void
    {
        $matcher = new AnyInvokedCount();
        $this->reportGeneratorEventMock->expects($matcher)->method('checkContext')
            ->willReturnCallback(
                function (...$parameters) use ($matcher): true {
                    if (1 === $matcher->numberOfInvocations()) {
                        $this->assertSame([
                            'leads',
                            'lead.pointlog',
                            'contact.attribution.multi',
                            'contact.attribution.first',
                            'contact.attribution.last',
                            'contact.frequencyrules',
                        ], $parameters[0]);
                    }

                    return true;
                }
            );

        $this->reportGeneratorEventMock->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $this->reportGeneratorEventMock->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilderMock);

        $this->reportSubscriber->onReportGenerate($this->reportGeneratorEventMock);
    }

    #[DataProvider('ReportGraphEventDataProvider')]
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
            ->willReturn($this->createStub(PointsChangeLogRepository::class));

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

        $mockChartQuery
            ->method('loadAndBuildTimeData')
            ->willReturn(['a', 'b', 'c']);

        $mockChartQuery
            ->method('fetchCount')
            ->willReturn(2);

        $mockChartQuery
            ->method('fetchCountDateDiff')
            ->willReturn(2);

        $graphOptions = [
            'chartQuery' => $mockChartQuery,
            'translator' => $this->translatorMock,
            'dateFrom'   => new \DateTime(),
            'dateTo'     => new \DateTime(),
        ];

        $this->reportGraphEventMock
            ->method('getOptions')
            ->willReturn($graphOptions);

        $this->reportGraphEventMock
            ->method('getOptions')
            ->willReturn($graphOptions);

        $this->reportSubscriber->onReportGraphGenerate($this->reportGraphEventMock);
    }

    #[DataProvider('ReportGraphEventDataProvider')]
    public function testOnReportDisplay(string $event): void
    {
        $this->reportBuilderEventMock
        ->method('checkContext')
        ->willReturn($event);

        $this->fieldsBuilderMock
    ->method('getLeadFieldsColumns')
    ->willReturn($this->leadColumns);

        $this->fieldsBuilderMock
        ->method('getLeadFilter')
        ->willReturn($this->leadFilters);

        $this->companyReportDataMock
    ->method('getCompanyData')
    ->willReturn($this->companyColumns);

        $this->reportBuilderEventMock
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
        $this->reportBuilderEventMock
        ->method('getIpColumn')
        ->willReturn(
            [
                'i.ip_address' => [
                    'label' => 'mautic.core.ipaddress',
                    'type'  => 'string',
                ],
            ]
        );
        $this->reportBuilderEventMock
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

        $this->assertSame($columns, $columnCollectEvent->getColumns());
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

        $this->assertSame($columns, $columnCollectEvent->getColumns());
    }
}
