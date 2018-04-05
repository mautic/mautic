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
use Mautic\EmailBundle\EventListener\CampaignSubscriber;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;

class CampaignSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var array */
    private $config = [
        'companyname'       => 'Mautic',
        'companemail'       => 'mautic@mautic.com',
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

        $args = [
            'lead'  => 54,
            'event' => [
                'type'       => 'lead.updatecompany',
                'properties' => $this->config,
            ],
            'eventDetails'    => [],
            'systemTriggered' => true,
            'eventSettings'   => [],
        ];

        $event = new CampaignExecutionEvent($args, false);
        $subscriber->onCampaignTriggerActionUpdateCompany($event);

        $this->assertFalse($event->getResult());

        $lead           = $event->getLead();
        $primaryCompany = $lead->getPrimaryCompany();
        $this->assertSame($this->config['companyname'], $primaryCompany['companyname']);
    }
}
