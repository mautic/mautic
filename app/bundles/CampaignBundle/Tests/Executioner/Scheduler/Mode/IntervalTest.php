<?php

namespace Mautic\CampaignBundle\Tests\Executioner\Scheduler\Mode;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\NullLogger;

class IntervalTest extends \PHPUnit\Framework\TestCase
{
    public function testRescheduledToDueBeingBeforeSpecificHourRestriction()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 09:00:00')
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

        $grouped    = $interval->groupContactsByDate($event, $contacts, new \DateTime('2018-10-18 14:00:00', new \DateTimeZone('UTC')));
        $firstGroup = reset($grouped);

        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals('2018-10-18 09:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testRescheduledDueToBeingAfterSpecificHourRestriction()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 09:00:00')
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

        $grouped    = $interval->groupContactsByDate($event, $contacts, new \DateTime('2018-10-18 16:00:00', new \DateTimeZone('UTC')));
        $firstGroup = reset($grouped);

        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals('2018-10-18 09:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testNotRescheduledDueToSpecificHourRestriction()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 09:00:00')
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

        $grouped       = $interval->groupContactsByDate($event, $contacts, new \DateTime('2018-10-18 14:00:00', new \DateTimeZone('UTC')));
        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        // 6am pacific = 9am eastern so don't reschedule
        $this->assertEquals('2018-10-18 09:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testRescheduledToSameDayDueToStartHourRestriction()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('1970-01-01 10:00:00'));
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
        $contacts = new ArrayCollection([$contact1]);

        $interval               = $this->getInterval();
        $scheduledExecutionDate = new \DateTime('2018-10-18 12:00', new \DateTimeZone('UTC'));
        $grouped                = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals('2018-10-18 10:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testIsNotRescheduledDueToStartAndStopHourRestrictions()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('1970-01-01 10:00:00'));
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
            ->willReturn('Etc/GMT+5');
        $contacts = new ArrayCollection([$contact1]);

        $interval               = $this->getInterval();
        $scheduledExecutionDate = new \DateTime('2018-10-18 16:00', new \DateTimeZone('UTC'));
        $grouped                = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals('2018-10-18 11:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testRescheduledToNextDayDueToStopHourRestriction()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('1970-01-01 10:00:00'));
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
        $contacts = new ArrayCollection([$contact1]);

        $interval               = $this->getInterval();
        $scheduledExecutionDate = new \DateTime('2018-10-19 02:00', new \DateTimeZone('UTC'));
        $grouped                = $interval->groupContactsByDate($event, $contacts, $scheduledExecutionDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        $this->assertEquals('2018-10-19 10:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testRescheduledDueDayOfWeekRestriction()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime('2018-10-18 15:00:00', new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getCampaign')
            ->willReturn($campaign);
        // Only send on Saturday
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([6]);

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
        $this->assertEquals('2018-10-20 15:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testNotRescheduledDueDayOfWeekRestriction()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime('2018-10-18 15:00:00', new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getCampaign')
            ->willReturn($campaign);
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([4]);

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
        $this->assertEquals('2018-10-18 15:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testRescheduledDueToSpecificHourAndDayOfWeekRestrictions()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime('2018-10-18 15:00:00', new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 09:00:00')
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([6]);
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

        $this->assertEquals('2018-10-20 09:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testNotRescheduledDueToSpecificHourAndDayOfWeekRestrictions()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime('2018-10-18 15:00:00', new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 10:00:00')
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([4]);
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

        $this->assertEquals('2018-10-18 10:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testRescheduledDueToStartEndHoursAndDayOfWeekRestrictions()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime('2018-10-18 14:00:00', new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('1970-01-01 10:00:00'));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime('1970-01-01 20:00:00'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([6]);
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

        $this->assertEquals('2018-10-20 10:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testNotRescheduledDueToStartEndHoursAndDayOfWeekRestrictions()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime('2018-10-18 16:00:00', new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('1970-01-01 10:00:00'));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime('1970-01-01 20:00:00'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([4]);
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

        $this->assertEquals('2018-10-18 11:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testRescheduledDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyDowViolation()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime('2018-10-18 15:00:00', new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('1970-01-01 10:00:00'));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime('1970-01-01 20:00:00'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([6]);
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

        $this->assertEquals('2018-10-20 10:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testRescheduledToSameDayDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyStartHourViolation()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime('2018-10-18 13:00:00', new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('1970-01-01 10:00:00'));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime('1970-01-01 20:00:00'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([4]);
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

        $this->assertEquals('2018-10-18 10:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testRescheduledToNextDayDueToStartEndHoursAndDayOfWeekRestrictionsWithOnlyEndHourViolation()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        // Thursday/4
        $scheduledExecutionDate = new \DateTime('2018-10-19 02:00:00', new \DateTimeZone('UTC'));

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('1970-01-01 10:00:00'));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime('1970-01-01 20:00:00'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([4, 5]);
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

        $this->assertEquals('2018-10-19 10:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testContactsAreGrouped()
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
