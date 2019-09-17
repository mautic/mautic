<?php

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use MauticPlugin\IntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\IntegrationsBundle\EventListener\LeadSubscriber;
use MauticPlugin\IntegrationsBundle\Helper\SyncIntegrationsHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;

class LeadSubscriberTest extends \PHPUnit_Framework_TestCase
{
    private $fieldChangeRepository;
    private $variableExpresserHelperInterface;
    private $syncIntegrationsHelper;
    private $subscriber;

    public function setUp()
    {
        parent::setUp();

        $this->fieldChangeRepository = $this->createMock(FieldChangeRepository::class);
        $this->variableExpresserHelperInterface = $this->createMock(VariableExpresserHelperInterface::class);
        $this->syncIntegrationsHelper = $this->createMock(SyncIntegrationsHelper::class);
        $this->subscriber = new LeadSubscriber(
            $this->fieldChangeRepository,
            $this->variableExpresserHelperInterface,
            $this->syncIntegrationsHelper
        );
    }


    public function testOnLeadPostSaveAnonymousLead()
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->at(0))
            ->method('isAnonymous')
            ->willReturn(true);
        $lead->expects($this->never())
            ->method('getChanges');

        $event = $this->createMock(LeadEvent::class);
        $event->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->subscriber->onLeadPostSave($event);
    }

    public function testOnLeadPostSaveAnonymousLeadObjectSyncNotEnabled()
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->at(0))
            ->method('isAnonymous')
            ->willReturn(false);
        $lead->expects($this->never())
            ->method('getChanges');

        $event = $this->createMock(LeadEvent::class);
        $event->expects($this->once())
            ->method('getLead')
            ->willReturn($lead);

        $this->syncIntegrationsHelper->expects($this->once())
            ->method('hasObjectSyncEnabled')
            ->with(MauticSyncDataExchange::OBJECT_CONTACT)
            ->willReturn(false);

        $this->subscriber->onLeadPostSave($event);
    }

    public function testOnLeadPostDelete()
    {

    }

    public function testOnCompanyPostSave()
    {

    }

    public function testOnCompanyPostDelete()
    {

    }

    public function test__construct()
    {

    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                LeadEvents::LEAD_POST_SAVE      => ['onLeadPostSave', 0],
                LeadEvents::LEAD_POST_DELETE    => ['onLeadPostDelete', 255],
                LeadEvents::COMPANY_POST_SAVE   => ['onCompanyPostSave', 0],
                LeadEvents::COMPANY_POST_DELETE => ['onCompanyPostDelete', 255],
            ],
            LeadSubscriber::getSubscribedEvents()
        );
    }
}
