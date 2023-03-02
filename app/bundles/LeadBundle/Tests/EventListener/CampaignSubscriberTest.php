<?php

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CampaignBundle\Event\CampaignExecutionEvent;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\EventListener\CampaignSubscriber;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;

class CampaignSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var array */
    private $configFrom = [
        'id'          => 111,
        'companyname' => 'Mautic',
        'companemail' => 'mautic@mautic.com',
    ];

    /** @var array<string, string> */
    private $configTo = [
        'id'          => '112',
        'companyname' => 'Mautic2',
        'companemail' => 'mautic@mauticsecond.com',
    ];

    /** @var array<string, string> */
    private $configPageHit = [
        'startDate'         => '2022-06-08 12:45:22.0',
        'endDate'           => '2023-06-08 12:45:22.0',
        'page'              => '1',
        'page_url'          => '',
        'accumulative_time' => '5',
    ];

    /** @var array<string, string> */
    private $configUrlPageHit = [
        'startDate'         => '',
        'endDate'           => '',
        'page'              => '',
        'page_url'          => 'https://example.com',
        'accumulative_time' => '5',
    ];

    /** @var array<string, string> */
    private $configUrlPageHitWithoutSpentTime = [
        'startDate'         => '',
        'endDate'           => '',
        'page'              => '',
        'page_url'          => 'https://example.com',
        'accumulative_time' => '',
    ];

    public function testOnCampaignTriggerActiononUpdateCompany()
    {
        $mockIpLookupHelper = $this->createMock(IpLookupHelper::class);
        $mockLeadModel      = $this->createMock(LeadModel::class);
        $mockLeadFieldModel = $this->createMock(FieldModel::class);
        $mockListModel      = $this->createMock(ListModel::class);
        $mockCompanyModel   = $this->createMock(CompanyModel::class);
        $mockCampaignModel  = $this->createMock(CampaignModel::class);
        $companyEntityFrom  = $this->createMock(Company::class);

        $companyEntityFrom->method('getId')
            ->willReturn($this->configFrom['id']);
        $companyEntityFrom->method('getName')
            ->willReturn($this->configFrom['companyname']);

        $companyEntityTo = $this->createMock(Company::class);
        $companyEntityTo->method('getId')
            ->willReturn($this->configTo['id']);
        $companyEntityTo->method('getName')
            ->willReturn($this->configTo['companyname']);
        $companyEntityTo->method('getProfileFields')
            ->willReturn($this->configTo);

        $mockCompanyModel->expects($this->once())->method('getEntity')->willReturn($companyEntityFrom);

        $mockCompanyLeadRepo  = $this->createMock(CompanyLeadRepository::class);
        $mockCompanyLeadRepo->expects($this->once())->method('getCompaniesByLeadId')->willReturn(null);

        $mockCompanyModel->expects($this->once())
            ->method('getCompanyLeadRepository')
            ->willReturn($mockCompanyLeadRepo);

        $mockCompanyModel->expects($this->once())
            ->method('checkForDuplicateCompanies')
            ->willReturn([$companyEntityTo]);

        $mockCompanyModel->expects($this->any())
            ->method('fetchCompanyFields')
            ->willReturn([['alias' => 'companyname']]);

        $mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $mockCoreParametersHelper->method('get')
            ->with('default_timezone')
            ->willReturn('UTC');

        $subscriber = new CampaignSubscriber(
            $mockIpLookupHelper,
            $mockLeadModel,
            $mockLeadFieldModel,
            $mockListModel,
            $mockCompanyModel,
            $mockCampaignModel,
            $mockCoreParametersHelper
        );

        /** @var LeadModel $leadModel */
        $lead = new Lead();
        $lead->setId(99);
        $lead->setPrimaryCompany($this->configFrom);

        $mockLeadModel->expects($this->once())->method('setPrimaryCompany')->willReturnCallback(
            function () use ($lead) {
                $lead->setPrimaryCompany($this->configTo);
            }
        );

        $args = [
            'lead'  => $lead,
            'event' => [
                'type'       => 'lead.updatecompany',
                'properties' => $this->configTo,
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        $event = new CampaignExecutionEvent($args, true);
        $subscriber->onCampaignTriggerActionUpdateCompany($event);
        $this->assertTrue($event->getResult());

        $primaryCompany = $lead->getPrimaryCompany();
        $this->assertSame($this->configTo['companyname'], $primaryCompany['companyname']);
    }

    public function testOnCampaignTriggerConditionLeadLandingPageHit(): void
    {
        $mockIpLookupHelper = $this->createMock(IpLookupHelper::class);
        $mockLeadModel      = $this->createMock(LeadModel::class);
        $mockLeadFieldModel = $this->createMock(FieldModel::class);
        $mockListModel      = $this->createMock(ListModel::class);
        $mockCompanyModel   = $this->createMock(CompanyModel::class);
        $mockCampaignModel  = $this->createMock(CampaignModel::class);

        $mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $mockCoreParametersHelper->method('get')
            ->with('default_timezone')
            ->willReturn('UTC');

        $subscriber = new CampaignSubscriber(
            $mockIpLookupHelper,
            $mockLeadModel,
            $mockLeadFieldModel,
            $mockListModel,
            $mockCompanyModel,
            $mockCampaignModel,
            $mockCoreParametersHelper
        );

        $lead = new Lead();
        $lead->setId(99);
        $leadTimeline = [
            0 => [
                'events' => [
                    0 => [
                        'event'     => 'page.hit',
                        'eventId'   => '5',
                        'eventType' => 'Page hit',
                        'timestamp' => new \DateTime('2022-06-08 12:45:22.0'),
                        'contactId' => '1',
                        'details'   => [
                            'hit' => [
                                'hitId'    => '5',
                                'page_id'  => '1',
                                'dateHit'  => new \DateTime('2022-06-08 12:45:22.0'),
                                'dateLeft' => new \DateTime('2022-06-08 12:50:42.0'),
                            ],
                        ],
                    ],
                ],
            ], ];

        $mockLeadModel->expects($this->once())->method('getEngagements')->willReturn($leadTimeline);

        $args = [
            'lead'  => $lead,
            'event' => [
                'type'       => 'lead.pageHit',
                'properties' => $this->configPageHit,
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        $event = new CampaignExecutionEvent($args, true);
        $subscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());
    }

    public function testOnCampaignTriggerConditionLeadPageUrlHit(): void
    {
        $mockIpLookupHelper = $this->createMock(IpLookupHelper::class);
        $mockLeadModel      = $this->createMock(LeadModel::class);
        $mockLeadFieldModel = $this->createMock(FieldModel::class);
        $mockListModel      = $this->createMock(ListModel::class);
        $mockCompanyModel   = $this->createMock(CompanyModel::class);
        $mockCampaignModel  = $this->createMock(CampaignModel::class);

        $mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $mockCoreParametersHelper->method('get')
            ->with('default_timezone')
            ->willReturn('UTC');

        $subscriber = new CampaignSubscriber(
            $mockIpLookupHelper,
            $mockLeadModel,
            $mockLeadFieldModel,
            $mockListModel,
            $mockCompanyModel,
            $mockCampaignModel,
            $mockCoreParametersHelper
        );

        $lead = new Lead();
        $lead->setId(99);
        $leadTimeline = [
            0 => [
                'events' => [
                    0 => [
                        'event'     => 'page.hit',
                        'eventId'   => '5',
                        'eventType' => 'Page hit',
                        'timestamp' => new \DateTime('2022-06-08 12:45:22.0'),
                        'contactId' => '1',
                        'details'   => [
                            'hit' => [
                                'hitId'    => '5',
                                'page_id'  => '',
                                'dateHit'  => new \DateTime('2022-06-08 12:45:22.0'),
                                'dateLeft' => new \DateTime('2022-06-08 12:50:42.0'),
                                'url'      => 'https://example.com',
                            ],
                        ],
                    ],
                ],
            ], ];

        $mockLeadModel->expects($this->once())->method('getEngagements')->willReturn($leadTimeline);

        $args = [
            'lead'  => $lead,
            'event' => [
                'type'       => 'lead.pageHit',
                'properties' => $this->configUrlPageHit,
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        $event = new CampaignExecutionEvent($args, true);
        $subscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());
    }

    public function testOnCampaignTriggerConditionLeadPageUrlHitWithoutSpentTime(): void
    {
        $mockIpLookupHelper = $this->createMock(IpLookupHelper::class);
        $mockLeadModel      = $this->createMock(LeadModel::class);
        $mockLeadFieldModel = $this->createMock(FieldModel::class);
        $mockListModel      = $this->createMock(ListModel::class);
        $mockCompanyModel   = $this->createMock(CompanyModel::class);
        $mockCampaignModel  = $this->createMock(CampaignModel::class);

        $mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $mockCoreParametersHelper->method('get')
            ->with('default_timezone')
            ->willReturn('UTC');

        $subscriber = new CampaignSubscriber(
            $mockIpLookupHelper,
            $mockLeadModel,
            $mockLeadFieldModel,
            $mockListModel,
            $mockCompanyModel,
            $mockCampaignModel,
            $mockCoreParametersHelper
        );

        $lead = new Lead();
        $lead->setId(99);
        $leadTimeline = [
            0 => [
                'events' => [
                    0 => [
                        'event'     => 'page.hit',
                        'eventId'   => '5',
                        'eventType' => 'Page hit',
                        'timestamp' => new \DateTime('2022-06-08 12:45:22.0'),
                        'contactId' => '1',
                        'details'   => [
                            'hit' => [
                                'hitId'    => '5',
                                'page_id'  => '',
                                'dateHit'  => new \DateTime('2022-06-08 12:45:22.0'),
                                'dateLeft' => new \DateTime('2022-06-08 12:50:42.0'),
                                'url'      => 'https://example.com',
                            ],
                        ],
                    ],
                ],
            ], ];

        $mockLeadModel->expects($this->once())->method('getEngagements')->willReturn($leadTimeline);

        $args = [
            'lead'  => $lead,
            'event' => [
                'type'       => 'lead.pageHit',
                'properties' => $this->configUrlPageHitWithoutSpentTime,
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        $event = new CampaignExecutionEvent($args, true);
        $subscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());
    }
}
