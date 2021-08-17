<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner\Scheduler;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Event\ScheduledBatchEvent;
use Mautic\CampaignBundle\Event\ScheduledEvent;
use Mautic\CampaignBundle\EventCollector\Accessor\Event\ActionAccessor;
use Mautic\CampaignBundle\EventCollector\EventCollector;
use Mautic\CampaignBundle\Executioner\Logger\EventLogger;
use Mautic\CampaignBundle\Executioner\Scheduler\EventScheduler;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\DateTime;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EventSchedulerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var EventLogger|MockObject
     */
    private $eventLogger;

    /**
     * @var Interval
     */
    private $intervalScheduler;

    /**
     * @var DateTime
     */
    private $dateTimeScheduler;

    /**
     * @var EventCollector|MockObject
     */
    private $eventCollector;

    /**
     * @var EventDispatcherInterface|MockObject
     */
    private $dispatcher;

    /**
     * @var CoreParametersHelper|MockObject
     */
    private $coreParamtersHelper;

    /**
     * @var EventScheduler
     */
    private $scheduler;

    protected function setUp(): void
    {
        $this->logger              = new NullLogger();
        $this->coreParamtersHelper = $this->createMock(CoreParametersHelper::class);
        $this->coreParamtersHelper->method('get')
            ->willReturnCallback(
                function () {
                    return 'America/New_York';
                }
            );
        $this->eventLogger       = $this->createMock(EventLogger::class);
        $this->intervalScheduler = new Interval($this->logger, $this->coreParamtersHelper);
        $this->dateTimeScheduler = new DateTime($this->logger);
        $this->eventCollector    = $this->createMock(EventCollector::class);
        $this->dispatcher        = $this->createMock(EventDispatcherInterface::class);
        $this->scheduler         = new EventScheduler(
            $this->logger,
            $this->eventLogger,
            $this->intervalScheduler,
            $this->dateTimeScheduler,
            $this->eventCollector,
            $this->dispatcher,
            $this->coreParamtersHelper
        );
    }

    public function testShouldScheduleIgnoresSeconds()
    {
        $this->assertFalse(
            $this->scheduler->shouldSchedule(
                new \DateTime('2018-07-03 09:20:45'),
                new \DateTime('2018-07-03 09:20:30')
            )
        );
    }

    public function testShouldSchedule()
    {
        $this->assertTrue(
            $this->scheduler->shouldSchedule(
                new \DateTime('2018-07-03 09:21:45'),
                new \DateTime('2018-07-03 09:20:30')
            )
        );
    }

    public function testShouldScheduleForInactive()
    {
        $date  = new \DateTime();
        $now   = clone $date;
        $event = new Event();
        $event->setTriggerIntervalUnit('d');
        $event->setTriggerMode(Event::TRIGGER_MODE_INTERVAL);

        $this->assertFalse($this->scheduler->shouldScheduleEvent($event, $date, $now));

        $event->setTriggerRestrictedDaysOfWeek([]);

        $this->assertFalse($this->scheduler->shouldScheduleEvent($event, $date, $now));

        $event->setTriggerRestrictedStartHour('23:00');
        $event->setTriggerRestrictedStopHour('23:30');

        $this->assertTrue($this->scheduler->shouldScheduleEvent($event, $date, $now));

        $date->add(new \DateInterval('P2D'));
        $event = new Event();
        $this->assertTrue($this->scheduler->shouldScheduleEvent($event, $date, $now));
    }

    public function testGetExecutionDateForInactivity()
    {
        $date = new \DateTime();
        $now  = clone $date;
        $now->add(new \DateInterval('P2D'));

        $clonedNow = $this->scheduler->getExecutionDateForInactivity($date, $date, $now);
        $this->assertNotSame($now, $clonedNow);
        $this->assertSame($now->getTimestamp(), $clonedNow->getTimestamp());

        $secondDate = clone $date;
        $secondDate->add(new \DateInterval('P1D'));

        $resultDate = $this->scheduler->getExecutionDateForInactivity($date, $secondDate, $now);
        $this->assertSame($date, $resultDate);
    }

    public function testEventDoesNotGetRescheduledForRelativeTimeWhenValidated()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn(1);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('d');
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 09:00:00')
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        // The campaign executed with + 1 day at 1pm ET
        $logDateTriggered = new \DateTime('2018-08-30 17:00:00', new \DateTimeZone('America/New_York'));

        // The log was scheduled to be executed at 9am
        $logTriggerDate = new \DateTime('2018-08-31 13:00:00', new \DateTimeZone('America/New_York'));

        // Simulate now with a few seconds past trigger date because in reality it won't be exact
        $simulatedNow = new \DateTime('2018-08-31 13:00:15', new \DateTimeZone('America/New_York'));

        $contact = $this->createMock(Lead::class);
        $contact->method('getId')
            ->willReturn('1');
        $contact->method('getTimezone')
            ->willReturn('America/New_York');

        $log = $this->createMock(LeadEventLog::class);
        $log->method('getTriggerDate')
            ->willReturn($logTriggerDate);
        $log->method('getDateTriggered')
            ->willReturn($logDateTriggered);
        $log->method('getLead')
            ->willReturn($contact);
        $log->method('getEvent')
            ->willReturn($event);

        $executionDate = $this->scheduler->validateExecutionDateTime($log, $simulatedNow);
        $this->assertFalse($this->scheduler->shouldSchedule($executionDate, $simulatedNow));
        $this->assertEquals('2018-08-31 09:00:00', $executionDate->format('Y-m-d H:i:s'));
        $this->assertEquals('America/New_York', $executionDate->getTimezone()->getName());
    }

    public function testEventIsRescheduledForRelativeTimeIfAppropriate()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn(1);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('d');
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 11:00:00')
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        // The campaign executed with + 1 day at 1pm ET
        $logDateTriggered = new \DateTime('2018-08-30 17:00:00');

        // The log was scheduled to be executed at 9am
        $logTriggerDate = new \DateTime('2018-08-31 13:00:00');

        // Simulate now with a few seconds past trigger date because in reality it won't be exact
        $simulatedNow = new \DateTime('2018-08-31 13:00:15');

        $contact = $this->createMock(Lead::class);
        $contact->method('getId')
            ->willReturn('1');
        $contact->method('getTimezone')
            ->willReturn('America/New_York');

        $log = $this->createMock(LeadEventLog::class);
        $log->method('getTriggerDate')
            ->willReturn($logTriggerDate);
        $log->method('getDateTriggered')
            ->willReturn($logDateTriggered);
        $log->method('getLead')
            ->willReturn($contact);
        $log->method('getEvent')
            ->willReturn($event);

        $executionDate = $this->scheduler->validateExecutionDateTime($log, $simulatedNow);
        $this->assertTrue($this->scheduler->shouldSchedule($executionDate, $simulatedNow));
        $this->assertEquals('2018-08-31 11:00:00', $executionDate->format('Y-m-d H:i:s'));
        $this->assertEquals('America/New_York', $executionDate->getTimezone()->getName());
    }

    public function testEventDoesNotGetRescheduledForRelativeTimeWithDowWhenValidated()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // The campaign executed with + 1 day at 1pm ET
        $logDateTriggered = new \DateTime('2018-08-30 17:00:00', new \DateTimeZone('America/New_York'));

        // The log was scheduled to be executed at 9am
        $logTriggerDate = new \DateTime('2018-08-31 13:00:00', new \DateTimeZone('America/New_York'));

        // Simulate now with a few seconds past trigger date because in reality it won't be exact
        $simulatedNow = new \DateTime('2018-08-31 13:00:15', new \DateTimeZone('America/New_York'));

        $dow = $simulatedNow->format('w');

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('1970-01-01 10:00:00'));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime('1970-01-01 20:00:00'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dow]);
        $event->method('getCampaign')
            ->willReturn($campaign);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('d');

        $contact = $this->createMock(Lead::class);
        $contact->method('getId')
            ->willReturn('1');
        $contact->method('getTimezone')
            ->willReturn('America/New_York');

        $log = $this->createMock(LeadEventLog::class);
        $log->method('getTriggerDate')
            ->willReturn($logTriggerDate);
        $log->method('getDateTriggered')
            ->willReturn($logDateTriggered);
        $log->method('getLead')
            ->willReturn($contact);
        $log->method('getEvent')
            ->willReturn($event);

        $executionDate = $this->scheduler->validateExecutionDateTime($log, $simulatedNow);

        $this->assertFalse($this->scheduler->shouldSchedule($executionDate, $simulatedNow));
        $this->assertEquals('2018-08-31 13:00:15', $executionDate->format('Y-m-d H:i:s'));
        $this->assertEquals('America/New_York', $executionDate->getTimezone()->getName());
    }

    public function testRescheduleFailuresWithRescheduleDateSet(): void
    {
        $logWithRescheduleInterval   = new LeadEventLog();
        $logWithNoRescheduleInterval = new LeadEventLog();
        $event                       = new Event();
        $campaign                    = new Campaign();
        $contact                     = new Lead();
        $now                         = new \DateTimeImmutable('now');

        /** @var MockObject|CoreParametersHelper */
        $coreParamtersHelper = $this->createMock(CoreParametersHelper::class);

        $event->setCampaign($campaign);

        $logWithRescheduleInterval->setRescheduleInterval(new \DateInterval('PT10M'));
        $logWithRescheduleInterval->setEvent($event);
        $logWithRescheduleInterval->setLead($contact);

        $logWithNoRescheduleInterval->setEvent($event);
        $logWithNoRescheduleInterval->setLead($contact);

        $this->eventCollector->method('getEventConfig')
            ->willReturn(new ActionAccessor([]));

        $coreParamtersHelper->expects($this->once())
            ->method('get')
            ->with('campaign_time_wait_on_event_false')
            ->willReturn('PT1H');

        $this->dispatcher->expects($this->exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [
                    CampaignEvents::ON_EVENT_SCHEDULED,
                    $this->callback(
                        function (ScheduledEvent $event) use ($now) {
                            // The first log was scheduled to 10 minutes.
                            Assert::assertGreaterThan($now->modify('+9 minutes'), $event->getLog()->getTriggerDate());
                            Assert::assertLessThan($now->modify('+11 minutes'), $event->getLog()->getTriggerDate());

                            return true;
                        }
                    ),
                ],
                [
                    CampaignEvents::ON_EVENT_SCHEDULED,
                    $this->callback(
                        function (ScheduledEvent $event) use ($now) {
                            // The second log was not scheduled so the default interval is used.
                            Assert::assertGreaterThan($now->modify('+59 minutes'), $event->getLog()->getTriggerDate());
                            Assert::assertLessThan($now->modify('+61 minutes'), $event->getLog()->getTriggerDate());

                            return true;
                        }
                    ),
                ],
                [
                    CampaignEvents::ON_EVENT_SCHEDULED_BATCH,
                    $this->callback(
                        function (ScheduledBatchEvent $event) {
                            Assert::assertCount(2, $event->getScheduled());

                            return true;
                        }
                    ),
                ]
            );

        $scheduler = new EventScheduler(
            $this->logger,
            $this->eventLogger,
            $this->intervalScheduler,
            $this->dateTimeScheduler,
            $this->eventCollector,
            $this->dispatcher,
            $coreParamtersHelper
        );

        $scheduler->rescheduleFailures(new ArrayCollection([$logWithRescheduleInterval, $logWithNoRescheduleInterval]));
    }
}
