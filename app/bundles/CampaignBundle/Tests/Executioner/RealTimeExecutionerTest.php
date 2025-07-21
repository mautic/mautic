<?php

namespace Mautic\CampaignBundle\Tests\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\Event\DecisionExecutioner;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\Helper\DecisionHelper;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class RealTimeExecutionerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|LeadModel
     */
    private MockObject $leadModel;

    /**
     * @var MockObject|EventRepository
     */
    private MockObject $eventRepository;

    /**
     * @var MockObject|EventExecutioner
     */
    private MockObject $executioner;

    /**
     * @var MockObject|DecisionExecutioner
     */
    private MockObject $decisionExecutioner;

    /**
     * @var MockObject|EventCollector
     */
    private MockObject $eventCollector;

    /**
     * @var MockObject|EventScheduler
     */
    private MockObject $eventScheduler;

    /**
     * @var MockObject|ContactTracker
     */
    private MockObject $contactTracker;

    /**
     * @var MockObject|LeadRepository
     */
    private MockObject $leadRepository;

    private DecisionHelper $decisionHelper;

    protected function setUp(): void
    {
        $this->leadModel = $this->createMock(LeadModel::class);

        $this->eventRepository = $this->createMock(EventRepository::class);

        $this->executioner = $this->createMock(EventExecutioner::class);

        $this->decisionExecutioner = $this->createMock(DecisionExecutioner::class);

        $this->eventCollector = $this->createMock(EventCollector::class);

        $this->eventScheduler = $this->createMock(EventScheduler::class);

        $this->contactTracker = $this->createMock(ContactTracker::class);

        $this->leadRepository = $this->createMock(LeadRepository::class);

        $this->decisionHelper = new DecisionHelper($this->leadRepository);
    }

    public function testContactNotFoundResultsInEmptyResponses(): void
    {
        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn(null);

        $this->eventRepository->expects($this->never())
            ->method('getContactPendingEvents');

        $responses = $this->getExecutioner()->execute('something');

        $this->assertEquals(0, $responses->containsResponses());
    }

    public function testNoRelatedEventsResultInEmptyResponses(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->exactly(3))
            ->method('getId')
            ->willReturn(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([]);

        $this->eventCollector->expects($this->never())
            ->method('getEventConfig');

        $responses = $this->getExecutioner()->execute('something');

        $this->assertEquals(0, $responses->containsResponses());
    }

    public function testChannelMisMatchResultsInEmptyResponses(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->exactly(5))
            ->method('getId')
            ->willReturn(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->createMock(Event::class);
        $event->expects($this->exactly(3))
            ->method('getChannel')
            ->willReturn('email');
        $event->method('getEventType')
            ->willReturn(Event::TYPE_DECISION);

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([$event]);

        $this->eventCollector->expects($this->never())
            ->method('getEventConfig');

        $responses = $this->getExecutioner()->execute('something', null, 'page');

        $this->assertEquals(0, $responses->containsResponses());
    }

    public function testChannelFuzzyMatchResultsInNonEmptyResponses(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->exactly(5))
            ->method('getId')
            ->willReturn(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->createMock(Event::class);
        $event->expects($this->exactly(2))
            ->method('getChannel')
            ->willReturn('page');
        $event->method('getEventType')
            ->willReturn(Event::TYPE_DECISION);

        $action1 = $this->createMock(Event::class);
        $action2 = $this->createMock(Event::class);

        $event->expects($this->once())
            ->method('getPositiveChildren')
            ->willReturn(new ArrayCollection([$action1, $action2]));

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([$event]);

        $this->eventCollector->expects($this->once())
            ->method('getEventConfig')
            ->willReturn(new DecisionAccessor([]));

        $this->eventScheduler->expects($this->exactly(2))
            ->method('getExecutionDateTime')
            ->willReturn(new \DateTime());

        $this->eventScheduler->expects($this->exactly(2))
            ->method('shouldSchedule')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->eventScheduler->expects($this->once())
            ->method('scheduleForContact');

        // This is how we know if the test failed/passed
        $this->executioner->expects($this->once())
            ->method('executeEventsForContact');

        $this->getExecutioner()->execute('something', null, 'page.redirect');
    }

    public function testChannelIdMisMatchResultsInEmptyResponses(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->exactly(5))
            ->method('getId')
            ->willReturn(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->getEventMock(2, 4);
        $event->method('getEventType')
            ->willReturn(Event::TYPE_DECISION);

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([$event]);

        $this->eventCollector->expects($this->never())
            ->method('getEventConfig');

        $responses = $this->getExecutioner()->execute('something', null, 'email', 1);

        $this->assertEquals(0, $responses->containsResponses());
    }

    public function testEmptyPositiveactionsResultsInEmptyResponses(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->exactly(5))
            ->method('getId')
            ->willReturn(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->getEventMock(2, 3);
        $event->expects($this->once())
            ->method('getPositiveChildren')
            ->willReturn(new ArrayCollection());
        $event->method('getEventType')
            ->willReturn(Event::TYPE_DECISION);

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([$event]);

        $this->eventCollector->expects($this->once())
            ->method('getEventConfig')
            ->willReturn(new DecisionAccessor([]));

        $this->decisionExecutioner->expects($this->once())
            ->method('evaluateForContact');

        $responses = $this->getExecutioner()->execute('something', null, 'email', 3);

        $this->assertEquals(0, $responses->containsResponses());
    }

    public function testAssociatedEventsAreExecuted(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->exactly(5))
            ->method('getId')
            ->willReturn(10);
        $lead->expects($this->once())
            ->method('getChanges')
            ->willReturn(['notempty' => true]);

        $this->leadModel->expects($this->once())
            ->method('saveEntity');

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $action1 = $this->createMock(Event::class);
        $action2 = $this->createMock(Event::class);

        $event = $this->getEventMock(2, 3);
        $event->method('getEventType')
            ->willReturn(Event::TYPE_DECISION);
        $event->expects($this->once())
            ->method('getPositiveChildren')
            ->willReturn(new ArrayCollection([$action1, $action2]));

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([$event]);

        $this->eventCollector->expects($this->once())
            ->method('getEventConfig')
            ->willReturn(new DecisionAccessor([]));

        $this->decisionExecutioner->expects($this->once())
            ->method('evaluateForContact');

        $this->eventScheduler->expects($this->exactly(2))
            ->method('getExecutionDateTime')
            ->willReturn(new \DateTime());

        $this->eventScheduler->expects($this->exactly(2))
            ->method('shouldSchedule')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->eventScheduler->expects($this->once())
            ->method('scheduleForContact');

        $this->executioner->expects($this->once())
            ->method('executeEventsForContact');

        $responses = $this->getExecutioner()->execute('something', null, 'email', 3);

        $this->assertEquals(0, $responses->containsResponses());
    }

    public function testNonDecisionEventsAreIgnored(): void
    {
        $lead = $this->createMock(Lead::class);
        $lead->expects($this->exactly(5))
            ->method('getId')
            ->willReturn(10);
        $lead->expects($this->once())
            ->method('getChanges')
            ->willReturn(['notempty' => true]);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->createMock(Event::class);
        $event->method('getEventType')
            ->willReturn(Event::TYPE_CONDITION);

        $event->expects($this->never())
            ->method('getPositiveChildren');

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([$event]);

        $responses = $this->getExecutioner()->execute('something');

        $this->assertEquals(0, $responses->containsResponses());
    }

    private function getEventMock(int $getChannelExpectsCount, int $getChannelIdExpectsCount): MockObject
    {
        $event = $this->createMock(Event::class);
        $event->expects($this->exactly($getChannelExpectsCount))
            ->method('getChannel')
            ->willReturn('email');
        $event->expects($this->exactly($getChannelIdExpectsCount))
            ->method('getChannelId')
            ->willReturn('3');

        return $event;
    }

    /**
     * @return RealTimeExecutioner
     */
    private function getExecutioner()
    {
        return new RealTimeExecutioner(
            new NullLogger(),
            $this->leadModel,
            $this->eventRepository,
            $this->executioner,
            $this->decisionExecutioner,
            $this->eventCollector,
            $this->eventScheduler,
            $this->contactTracker,
            $this->decisionHelper
        );
    }
}
