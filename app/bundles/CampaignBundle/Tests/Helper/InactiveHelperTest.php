<?php

namespace Mautic\CampaignBundle\Tests\Helper;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CampaignBundle\Executioner\ContactFinder\InactiveContactFinder;
use Mautic\CampaignBundle\Executioner\Helper\DecisionHelper;
use Mautic\CampaignBundle\Executioner\Helper\InactiveHelper;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class InactiveHelperTest extends TestCase
{
    /**
     * @var EventScheduler|MockObject
     */
    private $scheduler;

    /**
     * @var InactiveContactFinder|MockObject
     */
    private $inactiveContactFinder;

    /**
     * @var LeadEventLogRepository|MockObject
     */
    private $eventLogRepository;

    /**
     * @var EventRepository|MockObject
     */
    private $eventRepository;

    /**
     * @var LeadRepository|MockObject
     */
    private $leadRepository;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var InactiveHelper
     */
    private $inactiveHelper;

    /**
     * @var DecisionHelper
     */
    private $decisionHelper;

    protected function setUp(): void
    {
        $this->scheduler             = $this->createMock(EventScheduler::class);
        $this->inactiveContactFinder = $this->createMock(InactiveContactFinder::class);
        $this->eventLogRepository    = $this->createMock(LeadEventLogRepository::class);
        $this->eventRepository       = $this->createMock(EventRepository::class);
        $this->leadRepository        = $this->createMock(LeadRepository::class);
        $this->logger                = $this->createMock(LoggerInterface::class);
        $this->decisionHelper        = new DecisionHelper($this->leadRepository);
        $this->inactiveHelper        = new InactiveHelper(
            $this->scheduler,
            $this->inactiveContactFinder,
            $this->eventLogRepository,
            $this->eventRepository,
            $this->logger,
            $this->decisionHelper
        );
    }

    public function testRemoveContactsThatAreNotApplicable(): void
    {
        $lastActiveEventId = 6;

        // lead not applicable because of parent negative path taken
        $leadNegative = new Lead();
        $leadNegative->setId(9);

        // lead not applicable because of parent positive path taken
        $leadNegative2 = new Lead();
        $leadNegative2->setId(10);

        // applicable lead
        $leadPositive = new Lead();
        $leadPositive->setId(12);

        // lead not applicable because of no parent event log
        $leadNegative3 = new Lead();
        $leadNegative3->setId(11);

        $this->eventLogRepository->expects($this->once())
            ->method('getDatesExecuted')
            ->willReturn(new ArrayCollection([
                $leadNegative->getId()  => DateTime::createFromFormat('Y-m-d H:i:s', '2022-05-28 21:37:00'),
                $leadNegative2->getId() => DateTime::createFromFormat('Y-m-d H:i:s', '2022-05-28 21:37:00'),
                $leadPositive->getId()  => DateTime::createFromFormat('Y-m-d H:i:s', '2022-05-28 21:37:00'),
                $leadNegative3->getId() => DateTime::createFromFormat('Y-m-d H:i:s', '2022-05-28 21:37:00'),
            ]));

        /** @var LeadEventLog&MockObject */
        $log = $this->createMock(LeadEventLog::class);
        $log->expects($this->exactly(3))
            ->method('getNonActionPathTaken')
            ->will($this->onConsecutiveCalls(1, 0, 1));

        /** @var Campaign&MockObject */
        $campaign = $this->createMock(Campaign::class);
        $campaign->expects($this->any())
            ->method('getId')
            ->willReturn(2);

        /** @var Event&MockObject */
        $parentEvent = $this->createMock(Event::class);
        $parentEvent->expects($this->exactly(4))
            ->method('getLogByContactAndRotation')
            ->will($this->onConsecutiveCalls($log, $log, $log, null));

        $event = new Event();
        $event->setParent($parentEvent);
        $event->setDecisionPath('yes');
        $event->setCampaign($campaign);
        $event->setEventType(Event::TYPE_DECISION);

        $parentEvent->expects($this->any())
            ->method('getNegativeChildren')
            ->will($this->onConsecutiveCalls(new ArrayCollection(), new ArrayCollection([$event])));

        $parentEvent->expects($this->any())
            ->method('getPositiveChildren')
            ->will($this->onConsecutiveCalls(new ArrayCollection(), new ArrayCollection()));

        $this->leadRepository->expects($this->exactly(4))
            ->method('getContactRotations')
            ->willReturn([]);

        $this->scheduler->expects($this->any())
            ->method('getExecutionDateTime')
            ->willReturn(DateTime::createFromFormat('Y-m-d H:i:s', '2022-05-30 12:00:00'));

        $now      = DateTime::createFromFormat('Y-m-d H:i:s', '2022-05-31 12:00:00');
        $contacts = new ArrayCollection([
            $leadNegative->getId()  => $leadNegative,
            $leadNegative2->getId() => $leadNegative2,
            $leadPositive->getId()  => $leadPositive,
            $leadNegative3->getId() => $leadNegative3,
        ]);

        $this->inactiveHelper->removeContactsThatAreNotApplicable(
            $now,
            $contacts,
            $lastActiveEventId,
            new ArrayCollection([new Event()]),
            $event
        );

        $this->assertEquals(1, $contacts->count());
    }
}
