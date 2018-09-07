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
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use Psr\Log\NullLogger;

class IntervalTest extends \PHPUnit_Framework_TestCase
{
    public function testRelativeHour()
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
        $grouped  = $interval->groupContactsByDate($event, $this->getContacts(), new \DateTime('+1 day'));

        foreach ($grouped as $groupExecutionDateDAO) {
            $executionDate = $groupExecutionDateDAO->getExecutionDate();

            switch ($executionDate->getTimezone()->getName()) {
                case 'America/Los_Angeles':
                    $this->assertCount(2, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([1, 2], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('09:00', $executionDate->format('H:i'));
                    break;
                case 'Africa/Johannesburg':
                    $this->assertCount(2, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([3, 4], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('09:00', $executionDate->format('H:i'));
                    break;
                case 'America/New_York':
                    $this->assertCount(2, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([5, 6], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('09:00', $executionDate->format('H:i'));
                    break;
            }
        }
    }

    public function testRelativeStartEndHours()
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

        $interval               = $this->getInterval();
        $scheduledExecutionDate = new \DateTime('16:00', new \DateTimeZone('America/New_York'));
        $grouped                = $interval->groupContactsByDate($event, $this->getContacts(), $scheduledExecutionDate);

        foreach ($grouped as $groupExecutionDateDAO) {
            $executionDate = $groupExecutionDateDAO->getExecutionDate();

            switch ($executionDate->getTimezone()->getName()) {
                case 'America/Los_Angeles':
                    // New York is scheduled at 4pm it's time where Los Angeles at 1pm it's time which is the same time
                    $this->assertCount(4, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([1, 2, 5, 6], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('13:00', $executionDate->format('H:i'));
                    $diff = $scheduledExecutionDate->diff($executionDate);

                    // This was within the time range for all contacts so should be 0
                    $this->assertEquals(0, $diff->h);
                    $this->assertEquals(0, $diff->d);
                    break;
                case 'Africa/Johannesburg':
                    $this->assertCount(2, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([3, 4], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('10:00', $executionDate->format('H:i'));
                    // Johannesburg should be 12 hours difference because 4pm ET their time is 10pm which is after hours so it should be sent
                    // at 10am instead
                    $diff = $scheduledExecutionDate->diff($executionDate);
                    $this->assertEquals(12, $diff->h);
                    $this->assertEquals(0, $diff->d);

                    break;
            }
        }
    }

    public function testRelativeDayOfWeek()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $scheduledExecutionDate = new \DateTime('now');
        $inTwoDays              = clone $scheduledExecutionDate;
        $inTwoDays->modify('+2 days');

        $dow = $inTwoDays->format('w');

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dow]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $this->getContacts(), $scheduledExecutionDate);

        foreach ($grouped as $groupExecutionDateDAO) {
            // Everyone should be on the same day
            $this->assertCount(6, $groupExecutionDateDAO->getContacts());

            // The scheduled date should not be the same day of the week we set getTriggerRestrictedDaysOfWeek
            $this->assertEquals($dow, $groupExecutionDateDAO->getExecutionDate()->format('w'));
        }
    }

    public function testHourWithDaysOfWeek()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $scheduledExecutionDate = new \DateTime('now');
        $inTwoDays              = clone $scheduledExecutionDate;
        $inTwoDays->modify('+2 days');

        $dow = $inTwoDays->format('w');

        $event = $this->createMock(Event::class);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerHour')
            ->willReturn(
                new \DateTime('1970-01-01 09:00:00')
            );
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([$dow]);
        $event->method('getCampaign')
            ->willReturn($campaign);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $this->getContacts(), $scheduledExecutionDate);

        foreach ($grouped as $groupExecutionDateDAO) {
            $executionDate = $groupExecutionDateDAO->getExecutionDate();

            // Should be on the allowed DOW
            $this->assertEquals($dow, $executionDate->format('w'));

            switch ($executionDate->getTimezone()->getName()) {
                case 'America/Los_Angeles':
                    $this->assertCount(2, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([1, 2], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('09:00', $executionDate->format('H:i'));
                    break;
                case 'Africa/Johannesburg':
                    $this->assertCount(2, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([3, 4], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('09:00', $executionDate->format('H:i'));
                    break;
                case 'America/New_York':
                    $this->assertCount(2, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([5, 6], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('09:00', $executionDate->format('H:i'));
                    break;
            }
        }
    }

    public function testRelativeStartEndHoursWithDaysOfWeek()
    {
        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $scheduledExecutionDate = new \DateTime('16:00', new \DateTimeZone('America/New_York'));
        $inTwoDays              = clone $scheduledExecutionDate;
        $inTwoDays->modify('+2 days');

        $dow = $inTwoDays->format('w');

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

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $this->getContacts(), $scheduledExecutionDate);

        foreach ($grouped as $groupExecutionDateDAO) {
            $executionDate = $groupExecutionDateDAO->getExecutionDate();

            // Should be on the allowed DOW
            $this->assertEquals($dow, $executionDate->format('w'));

            switch ($executionDate->getTimezone()->getName()) {
                case 'America/Los_Angeles':
                    // New York is scheduled at 4pm it's time where Los Angeles at 1pm it's time which is the same time
                    $this->assertCount(4, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([1, 2, 5, 6], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('13:00', $executionDate->format('H:i'));
                    $diff = $scheduledExecutionDate->diff($executionDate);

                    // This was within the time range for all contacts so should be 0
                    $this->assertEquals(0, $diff->h);
                    $this->assertEquals(2, $diff->d);
                    break;
                case 'Africa/Johannesburg':
                    $this->assertCount(2, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([3, 4], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('10:00', $executionDate->format('H:i'));
                    // Johannesburg should be 12 hours difference because 4pm ET their time is 10pm which is after hours so it should be sent
                    // at 10am instead but also should be the expected weekday
                    $diff = $scheduledExecutionDate->diff($executionDate);
                    $this->assertEquals(12, $diff->h);
                    $this->assertEquals(1, $diff->d);

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
        $coreParametersHelper->method('getParameter')
            ->willReturnCallback(
                function ($param, $default) {
                    return 'America/New_York';
                }
            );

        return new Interval(new NullLogger(), $coreParametersHelper);
    }

    /**
     * @return ArrayCollection
     */
    private function getContacts()
    {
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
            ->willReturn('Africa/Johannesburg');

        $contact4 = $this->createMock(Lead::class);
        $contact4->method('getId')
            ->willReturn(4);
        $contact4->method('getTimezone')
            ->willReturn('Africa/Johannesburg');

        $contact5 = $this->createMock(Lead::class);
        $contact5->method('getId')
            ->willReturn(5);
        $contact5->method('getTimezone')
            ->willReturn('');

        $contact6 = $this->createMock(Lead::class);
        $contact6->method('getId')
            ->willReturn(6);
        $contact6->method('getTimezone')
            ->willReturn('');

        $contacts = new ArrayCollection([
            1 => $contact1,
            2 => $contact2,
            3 => $contact3,
            4 => $contact4,
            5 => $contact5,
            6 => $contact6,
        ]);

        return $contacts;
    }
}
