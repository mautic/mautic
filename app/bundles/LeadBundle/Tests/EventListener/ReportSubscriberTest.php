<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\EventListener;

use Doctrine\DBAL\Driver\PDOStatement;
use Doctrine\DBAL\Query\QueryBuilder;
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
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\LeadBundle\Segment\Query\Expression\ExpressionBuilder;
use Mautic\ReportBundle\Entity\Report;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportDataEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\Event\ReportGraphEvent;
use Mautic\ReportBundle\Helper\ReportHelper;
use Mautic\StageBundle\Model\StageModel;
use PHPUnit\Framework\MockObject\MockObject;

class ReportSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|LeadModel
     */
    private $leadModelMock;

    /**
     * @var MockObject|StageModel
     */
    private $stageModelMock;

    /**
     * @var MockObject|CampaignModel
     */
    private $campaignModelMock;

    /**
     * @var MockObject|EventCollector
     */
    private $eventCollectorMock;

    /**
     * @var MockObject|CompanyModel
     */
    private $companyModelMock;

    /**
     * @var MockObject|CompanyReportData
     */
    private $companyReportDataMock;

    /**
     * @var MockObject|FieldsBuilder
     */
    private $fieldsBuilderMock;

    /**
     * @var MockObject|Translator
     */
    private $translatorMock;

    /**
     * @var MockObject|ReportGeneratorEvent
     */
    private $reportGeneratorEventMock;

    /**
     * @var MockObject|ChannelListHelper
     */
    private $channelListHelperMock;

    /**
     * @var MockObject|ReportHelper
     */
    private $reportHelperMock;

    /**
     * @var MockObject|CampaignRepository
     */
    private $campaignRepositoryMock;

    /**
     * @var MockObject|ReportBuilderEvent
     */
    private $reportBuilderEventMock;

    /**
     * @var MockObject|QueryBuilder
     */
    private $queryBuilderMock;

    /**
     * @var MockObject|ExpressionBuilder
     */
    private $expressionBuilderMock;

    /**
     * @var MockObject|ReportGraphEvent
     */
    private $reportGraphEventMock;

    /**
     * @var MockObject|CompanyRepository
     */
    private $companyRepositoryMock;

    /**
     * @var MockObject|PointsChangeLogRepository
     */
    private $pointsChangeLogRepositoryMock;

    /**
     * @var MockObject|ReportMock
     */
    private $reportMock;

    /**
     * @var MockObject|ReportDataEventMock
     */
    private $reportDataEventMock;

    /**
     * @var ReportSubscriber
     */
    private $reportSubscriber;

    /**
     * @var array
     */
    private $leadColumns = [
        'xx.yy' => [
            'label' => null,
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
        if (!defined('MAUTIC_TABLE_PREFIX')) {
            define('MAUTIC_TABLE_PREFIX', '');
        }

        $this->leadModelMock                    = $this->createMock(LeadModel::class);
        $this->stageModelMock                   = $this->createMock(StageModel::class);
        $this->campaignModelMock                = $this->createMock(CampaignModel::class);
        $this->eventCollectorMock               = $this->createMock(EventCollector::class);
        $this->companyModelMock                 = $this->createMock(CompanyModel::class);
        $this->companyReportDataMock            = $this->createMock(CompanyReportData::class);
        $this->fieldsBuilderMock                = $this->createMock(FieldsBuilder::class);
        $this->translatorMock                   = $this->createMock(Translator::class);
        $this->reportGeneratorEventMock         = $this->createMock(ReportGeneratorEvent::class);
        $this->reportDataEventMock              = $this->createMock(ReportDataEvent::class);
        $this->channelListHelperMock            = $this->createMock(ChannelListHelper::class);
        $this->reportHelperMock                 = $this->createMock(ReportHelper::class);
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
            $this->stageModelMock,
            $this->campaignModelMock,
            $this->eventCollectorMock,
            $this->companyModelMock,
            $this->companyReportDataMock,
            $this->fieldsBuilderMock,
            $this->translatorMock
        );

        $this->expressionBuilderMock->expects($this->any())
            ->method('andX')
            ->willReturn($this->expressionBuilderMock);

        $this->queryBuilderMock->expects($this->any())
                ->method('expr')
                ->willReturn($this->expressionBuilderMock);

        $this->queryBuilderMock->expects($this->any())
            ->method('resetQueryParts')
            ->willReturn($this->queryBuilderMock);

        $this->queryBuilderMock->expects($this->any())
            ->method('getQueryPart')
            ->willReturn([['alias' => 'lp']]);

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
                            'formType'        => "Mautic\EmailBundle\Form\Type\EmailSendType",
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
                              'description'            => 'Trigger actions when an email is clicked. Connect a &quot;Send Email&quot; action to the top of this decision.',
                              'eventName'              => 'mautic.email.on_campaign_trigger_decision',
                              'formType'               => "Mautic\EmailBundle\Form\Type\EmailClickDecisionType",
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

    public function eventDataProvider()
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

    public function reportGraphEventDataProvider()
    {
        return [
            ['leads'],
            ['lead.pointlog'],
            ['contact.attribution.multi'],
            ['companies'],
        ];
    }

    public function testNotRelevantContextBuilder()
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

    public function testNotRelevantContextGenerate()
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
    public function testOnReportBuilder($event)
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
                        'label' => null,
                        'type'  => 'bool',
                        'alias' => 'first',
                    ],
                    'comp.name' => [
                        'label' => null,
                        'type'  => 'text',
                        'alias' => 'name',
                    ],
                ],
                'filters' => [
                    'filter' => [
                        'label' => null,
                        'type'  => 'text',
                        'alias' => 'filter',
                    ],
                    'comp.name' => [
                        'label' => null,
                        'type'  => 'text',
                        'alias' => 'name',
                    ],
                    ],
                'group' => 'contacts',
            ],
        ];
        switch ($event) {
            case 'leads':
                break;
            case 'contact.frequencyrules':
                $expected['contact.frequencyrules'] = [
                    'display_name' => 'mautic.lead.report.frequency.messages',
                    'columns'      => [
                        'xx.yy' => [
                            'label' => null,
                            'type'  => 'bool',
                            'alias' => 'first',
                        ],
                        'comp.name' => [
                            'label' => null,
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                        'lf.frequency_number' => [
                            'label' => null,
                            'type'  => 'int',
                            'alias' => 'frequency_number',
                        ],
                        'lf.frequency_time' => [
                            'label' => null,
                            'type'  => 'string',
                            'alias' => 'frequency_time',
                        ],
                        'lf.channel' => [
                            'label' => null,
                            'type'  => 'string',
                            'alias' => 'channel',
                        ],
                        'lf.preferred_channel' => [
                            'label' => null,
                            'type'  => 'boolean',
                            'alias' => 'preferred_channel',
                        ],
                        'lf.pause_from_date' => [
                            'label' => null,
                            'type'  => 'datetime',
                            'alias' => 'pause_from_date',
                        ],
                        'lf.pause_to_date' => [
                            'label' => null,
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
                            'label' => null,
                            'type'  => 'text',
                            'alias' => 'filter',
                        ],
                        'comp.name' => [
                            'label' => null,
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                        'lf.frequency_number' => [
                            'label' => null,
                            'type'  => 'int',
                            'alias' => 'frequency_number',
                        ],
                        'lf.frequency_time' => [
                            'label' => null,
                            'type'  => 'string',
                            'alias' => 'frequency_time',
                        ],
                        'lf.channel' => [
                            'label' => null,
                            'type'  => 'string',
                            'alias' => 'channel',
                        ],
                        'lf.preferred_channel' => [
                            'label' => null,
                            'type'  => 'boolean',
                            'alias' => 'preferred_channel',
                        ],
                        'lf.pause_from_date' => [
                            'label' => null,
                            'type'  => 'datetime',
                            'alias' => 'pause_from_date',
                        ],
                        'lf.pause_to_date' => [
                            'label' => null,
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
                            'label' => null,
                            'type'  => 'bool',
                            'alias' => 'first',
                        ],
                        'comp.name' => [
                            'label' => null,
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                        'lp.id' => [
                            'label' => null,
                            'type'  => 'int',
                            'alias' => 'id',
                        ],
                        'lp.type' => [
                            'label' => null,
                            'type'  => 'string',
                            'alias' => 'type',
                        ],
                        'lp.event_name' => [
                            'label' => null,
                            'type'  => 'string',
                            'alias' => 'event_name',
                        ],
                        'lp.action_name' => [
                            'label' => null,
                            'type'  => 'string',
                            'alias' => 'action_name',
                        ],
                        'lp.delta' => [
                            'label' => null,
                            'type'  => 'int',
                            'alias' => 'delta',
                        ],
                        'lp.date_added' => [
                            'label'          => null,
                            'type'           => 'datetime',
                            'groupByFormula' => 'DATE(lp.date_added)',
                            'alias'          => 'date_added',
                        ],
                        'i.ip_address' => [
                            'label' => null,
                            'type'  => 'string',
                            'alias' => 'ip_address',
                        ],
                    ],
                    'filters' => [
                        'filter' => [
                            'label' => null,
                            'type'  => 'text',
                            'alias' => 'filter',
                        ],
                        'comp.name' => [
                            'label' => null,
                            'type'  => 'text',
                            'alias' => 'name',
                        ],
                        'lp.id' => [
                            'label' => null,
                            'type'  => 'int',
                            'alias' => 'id',
                        ],
                        'lp.type' => [
                            'label' => null,
                            'type'  => 'string',
                            'alias' => 'type',
                        ],
                        'lp.event_name' => [
                            'label' => null,
                            'type'  => 'string',
                            'alias' => 'event_name',
                        ],
                        'lp.action_name' => [
                            'label' => null,
                            'type'  => 'string',
                            'alias' => 'action_name',
                        ],
                        'lp.delta' => [
                            'label' => null,
                            'type'  => 'int',
                            'alias' => 'delta',
                        ],
                        'lp.date_added' => [
                            'label'          => null,
                            'type'           => 'datetime',
                            'groupByFormula' => 'DATE(lp.date_added)',
                            'alias'          => 'date_added',
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
                                'label' => null,
                                'type'  => 'bool',
                                'alias' => 'first',
                            ],
                            'comp.name' => [
                                'label' => null,
                                'type'  => 'text',
                                'alias' => 'name',
                            ],
                            'cat.id' => [
                                'label' => null,
                                'type'  => 'int',
                                'alias' => 'category_id',
                            ],
                            'cat.title' => [
                                'label' => null,
                                'type'  => 'string',
                                'alias' => 'category_title',
                            ],
                            'log.campaign_id' => [
                                'label' => null,
                                'type'  => 'int',
                                'link'  => 'mautic_campaign_action',
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
                                'label' => null,
                                'type'  => 'string',
                            ],
                            'l.stage_id' => [
                                'label' => null,
                                'type'  => 'int',
                                'link'  => 'mautic_stage_action',
                                'alias' => 'stage_id',
                            ],
                            's.name' => [
                                'alias' => 'stage_name',
                                'label' => null,
                                'type'  => 'string',
                            ],
                            'channel' => [
                                'alias'   => 'channel',
                                'formula' => 'SUBSTRING_INDEX(e.type, \'.\', 1)',
                                'label'   => null,
                                'type'    => 'string',
                            ],
                            'channel_action' => [
                                'alias'   => 'channel_action',
                                'formula' => 'SUBSTRING_INDEX(e.type, \'.\', -1)',
                                'label'   => null,
                                'type'    => 'string',
                            ],
                            'e.name' => [
                                'alias' => 'action_name',
                                'label' => null,
                                'type'  => 'string',
                            ],
                        ],
                        'filters' => [
                            'filter' => [
                                'label' => null,
                                'type'  => 'text',
                                'alias' => 'filter',
                            ],
                            'comp.name' => [
                                'label' => null,
                                'type'  => 'text',
                                'alias' => 'name',
                            ],
                            'cat.id' => [
                                'label' => null,
                                'type'  => 'int',
                                'alias' => 'category_id',
                            ],
                            'cat.title' => [
                                'label' => null,
                                'type'  => 'string',
                                'alias' => 'category_title',
                            ],
                            'log.campaign_id' => [
                                'label' => null,
                                'type'  => 'select',
                                'list'  => null,
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
                                'label' => null,
                                'type'  => 'string',
                            ],
                            'l.stage_id' => [
                                'label' => null,
                                'type'  => 'select',
                                'list'  => [
                                    1 => 'Stage One',
                                ],
                                'alias' => 'stage_id',
                            ],
                            's.name' => [
                                'alias' => 'stage_name',
                                'label' => null,
                                'type'  => 'string',
                            ],
                            'channel' => [
                                'label' => null,
                                'type'  => 'select',
                                'list'  => [
                                    'email' => 'Email',
                                ],
                                'alias' => 'channel',
                            ],
                            'channel_action' => [
                                'label' => null,
                                'type'  => 'select',
                                'list'  => [
                                    'click' => 'email: click',
                                ],
                                'alias' => 'channel_action',
                            ],
                            'e.name' => [
                                'alias' => 'action_name',
                                'label' => null,
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
                                'label' => null,
                                'type'  => 'text',
                                'alias' => 'name',
                            ],
                        ],
                        'filters' => [
                            'comp.name' => [
                                'label' => null,
                                'type'  => 'text',
                                'alias' => 'name',
                            ],
                        ],
                    'group' => 'companies',
                ];
                break;
        }

        $this->assertSame($expected, $reportBuilderEvent->getTables());
    }

    /**
     * @dataProvider eventDataProvider
     */
    public function testReportGenerate($context)
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
    public function testonReportGraphGenerate($event)
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

        $mockStmt = $this->getMockBuilder(PDOStatement::class)
            ->disableOriginalConstructor()
            ->setMethods(['fetchAll'])
            ->getMock();

        $this->reportGraphEventMock->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($this->queryBuilderMock);

        $mockChartQuery = $this->getMockBuilder(ChartQuery::class)
            ->disableOriginalConstructor()
            ->setMethods([
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
    public function testOnReportDisplay($event)
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
}
