<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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

    private $configTo = [
        'id'          => '112',
        'companyname' => 'Mautic2',
        'companemail' => 'mautic@mauticsecond.com',
    ];

    /**
     * @var IpLookupHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockIpLookupHelper;

    /**
     * @var LeadModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockLeadModel;

    /**
     * @var FieldModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockLeadFieldModel;

    /**
     * @var ListModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockListModel;

    /**
     * @var CompanyModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockCompanyModel;

    /**
     * @var CampaignModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mockCampaignModel;

    /**
     * @var Company|\PHPUnit\Framework\MockObject\MockObject
     */
    private $companyEntityFrom;

    protected function setUp(): void
    {
        $this->mockIpLookupHelper = $this->createMock(IpLookupHelper::class);
        $this->mockLeadModel      = $this->createMock(LeadModel::class);
        $this->mockLeadFieldModel = $this->createMock(FieldModel::class);
        $this->mockListModel      = $this->createMock(ListModel::class);
        $this->mockCompanyModel   = $this->createMock(CompanyModel::class);
        $this->mockCampaignModel  = $this->createMock(CampaignModel::class);
        $this->companyEntityFrom  = $this->createMock(Company::class);
    }

    public function testOnCampaignTriggerActionUpdateContact()
    {
        $mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $mockCoreParametersHelper->method('get')
            ->with('default_timezone')
            ->willReturn('UTC');

        $subscriber = new CampaignSubscriber(
            $this->mockIpLookupHelper,
            $this->mockLeadModel,
            $this->mockLeadFieldModel,
            $this->mockListModel,
            $this->mockCompanyModel,
            $this->mockCampaignModel,
            $mockCoreParametersHelper
        );

        /** @var LeadModel $leadModel */
        $lead = new Lead();
        $lead->setId(99);
        $fields = [
            'core' => [
                'custom_multiselect'  => [
                    'alias' => 'custom_multiselect',
                    'label' => 'Custom multiselect',
                    'type'  => 'multiselect',
                    'value' => 'second|three',
                ],
                'custom_multiselect2' => [
                    'alias' => 'custom_multiselect2',
                    'label' => 'Custom multiselect 2',
                    'type'  => 'multiselect',
                    'value' => 'second|three',
                ],
                'custom_multiselect3' => [
                    'alias' => 'custom_multiselect3',
                    'label' => 'Custom multiselect 3',
                    'type'  => 'multiselect',
                    'value' => 'second|three',
                ],
                'custom_multiselect4' => [
                    'alias' => 'custom_multiselect4',
                    'label' => 'Custom multiselect 4',
                    'type'  => 'multiselect',
                    'value' => 'second|three',
                ],
            ],
        ];

        $lead->setFields($fields);

        $config = [];

        $config['fields_to_update'][]             = 'custom_multiselect';
        $config['actions']['custom_multiselect']  = 'add';
        $config['fields']['custom_multiselect']   = ['first'];

        $config['fields_to_update'][]              = 'custom_multiselect2';
        $config['actions']['custom_multiselect2']  = 'remove';
        $config['fields']['custom_multiselect2']   = ['second'];

        $config['fields_to_update'][]             = 'custom_multiselect3';
        $config['actions']['custom_multiselect3'] = 'update';
        $config['fields']['custom_multiselect3']  = ['first', 'second', 'tree', 'four'];

        $config['fields_to_update'][]             = 'custom_multiselect4';
        $config['actions']['custom_multiselect4'] = 'empty';
        $config['fields']['custom_multiselect4']  = [];

        $args = [
            'lead'            => $lead,
            'event'           => [
                'type'       => 'lead.updatelead',
                'properties' => $config,
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        $this->mockLeadModel
            ->expects(self::at(0))
            ->method('setFieldValues')
            ->willReturnCallback(
                function ($lead, $values) {
                    $this->assertCount(4, $values['custom_multiselect3']);
                }
            );

        $this->mockLeadModel
            ->expects(self::at(1))
            ->method('setFieldValues')
            ->willReturnCallback(
                function ($lead, $values) {
                    $this->assertCount(0, $values['custom_multiselect4']);
                }
            );

        $this->mockLeadModel
            ->expects(self::at(2))
            ->method('setFieldValues')
            ->willReturnCallback(
                function ($lead, $values) {
                    $this->assertCount(3, $values['custom_multiselect']);
                }
            );

        $this->mockLeadModel
            ->expects(self::at(3))
            ->method('setFieldValues')
            ->willReturnCallback(
                function ($lead, $values) {
                    $this->assertCount(1, $values['custom_multiselect2']);
                }
            );

        $event = new CampaignExecutionEvent($args, true);
        $subscriber->onCampaignTriggerActionUpdateLead($event);
    }

    public function testOnCampaignTriggerActiononUpdateCompany()
    {
        $this->companyEntityFrom->method('getId')
            ->willReturn($this->configFrom['id']);
        $this->companyEntityFrom->method('getName')
            ->willReturn($this->configFrom['companyname']);

        $companyEntityTo = $this->createMock(Company::class);
        $companyEntityTo->method('getId')
            ->willReturn($this->configTo['id']);
        $companyEntityTo->method('getName')
            ->willReturn($this->configTo['companyname']);

        $this->mockCompanyModel->expects($this->once())->method('getEntity')->willReturn($this->companyEntityFrom);

        $this->mockCompanyModel->expects($this->once())
            ->method('getEntities')
            ->willReturn([$companyEntityTo]);

        $mockCompanyLeadRepo = $this->createMock(CompanyLeadRepository::class);
        $mockCompanyLeadRepo->expects($this->once())->method('getCompaniesByLeadId')->willReturn(null);

        $this->mockCompanyModel->expects($this->once())
            ->method('getCompanyLeadRepository')
            ->willReturn($mockCompanyLeadRepo);

        $mockCoreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $mockCoreParametersHelper->method('get')
            ->with('default_timezone')
            ->willReturn('UTC');

        $subscriber = new CampaignSubscriber(
            $this->mockIpLookupHelper,
            $this->mockLeadModel,
            $this->mockLeadFieldModel,
            $this->mockListModel,
            $this->mockCompanyModel,
            $this->mockCampaignModel,
            $mockCoreParametersHelper
        );

        /** @var LeadModel $leadModel */
        $lead = new Lead();
        $lead->setId(99);
        $lead->setPrimaryCompany($this->configFrom);

        $this->mockLeadModel->expects($this->once())->method('setPrimaryCompany')->willReturnCallback(
            function () use ($lead) {
                $lead->setPrimaryCompany($this->configTo);
            }
        );

        $args = [
            'lead'            => $lead,
            'event'           => [
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
}
