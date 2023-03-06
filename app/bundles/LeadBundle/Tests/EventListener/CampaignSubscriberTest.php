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
use Mautic\LeadBundle\Model\DoNotContact;
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

    private $configTo = [
        'id'          => '112',
        'companyname' => 'Mautic2',
        'companemail' => 'mautic@mauticsecond.com',
    ];

    private $dncConditionForm = [
        'condition'   => 0,
        'channels'    => ['email'],
    ];

    private $dncConditionForm2 = [
        'condition'   => 1,
        'channels'    => ['email'],
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
        $doNotContact       = $this->createMock(DoNotContact::class);

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
            $mockCoreParametersHelper,
            $doNotContact
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

    public function testOnCampaignTriggerConditionDNCFlag()
    {
        $mockIpLookupHelper = $this->createMock(IpLookupHelper::class);
        $mockLeadModel      = $this->createMock(LeadModel::class);
        $mockLeadFieldModel = $this->createMock(FieldModel::class);
        $mockListModel      = $this->createMock(ListModel::class);
        $mockCompanyModel   = $this->createMock(CompanyModel::class);
        $mockCampaignModel  = $this->createMock(CampaignModel::class);
        $doNotContact       = $this->createMock(DoNotContact::class);

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
            $mockCoreParametersHelper,
            $doNotContact
        );

        /** @var LeadModel $leadModel */
        $lead = new Lead();
        $lead->setId(99);

        $args = [
            'lead'  => $lead,
            'event' => [
                'type'       => 'lead.dnc',
                'properties' => $this->dncConditionForm,
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        $args2 = [
            'lead'  => $lead,
            'event' => [
                'type'       => 'lead.dnc',
                'properties' => $this->dncConditionForm2,
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        $event = new CampaignExecutionEvent($args, true);
        $subscriber->onCampaignTriggerCondition($event);
        $this->assertTrue($event->getResult());

        $event = new CampaignExecutionEvent($args2, true);
        $subscriber->onCampaignTriggerCondition($event);
        $this->assertFalse($event->getResult());
    }
}
