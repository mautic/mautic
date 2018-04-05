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
use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\EventListener\CampaignSubscriber;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;

class CampaignSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    private $configFrom = [
        'companyname'       => 'Mautic',
        'companemail'       => 'mautic@mautic.com',
    ];

    private $configTo = [
        'companyname'       => 'Mautic2',
        'companemail'       => 'mautic@mautic2.com',
    ];

    public function testOnCampaignTriggerActiononUpdateCompany()
    {
        $mockIpLookupHelper = $this->getMockBuilder(IpLookupHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLeadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockLeadFieldModel = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockListModel = $this->getMockBuilder(ListModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockCompanyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockCampaignModel = $this->getMockBuilder(CampaignModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $subscriber = new CampaignSubscriber($mockIpLookupHelper, $mockLeadModel, $mockLeadFieldModel, $mockListModel, $mockCompanyModel, $mockCampaignModel);

        $lead = new Lead();
        $lead->setId(54);
        $mockLeadModel->expects($this->once())->method('saveEntity')->with($lead);

        $helper     = new IdentifyCompanyHelper();
        $reflection = new \ReflectionClass(IdentifyCompanyHelper::class);
        $method     = $reflection->getMethod('identifyLeadsCompany');
        $method->setAccessible(true);
        list($company, $leadAdded, $companyEntity)  = $method->invokeArgs($helper, [$this->configFrom, $lead, $mockCompanyModel]);
        $mockCompanyModel->expects($this->once())->method('addLeadToCompany')->with($companyEntity, $lead);

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

        $event = new CampaignExecutionEvent($args, false);
        $subscriber->onCampaignTriggerActionUpdateCompany($event);
        $this->assertFalse($event->getResult());

        $primaryCompany = $event->getLead()->getPrimaryCompany();

        $this->assertSame($this->configTo['companyname'], $primaryCompany['companyname']);
    }
}
