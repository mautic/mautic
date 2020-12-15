<?php

declare(strict_types=1);

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
use Doctrine\ORM\NonUniqueResultException;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Event\ExecutedEvent;
use Mautic\CampaignBundle\Event\FailedEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\AbstractEventAccessor;
use Mautic\CampaignBundle\EventListener\CampaignEventSubscriber;
use Mautic\CampaignBundle\Executioner\Helper\NotificationHelper;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockObject;

class CampaignEventSubscriberTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @var CampaignModel
     */
    private $campaignModel;

    /**
     * @var MockObject|LeadEventLogRepository
     */
    private $leadEventLogRepositoryMock;

    public function setUp(): void
    {
        $this->eventRepo                  = $this->createMock(EventRepository::class);
        $this->notificationHelper         = $this->createMock(NotificationHelper::class);
        $this->campaignModel              = $this->createMock(CampaignModel::class);
        $this->leadEventLogRepositoryMock = $this->createMock(LeadEventLogRepository::class);
        $this->fixture                    = new CampaignEventSubscriber(
            $this->eventRepo,
            $this->notificationHelper,
            $this->campaignModel,
            $this->leadEventLogRepositoryMock
        );
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
        $this->leadEventLogRepositoryMock->expects($this->once())
            ->method('isLastFailed')
            ->with(42, 42)
            ->willReturn(false);

        $mockLead     = $this->createMock(Lead::class);
        $mockLead->expects($this->any())
            ->method('getId')
            ->willReturn(42);
        $mockCampaign = $this->createMock(Campaign::class);
        $mockCampaign->expects($this->once())
            ->method('getLeads')
            ->willReturn(new ArrayCollection(range(0, 99)));

        $mockEvent = $this->createMock(Event::class);
        $mockEvent->expects($this->once())
            ->method('getCampaign')
            ->willReturn($mockCampaign);
        $mockEvent->expects($this->any())
            ->method('getId')
            ->willReturn(42);

        $mockEventLog = $this->createMock(LeadEventLog::class);
        $mockEventLog->expects($this->once())
            ->method('getEvent')
            ->willReturn($mockEvent);

        $mockEventLog->expects($this->any())
            ->method('getLead')
            ->willReturn($mockLead);

        $this->eventRepo->expects($this->once())
            ->method('getFailedCountLeadEvent')
            ->withAnyParameters()
            ->willReturn(105);

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
        $this->leadEventLogRepositoryMock->expects($this->once())
            ->method('isLastFailed')
            ->with(42, 42)
            ->willReturn(false);

        $mockLead     = $this->createMock(Lead::class);
        $mockLead->expects($this->any())
            ->method('getId')
            ->willReturn(42);
        $mockCampaign = $this->createMock(Campaign::class);
        $mockCampaign->expects($this->once())
            ->method('getLeads')
            ->willReturn(new ArrayCollection(range(0, 99)));

        $mockEvent = $this->createMock(Event::class);
        $mockEvent->expects($this->once())
            ->method('getCampaign')
            ->willReturn($mockCampaign);
        $mockEvent->expects($this->any())
            ->method('getId')
            ->willReturn(42);

        $mockEventLog = $this->createMock(LeadEventLog::class);
        $mockEventLog->expects($this->once())
            ->method('getEvent')
            ->willReturn($mockEvent);

        $mockEventLog->expects($this->any())
            ->method('getLead')
            ->willReturn($mockLead);

        $this->eventRepo->expects($this->once())
            ->method('getFailedCountLeadEvent')
            ->withAnyParameters()
            ->willReturn(200);

        // Set failed count to 35% of getLeads()->count()
        $this->eventRepo->expects($this->once())
            ->method('incrementFailedCount')
            ->with($mockEvent)
            ->willReturn(35);

        $this->notificationHelper->expects($this->once())
            ->method('notifyOfFailure')
            ->with($mockLead, $mockEvent);

        $this->notificationHelper->expects($this->once())
            ->method('notifyOfUnpublish')
            ->with($mockEvent);

        $failedEvent = new FailedEvent($this->createMock(AbstractEventAccessor::class), $mockEventLog);

        $this->campaignModel->expects($this->once())
            ->method('saveEntity')
            ->with($mockCampaign);

        $mockCampaign->expects($this->once())
            ->method('setIsPublished')
            ->with(false);

        $this->fixture->onEventFailed($failedEvent);
    }

    public function testOnEventExecutedDecreaseTheCounter(): void
    {
        $mockEventLog = $this->createMock(LeadEventLog::class);

        $lead = new Lead();
        $lead->setId(42);

        $eventMock = $this->createMock(Event::class);
        $eventMock->expects($this->any())
            ->method('getId')
            ->willReturn(42);

        $mockEventLog->expects($this->at(0))
            ->method('getEvent')
            ->willReturn($eventMock);

        $mockEventLog->expects($this->at(1))
            ->method('getLead')
            ->willReturn($lead);

        $this->leadEventLogRepositoryMock->expects($this->once())
            ->method('isLastFailed')
            ->with(42, 42)
            ->willReturn(true);

        $executedEvent = new ExecutedEvent($this->createMock(AbstractEventAccessor::class), $mockEventLog);

        $this->eventRepo->expects($this->once())
            ->method('getFailedCountLeadEvent')
            ->withAnyParameters()
            ->willReturn(101);

        $this->eventRepo->expects($this->once())
            ->method('decreaseFailedCount')
            ->with($eventMock);

        $this->fixture->onEventExecuted($executedEvent);
    }

    /**
     * @throws NonUniqueResultException
     */
    public function testOnFailedEventGeneratesOneUnPublishNotificationAndEmail(): void
    {
        $leadEventLogMock = $this->createMock(LeadEventLog::class);
        $eventMock        = $this->createMock(Event::class);
        $leadEventLogMock->expects(self::once())->method('getEvent')->willReturn($eventMock);
        $leadMock = $this->createMock(Lead::class);
        $leadEventLogMock->expects(self::once())->method('getLead')->willReturn($leadMock);
        $campaignMock = $this->createMock(Campaign::class);
        $campaignMock->expects(self::once())->method('isPublished')->willReturn(false);
        $eventMock->expects(self::atMost(5))->method('getCampaign')->willReturn($campaignMock);
        $leadMock->expects(self::atMost(3))->method('getId')->willReturn(1);
        $eventMock->expects(self::atMost(2))->method('getId')->willReturn(1);
        $this->eventRepo->expects(self::once())->method('getFailedCountLeadEvent')
            ->with(1, 1)->willReturn(101);
        $this->leadEventLogRepositoryMock->expects(self::once())->method('isLastFailed')
            ->with(1, 1)->willReturn(false);
        $this->eventRepo->expects(self::once())->method('incrementFailedCount')
            ->with($eventMock)->willReturn(35);
        $totalLeads = array_fill(0, 100, new Lead());
        $campaignMock->expects(self::once())->method('getLeads')->willReturn(new ArrayCollection($totalLeads));
        $userModelMock           = $this->createMock(UserModel::class);
        $userMock                = $this->createMock(User::class);
        $translatorInterfaceMock = $this->createMock(TranslatorInterface::class);
        $userMock->expects(self::atMost(2))->method('getId')->willReturn(1);
        $userModelMock->expects(self::atMost(2))->method('getSystemAdministrator')->willReturn($userMock);
        // If campaign is unpublished, make sure UserModel::emailUser() is never called again.
        $userModelMock->expects(self::never())->method('emailUser')
            ->with($userMock, $translatorInterfaceMock, $translatorInterfaceMock);
        $notificationModelMock = $this->createMock(NotificationModel::class);
        $routerMock            = $this->createMock(Router::class);
        $notificationHelperObj = new NotificationHelper(
            $userModelMock,
            $notificationModelMock,
            $translatorInterfaceMock,
            $routerMock,
            $this->coreParametersHelperMock
        );
        $campaignEventSubscriberObj = new CampaignEventSubscriber(
            $this->eventRepo,
            $notificationHelperObj,
            $this->campaignModel,
            $this->leadEventLogRepositoryMock
        );
        $failedEvent = new FailedEvent($this->createMock(AbstractEventAccessor::class), $leadEventLogMock);
        $campaignEventSubscriberObj->onEventFailed($failedEvent);
    }
}
