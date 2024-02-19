<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventListener\CampaignEventSubscriber;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\LeadBundle\Entity\Lead;

class CampaignEventSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CampaignEventSubscriber
     */
    private $fixture;

    /**
     * @var EventRepository
     */
    private $eventRepo;

    /**
     * @var NotificationHelper
     */
    private $notificationHelper;

    public function setUp()
    {
        $this->eventRepo          = $this->createMock(EventRepository::class);
        $this->notificationHelper = $this->createMock(NotificationHelper::class);
        $this->fixture            = new CampaignEventSubscriber($this->eventRepo, $this->notificationHelper);
    }

    public function testEventFailedCountsGetResetOnCampaignPublish()
    {
        $campaign = new Campaign();
        // Ensure the campaign is unpublished
        $campaign->setIsPublished(false);
        // Go from unpublished to published.
        $campaign->setIsPublished(true);

        $this->eventRepo->expects($this->once())
            ->method('resetFailedCountsForEventsInCampaign')
            ->with($campaign);

        $this->fixture->onCampaignPreSave(new CampaignEvent($campaign));
    }

    public function testEventFailedCountsDoesNotGetResetOnCampaignUnPublish()
    {
        $campaign = new Campaign();
        // Ensure the campaign is published
        $campaign->setIsPublished(true);
        // Go from published to unpublished.
        $campaign->setIsPublished(false);

        $this->eventRepo->expects($this->never())
            ->method('resetFailedCountsForEventsInCampaign');

        $this->fixture->onCampaignPreSave(new CampaignEvent($campaign));
    }

    public function testEventFailedCountsDoesNotGetResetWhenPublishedStateIsNotChanged()
    {
        $campaign = new Campaign();

        $this->eventRepo->expects($this->never())
            ->method('resetFailedCountsForEventsInCampaign');

        $this->fixture->onCampaignPreSave(new CampaignEvent($campaign));
    }

    public function testFailedEventGeneratesANotification()
    {
        $mockLead     = $this->createMock(Lead::class);
        $mockCampaign = $this->createMock(Campaign::class);
        $mockCampaign->expects($this->once())
            ->method('getLeads')
            ->willReturn(new ArrayCollection(range(0, 99)));

        $mockEvent = $this->createMock(Event::class);
        $mockEvent->expects($this->once())
            ->method('getCampaign')
            ->willReturn($mockCampaign);

        $mockEventLog = $this->createMock(LeadEventLog::class);
        $mockEventLog->expects($this->once())
            ->method('getEvent')
            ->willReturn($mockEvent);

        $mockEventLog->expects($this->once())
            ->method('getLead')
            ->willReturn($mockLead);

        // Set failed count to 5% of getLeads()->count()
        $this->eventRepo->expects($this->once())
            ->method('incrementFailedCount')
            ->with($mockEvent)
            ->willReturn(5);

        $this->notificationHelper->expects($this->once())
            ->method('notifyOfFailure')
            ->with($mockLead, $mockEvent);

        $failedEvent = new FailedEvent($this->createMock(AbstractEventAccessor::class), $mockEventLog);

        $this->fixture->onEventFailed($failedEvent);
    }

    public function testFailedCountOverDisableCampaignThresholdDisablesTheCampaign()
    {
        $mockLead     = $this->createMock(Lead::class);
        $mockCampaign = $this->createMock(Campaign::class);
        $mockCampaign->expects($this->once())
            ->method('getLeads')
            ->willReturn(new ArrayCollection(range(0, 99)));

        $mockEvent = $this->createMock(Event::class);
        $mockEvent->expects($this->once())
            ->method('getCampaign')
            ->willReturn($mockCampaign);

        $mockEventLog = $this->createMock(LeadEventLog::class);
        $mockEventLog->expects($this->once())
            ->method('getEvent')
            ->willReturn($mockEvent);

        $mockEventLog->expects($this->once())
            ->method('getLead')
            ->willReturn($mockLead);

        // Set failed count to 10% of getLeads()->count()
        $this->eventRepo->expects($this->once())
            ->method('incrementFailedCount')
            ->with($mockEvent)
            ->willReturn(10);

        $this->notificationHelper->expects($this->once())
            ->method('notifyOfFailure')
            ->with($mockLead, $mockEvent);

        $this->notificationHelper->expects($this->once())
            ->method('notifyOfUnpublish')
            ->with($mockEvent);

        $failedEvent = new FailedEvent($this->createMock(AbstractEventAccessor::class), $mockEventLog);

        $mockEm = $this->createMock(EntityManager::class);
        $mockEm->expects($this->once())
            ->method('persist')
            ->with($mockCampaign);

        $mockCampaign->expects($this->once())
            ->method('setIsPublished')
            ->with(false);

        $this->fixture->setEntityManager($mockEm);

        $this->fixture->onEventFailed($failedEvent);
    }
}
