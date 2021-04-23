<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\DecisionAccessor;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\Event\DecisionExecutioner;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Psr\Log\NullLogger;

class RealTimeExecutionerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LeadModel
     */
    private $leadModel;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventRepository
     */
    private $eventRepository;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventExecutioner
     */
    private $executioner;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|DecisionExecutioner
     */
    private $decisionExecutioner;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventCollector
     */
    private $eventCollector;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|EventScheduler
     */
    private $eventScheduler;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ContactTracker
     */
    private $contactTracker;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|LeadRepository
     */
    private $leadRepository;

    protected function setUp(): void
    {
        $this->leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventRepository = $this->getMockBuilder(EventRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->executioner = $this->getMockBuilder(EventExecutioner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->decisionExecutioner = $this->getMockBuilder(DecisionExecutioner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventCollector = $this->getMockBuilder(EventCollector::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventScheduler = $this->getMockBuilder(EventScheduler::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->contactTracker = $this->getMockBuilder(ContactTracker::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->leadRepository = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testContactNotFoundResultsInEmptyResponses()
    {
        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn(null);

        $this->eventRepository->expects($this->never())
            ->method('getContactPendingEvents');

        $responses = $this->getExecutioner()->execute('something');

        $this->assertEquals(0, $responses->containsResponses());
    }

    public function testNoRelatedEventsResultInEmptyResponses()
    {
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
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

    public function testChannelMisMatchResultsInEmptyResponses()
    {
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->expects($this->exactly(5))
            ->method('getId')
            ->willReturn(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->getMockBuilder(Event::class)
            ->getMock();
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

    public function testChannelFuzzyMatchResultsInNonEmptyResponses()
    {
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->expects($this->exactly(5))
            ->method('getId')
            ->willReturn(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->getMockBuilder(Event::class)
            ->getMock();
        $event->expects($this->exactly(2))
            ->method('getChannel')
            ->willReturn('page');
        $event->method('getEventType')
            ->willReturn(Event::TYPE_DECISION);

        $action1 = $this->getMockBuilder(Event::class)
            ->getMock();
        $action2 = $this->getMockBuilder(Event::class)
            ->getMock();

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

    public function testChannelIdMisMatchResultsInEmptyResponses()
    {
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->expects($this->exactly(5))
            ->method('getId')
            ->willReturn(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->getMockBuilder(Event::class)
            ->getMock();
        $event->expects($this->exactly(2))
            ->method('getChannel')
            ->willReturn('email');
        $event->expects($this->exactly(4))
            ->method('getChannelId')
            ->willReturn(3);
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

    public function testEmptyPositiveactionsResultsInEmptyResponses()
    {
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->expects($this->exactly(5))
            ->method('getId')
            ->willReturn(10);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->getMockBuilder(Event::class)
            ->getMock();
        $event->expects($this->exactly(2))
            ->method('getChannel')
            ->willReturn('email');
        $event->expects($this->exactly(3))
            ->method('getChannelId')
            ->willReturn(3);
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

    public function testAssociatedEventsAreExecuted()
    {
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
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

        $action1 = $this->getMockBuilder(Event::class)
            ->getMock();
        $action2 = $this->getMockBuilder(Event::class)
            ->getMock();

        $event = $this->getMockBuilder(Event::class)
            ->getMock();
        $event->expects($this->exactly(2))
            ->method('getChannel')
            ->willReturn('email');
        $event->expects($this->exactly(3))
            ->method('getChannelId')
            ->willReturn(3);
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

    public function testNonDecisionEventsAreIgnored()
    {
        $lead = $this->getMockBuilder(Lead::class)
            ->getMock();
        $lead->expects($this->exactly(5))
            ->method('getId')
            ->willReturn(10);
        $lead->expects($this->once())
            ->method('getChanges')
            ->willReturn(['notempty' => true]);

        $this->contactTracker->expects($this->once())
            ->method('getContact')
            ->willReturn($lead);

        $event = $this->getMockBuilder(Event::class)
            ->getMock();
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
            $this->leadRepository
        );
    }
}
