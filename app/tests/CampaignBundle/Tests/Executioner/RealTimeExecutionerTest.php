<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner;

use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\Event\DecisionExecutioner;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\Helper\DecisionHelper;
use Mautic\CampaignBundle\Executioner\Helper\EventRedirectionHelper;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CampaignBundle\Executioner\Result\Responses;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[AllowMockObjectsWithoutExpectations]
final class RealTimeExecutionerTest extends TestCase
{
    private MockObject&LeadModel $leadModel;

    private MockObject&EventRepository $eventRepository;

    private MockObject&EventExecutioner $executioner;

    private MockObject&DecisionExecutioner $decisionExecutioner;

    private MockObject&EventCollector $eventCollector;

    private MockObject&EventScheduler $eventScheduler;

    private MockObject&ContactTracker $contactTracker;

    private DecisionHelper $decisionHelper;

    private EventRedirectionHelper&MockObject $redirectionHelper;

    protected function setUp(): void
    {
        $this->leadModel = $this->createMock(LeadModel::class);

        $this->eventRepository = $this->createMock(EventRepository::class);

        $this->executioner = $this->createMock(EventExecutioner::class);

        $this->decisionExecutioner = $this->createMock(DecisionExecutioner::class);

        $this->eventCollector = $this->createMock(EventCollector::class);

        $this->eventScheduler = $this->createMock(EventScheduler::class);

        $this->contactTracker = $this->createMock(ContactTracker::class);

        $this->decisionHelper    = new DecisionHelper($this->createStub(LeadRepository::class));
        $this->redirectionHelper = $this->createMock(EventRedirectionHelper::class);

        // Configure the redirection helper mock to return the event it receives
        $this->redirectionHelper->method('handleEventRedirection')
            ->willReturnCallback(fn (Event $event): Event => $event);
    }

    public function testContactNotFoundResultsInEmptyResponses(): void
    {
        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn(null);

        $this->eventRepository->expects($this->never())
            ->method('getContactPendingEvents');

        $responses = $this->getExecutioner()->execute('something');
        $this->assertInstanceOf(Responses::class, $responses);

        $this->assertSame(0, $responses->containsResponses());
    }

    public function testNoRelatedEventsResultInEmptyResponses(): void
    {
        $lead = new Lead();
        $lead->setId(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([]);

        $this->eventCollector->expects($this->never())
            ->method('getEventConfig');

        $responses = $this->getExecutioner()->execute('something');
        $this->assertInstanceOf(Responses::class, $responses);

        $this->assertSame(0, $responses->containsResponses());
    }

    public function testChannelMisMatchResultsInEmptyResponses(): void
    {
        $lead = new Lead();
        $lead->setId(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = new Event();
        $event->setChannel('email');
        $event->setEventType(Event::TYPE_DECISION);

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([$event]);

        $this->eventCollector->expects($this->never())
            ->method('getEventConfig');

        $responses = $this->getExecutioner()->execute('something', null, 'page');
        $this->assertInstanceOf(Responses::class, $responses);

        $this->assertSame(0, $responses->containsResponses());
    }

    public function testChannelFuzzyMatchResultsInNonEmptyResponses(): void
    {
        $lead = new Lead();
        $lead->setId(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = new Event();
        $event->setChannel('page');
        $event->setEventType(Event::TYPE_DECISION);

        $action1 = new Event();
        $action1->setDecisionPath(Event::PATH_ACTION);
        $action2 = new Event();
        $action2->setDecisionPath(Event::PATH_ACTION);
        $event->addChild($action1);
        $event->addChild($action2);

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
        $lead = new Lead();
        $lead->setId(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->createEvent();
        $event->setEventType(Event::TYPE_DECISION);

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([$event]);

        $this->eventCollector->expects($this->never())
            ->method('getEventConfig');

        $responses = $this->getExecutioner()->execute('something', null, 'email', 1);
        $this->assertInstanceOf(Responses::class, $responses);

        $this->assertSame(0, $responses->containsResponses());
    }

    public function testEmptyPositiveactionsResultsInEmptyResponses(): void
    {
        $lead = new Lead();
        $lead->setId(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->createEvent();
        $event->setEventType(Event::TYPE_DECISION);

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([$event]);

        $this->eventCollector->expects($this->once())
            ->method('getEventConfig')
            ->willReturn(new DecisionAccessor([]));

        $this->decisionExecutioner->expects($this->once())
            ->method('evaluateForContact');

        $responses = $this->getExecutioner()->execute('something', null, 'email', 3);
        $this->assertInstanceOf(Responses::class, $responses);

        $this->assertSame(0, $responses->containsResponses());
    }

    public function testAssociatedEventsAreExecuted(): void
    {
        $lead = new Lead();
        $lead->setId(10);
        $lead->setChanges(['notempty' => true]);

        $this->leadModel->expects($this->once())
            ->method('saveEntity');

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $action1 = new Event();
        $action1->setDecisionPath(Event::PATH_ACTION);
        $action2 = new Event();
        $action2->setDecisionPath(Event::PATH_ACTION);

        $event = $this->createEvent();
        $event->setEventType(Event::TYPE_DECISION);
        $event->addChild($action1);
        $event->addChild($action2);

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
        $this->assertInstanceOf(Responses::class, $responses);

        $this->assertSame(0, $responses->containsResponses());
    }

    public function testNonDecisionEventsAreIgnored(): void
    {
        $lead = new Lead();
        $lead->setId(10);
        $lead->setChanges(['notempty' => true]);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = new Event();
        $event->setEventType(Event::TYPE_CONDITION);

        $this->eventRepository->expects($this->once())
            ->method('getContactPendingEvents')
            ->willReturn([$event]);

        $responses = $this->getExecutioner()->execute('something');
        $this->assertInstanceOf(Responses::class, $responses);

        $this->assertSame(0, $responses->containsResponses());
    }

    private function createEvent(): Event
    {
        $event = new Event();
        $event->setChannel('email');
        $event->setChannelId('3');

        return $event;
    }

    private function getExecutioner(): RealTimeExecutioner
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
            $this->decisionHelper,
            $this->redirectionHelper
        );
    }
}
