<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Executioner\Scheduler\Mode;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\NullLogger;
use Throwable;

class IntervalTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return iterable<string, array<mixed>>
     */
    public function rescheduledToDueBeingBeforeSpecificHourRestrictionProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 09:00:00', '2018-10-18 14:00:00', '2018-10-18 09:00'];
        yield 'With Interval' => [5, 'D', '1970-01-01 09:00:00', '2018-10-18 14:00:00', '2018-10-23 09:00'];
    }

    /**
     * @dataProvider rescheduledToDueBeingBeforeSpecificHourRestrictionProvider
     */
    public function testRescheduledToDueBeingBeforeSpecificHourRestriction(int $triggerInterval, string $triggerIntervalUnit, string $triggerHour, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime($triggerHour)
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $interval = $this->getInterval();

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/Los_Angeles');
        $contacts = new ArrayCollection([$contact1]);

        $grouped    = $interval->groupContactsByDate($event, $contacts, new \DateTime($executionDate, new \DateTimeZone('UTC')));
        $firstGroup = reset($grouped);

        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function rescheduledDueToBeingAfterSpecificHourRestrictionProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 09:00:00', '2018-10-18 16:00:00', '2018-10-18 09:00'];
        yield 'With Interval' => [5, 'D', '1970-01-01 09:00:00', '2018-10-18 16:00:00', '2018-10-23 09:00'];
    }

    /**
     * @dataProvider rescheduledDueToBeingAfterSpecificHourRestrictionProvider
     */
    public function testRescheduledDueToBeingAfterSpecificHourRestriction(int $triggerInterval, string $triggerIntervalUnit, string $triggerHour, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime($triggerHour)
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $interval = $this->getInterval();

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/Los_Angeles');
        $contacts = new ArrayCollection([$contact1]);

        $grouped    = $interval->groupContactsByDate($event, $contacts, new \DateTime($executionDate, new \DateTimeZone('UTC')));
        $firstGroup = reset($grouped);

        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function notRescheduledDueToSpecificHourRestrictionProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 09:00:00', '2018-10-18 14:00:00', '2018-10-18 09:00'];
        yield 'With Interval' => [5, 'D', '1970-01-01 09:00:00', '2018-10-18 14:00:00', '2018-10-23 09:00'];
    }

    /**
     * @dataProvider notRescheduledDueToSpecificHourRestrictionProvider
     */
    public function testNotRescheduledDueToSpecificHourRestriction(int $triggerInterval, string $triggerIntervalUnit, string $triggerHour, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime($triggerHour)
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $interval = $this->getInterval();

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');
        $contacts = new ArrayCollection([$contact1]);

        $grouped       = $interval->groupContactsByDate($event, $contacts, new \DateTime($executionDate, new \DateTimeZone('UTC')));
        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        // 6am pacific = 9am eastern so don't reschedule
        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function rescheduledToSameDayDueToStartHourRestrictionProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 10:00:00', '1970-01-01 20:00:00', '2018-10-18 12:00', '2018-10-18 10:00'];
        yield 'With Interval' => [5, 'D', '1970-01-01 10:00:00', '1970-01-01 20:00:00', '2018-10-18 12:00', '2018-10-23 10:00'];
    }

    /**
     * @dataProvider rescheduledToSameDayDueToStartHourRestrictionProvider
     */
    public function testRescheduledToSameDayDueToStartHourRestriction(int $triggerInterval, string $triggerIntervalUnit, string $triggerRestrictedStartHour, string $triggerRestrictedStopHour, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime($triggerRestrictedStartHour));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime($triggerRestrictedStopHour));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');
        $contacts = new ArrayCollection([$contact1]);

        $interval               = $this->getInterval();
        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));
        $grouped                = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    public function testExecutionDateIsValidatedAsExpectedWithStartHourAndDaylightSavingsTimeChange(): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn(15);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('D');
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('1970-01-01 08:00:00'));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime('1970-01-01 20:00:00'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');

        $log = new LeadEventLog();
        $log->setCampaign($campaign);
        $log->setEvent($event);
        $log->setLead($contact1);
        $log->setDateTriggered(new \DateTime('2021-10-24 17:00:00'));
        $log->setTriggerDate(new \DateTime('2021-12-08 17:00:00'));
        $log->setIsScheduled(true);

        $interval = $this->getInterval();

        $executionDate  = $interval->validateExecutionDateTime($log, new \DateTime('2021-11-08'));
        $executionDate->setTimezone(new \DateTimeZone('UTC'));

        $this->assertEquals('2021-11-08 17:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testExecutionDateIsValidatedAsExpectedWithTriggerHourAndDaylightSavingsTimeChange(): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn(15);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('D');

        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 12:00:00')
            );
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(null);
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(null);
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');

        $log = new LeadEventLog();
        $log->setCampaign($campaign);
        $log->setEvent($event);
        $log->setLead($contact1);
        $log->setDateTriggered(new \DateTime('2021-10-24 17:00:00'));
        $log->setTriggerDate(new \DateTime('2021-11-08 17:00:00'));
        $log->setIsScheduled(true);

        $interval = $this->getInterval();

        $executionDate  = $interval->validateExecutionDateTime($log, new \DateTime('2021-11-08'));
        $executionDate->setTimezone(new \DateTimeZone('UTC'));

        $this->assertEquals('2021-11-08 17:00', $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function isNotRescheduledDueToStartAndStopHourRestrictionsProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 10:00:00', '1970-01-01 20:00:00', '2018-10-18 16:00', '2018-10-18 11:00'];
        yield 'With Interval' => [5, 'D', '1970-01-01 10:00:00', '1970-01-01 20:00:00', '2018-10-18 16:00', '2018-10-23 11:00'];
    }

    /**
     * @dataProvider isNotRescheduledDueToStartAndStopHourRestrictionsProvider
     */
    public function testIsNotRescheduledDueToStartAndStopHourRestrictions(int $triggerInterval, string $triggerIntervalUnit, string $triggerRestrictedStartHour, string $triggerRestrictedStopHour, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime($triggerRestrictedStartHour));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime($triggerRestrictedStopHour));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('Etc/GMT+5');
        $contacts = new ArrayCollection([$contact1]);

        $interval               = $this->getInterval();
        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));
        $grouped                = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function rescheduledToNextDayDueToStopHourRestrictionProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 10:00:00', '1970-01-01 20:00:00', '2018-10-19 02:00', '2018-10-19 10:00'];
        yield 'With Interval' => [5, 'D', '1970-01-01 10:00:00', '1970-01-01 20:00:00', '2018-10-19 02:00', '2018-10-24 10:00'];
    }

    /**
     * @dataProvider rescheduledToNextDayDueToStopHourRestrictionProvider
     */
    public function testRescheduledToNextDayDueToStopHourRestriction(int $triggerInterval, string $triggerIntervalUnit, string $triggerRestrictedStartHour, string $triggerRestrictedStopHour, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime($triggerRestrictedStartHour));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime($triggerRestrictedStopHour));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');
        $contacts = new ArrayCollection([$contact1]);

        $interval               = $this->getInterval();
        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));
        $grouped                = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function rescheduledDueDayOfWeekRestrictionProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', 6, '2018-10-18 15:00:00', '2018-10-20 15:00'];
        yield 'With Interval' => [7, 'D', 6, '2018-10-18 15:00:00', '2018-10-27 15:00'];
    }

    /**
     * @dataProvider rescheduledDueDayOfWeekRestrictionProvider
     */
    public function testRescheduledDueDayOfWeekRestriction(int $triggerInterval, string $triggerIntervalUnit, int $dayOfWeek, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getCampaign')
            ->willReturn($campaign);
        // Only send on given day of week
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dayOfWeek]);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');
        $contacts = new ArrayCollection([$contact1]);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $executionDate->setTimezone(new \DateTimeZone('UTC'));
        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function notRescheduledDueDayOfWeekRestrictionProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', 4, '2018-10-18 15:00:00', '2018-10-18 15:00'];
        yield 'With Interval' => [7, 'D', 4, '2018-10-18 15:00:00', '2018-10-25 15:00'];
    }

    /**
     * @dataProvider notRescheduledDueDayOfWeekRestrictionProvider
     */
    public function testNotRescheduledDueDayOfWeekRestriction(int $triggerInterval, string $triggerIntervalUnit, int $dayOfWeek, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getCampaign')
            ->willReturn($campaign);
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dayOfWeek]);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');
        $contacts = new ArrayCollection([$contact1]);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $executionDate->setTimezone(new \DateTimeZone('UTC'));
        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function rescheduledDueToSpecificHourAndDayOfWeekRestrictionsProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 09:00:00', 6, '2018-10-18 15:00:00', '2018-10-20 09:00'];
        yield 'With Interval' => [7, 'D', '1970-01-01 09:00:00',  6,  '2018-10-18 15:00:00', '2018-10-27 09:00'];
    }

    /**
     * @dataProvider rescheduledDueToSpecificHourAndDayOfWeekRestrictionsProvider
     */
    public function testRescheduledDueToSpecificHourAndDayOfWeekRestrictions(int $triggerInterval, string $triggerIntervalUnit, string $triggerHour, int $dayOfWeek, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime($triggerHour)
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dayOfWeek]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');
        $contacts = new ArrayCollection([$contact1]);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function notRescheduledDueToSpecificHourAndDayOfWeekRestrictionsProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 10:00:00', 4, '2018-10-18 15:00:00', '2018-10-18 10:00'];
        yield 'With Interval' => [7, 'D', '1970-01-01 10:00:00',  4,  '2018-10-18 15:00:00', '2018-10-25 10:00'];
    }

    /**
     * @dataProvider notRescheduledDueToSpecificHourAndDayOfWeekRestrictionsProvider
     */
    public function testNotRescheduledDueToSpecificHourAndDayOfWeekRestrictions(int $triggerInterval, string $triggerIntervalUnit, string $triggerHour, int $dayOfWeek, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime($triggerHour)
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dayOfWeek]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');
        $contacts = new ArrayCollection([$contact1]);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function rescheduledDueToStartEndHoursAndDayOfWeekRestrictionsProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 10:00:00', '1970-01-01 20:00:00', 6, '2018-10-18 14:00:00', '2018-10-20 10:00'];
        yield 'With Interval' => [7, 'D', '1970-01-01 10:00:00', '1970-01-01 20:00:00',  6,  '2018-10-18 14:00:00', '2018-10-27 10:00'];
    }

    /**
     * @dataProvider rescheduledDueToStartEndHoursAndDayOfWeekRestrictionsProvider
     */
    public function testRescheduledDueToStartEndHoursAndDayOfWeekRestrictions(int $triggerInterval, string $triggerIntervalUnit, string $triggerRestrictedStartHour, string $triggerRestrictedStopHour, int $dayOfWeek, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime($triggerRestrictedStartHour));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime($triggerRestrictedStopHour));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dayOfWeek]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');
        $contacts = new ArrayCollection([$contact1]);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function notRescheduledDueToStartEndHoursAndDayOfWeekRestrictionsProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 10:00:00', '1970-01-01 20:00:00', 4, '2018-10-18 16:00:00', '2018-10-18 11:00'];
        yield 'With Interval' => [7, 'D', '1970-01-01 10:00:00', '1970-01-01 20:00:00',  4,  '2018-10-18 16:00:00', '2018-10-25 11:00'];
    }

    /**
     * @dataProvider notRescheduledDueToStartEndHoursAndDayOfWeekRestrictionsProvider
     */
    public function testNotRescheduledDueToStartEndHoursAndDayOfWeekRestrictions(int $triggerInterval, string $triggerIntervalUnit, string $triggerRestrictedStartHour, string $triggerRestrictedStopHour, int $dayOfWeek, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime($triggerRestrictedStartHour));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime($triggerRestrictedStopHour));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dayOfWeek]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('Etc/GMT+5');
        $contacts = new ArrayCollection([$contact1]);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function rescheduledDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyDowViolationProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 10:00:00', '1970-01-01 20:00:00', 6, '2018-10-18 15:00:00', '2018-10-20 10:00'];
        yield 'With Interval' => [7, 'D', '1970-01-01 10:00:00', '1970-01-01 20:00:00',  6,  '2018-10-18 15:00:00', '2018-10-27 10:00'];
    }

    /**
     * @dataProvider rescheduledDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyDowViolationProvider
     */
    public function testRescheduledDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyDowViolation(int $triggerInterval, string $triggerIntervalUnit, string $triggerRestrictedStartHour, string $triggerRestrictedStopHour, int $dayOfWeek, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime($triggerRestrictedStartHour));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime($triggerRestrictedStopHour));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dayOfWeek]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('Etc/GMT+5');
        $contacts = new ArrayCollection([$contact1]);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function rescheduledToSameDayDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyStartHourViolationProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 10:00:00', '1970-01-01 20:00:00', 4, '2018-10-18 13:00:00', '2018-10-18 10:00'];
        yield 'With Interval' => [7, 'D', '1970-01-01 10:00:00', '1970-01-01 20:00:00',  4,  '2018-10-18 13:00:00', '2018-10-25 10:00'];
    }

    /**
     * @dataProvider rescheduledToSameDayDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyStartHourViolationProvider
     */
    public function testRescheduledToSameDayDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyStartHourViolation(int $triggerInterval, string $triggerIntervalUnit, string $triggerRestrictedStartHour, string $triggerRestrictedStopHour, int $dayOfWeek, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime($triggerRestrictedStartHour));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime($triggerRestrictedStopHour));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dayOfWeek]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');
        $contacts = new ArrayCollection([$contact1]);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return iterable<string, array<mixed>>
     */
    public function rescheduledToNextDayDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyEndHourViolationProvider(): iterable
    {
        yield 'Without any Interval' => [0, '', '1970-01-01 10:00:00', '1970-01-01 20:00:00', [4, 5], '2018-10-19 02:00:00', '2018-10-19 10:00'];
        yield 'With Interval' => [7, 'D', '1970-01-01 10:00:00', '1970-01-01 20:00:00',  [4, 5],  '2018-10-19 02:00:00', '2018-10-26 10:00'];
    }

    /**
     * @dataProvider rescheduledToNextDayDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyEndHourViolationProvider
     *
     * @param array<int> $daysOfWeek
     */
    public function testRescheduledToNextDayDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyEndHourViolation(int $triggerInterval, string $triggerIntervalUnit, string $triggerRestrictedStartHour, string $triggerRestrictedStopHour, array $daysOfWeek, string $executionDate, string $resultedExecutionDate): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $scheduledExecutionDate = new \DateTime($executionDate, new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($triggerIntervalUnit);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime($triggerRestrictedStartHour));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime($triggerRestrictedStopHour));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn($daysOfWeek);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/New_York');
        $contacts = new ArrayCollection([$contact1]);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals($resultedExecutionDate, $executionDate->format('Y-m-d H:i'));
    }

    public function testContactsAreGrouped(): void
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 06:00:00')
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $interval = $this->getInterval();
        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn('America/Los_Angeles');

        $contact2 = $this->createMock(Lead::class);
        $contact2->method('getId')
            ->willReturn(2);
        $contact2->method('getTimezone')
            ->willReturn('America/Los_Angeles');

        $contact3 = $this->createMock(Lead::class);
        $contact3->method('getId')
            ->willReturn(3);
        $contact3->method('getTimezone')
            ->willReturn('America/North_Dakota/Center');

        $contact4 = $this->createMock(Lead::class);
        $contact4->method('getId')
            ->willReturn(4);
        $contact4->method('getTimezone')
            ->willReturn('America/North_Dakota/Center');

        $contact5 = $this->createMock(Lead::class);
        $contact5->method('getId')
            ->willReturn(5);
        $contact5->method('getTimezone')
            ->willReturn(''); // use default of New_York

        $contact6 = $this->createMock(Lead::class);
        $contact6->method('getId')
            ->willReturn(6);
        $contact6->method('getTimezone')
            ->willReturn(''); // use default of New_York

        $contact7 = $this->createMock(Lead::class);
        $contact7->method('getId')
            ->willReturn(7);
        $contact7->method('getTimezone')
            ->willReturn('Bad/Timezone'); // use default of New_York

        $contact8 = $this->createMock(Lead::class);
        $contact8->method('getId')
            ->willReturn(8);
        $contact8->method('getTimezone')
            ->willReturn('Bad/Timezone'); // use default of New_York

        $contacts = new ArrayCollection([
            1 => $contact1,
            2 => $contact2,
            3 => $contact3,
            4 => $contact4,
            5 => $contact5,
            6 => $contact6,
            7 => $contact7,
            8 => $contact8,
        ]);

        $scheduledExecutionDate = new \DateTime('2018-10-18 6:00:00', new \DateTimeZone('America/Los_Angeles'));
        $grouped                = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);
        $this->assertCount(3, $grouped);

        foreach ($grouped as $groupExecutionDateDAO) {
            $executionDate = $groupExecutionDateDAO->getExecutionDate();

            switch ($executionDate->getTimezone()->getName()) {
                case 'America/Los_Angeles':
                    $this->assertCount(2, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([1, 2], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('2018-10-18 06:00', $executionDate->format('Y-m-d H:i'));
                    break;
                case 'America/North_Dakota/Center':
                    $this->assertCount(2, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([3, 4], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('2018-10-18 06:00', $executionDate->format('Y-m-d H:i'));
                    break;
                case 'America/New_York':
                    $this->assertCount(4, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([5, 6, 7, 8], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('2018-10-18 06:00', $executionDate->format('Y-m-d H:i'));
                    break;
            }
        }
    }

    /**
     * @return Interval
     */
    private function getInterval()
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->willReturnCallback(
                function ($param, $default) {
                    return 'America/New_York';
                }
            );

        return new Interval(new NullLogger(), $coreParametersHelper);
    }
}
