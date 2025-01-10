<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Executioner\Scheduler\Mode;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\Scheduler\Exception\NotSchedulableException;
use Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Psr\Log\NullLogger;

class IntervalTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider provideBatchReschedulingData
     *
     * @param array<int> $restrictedDays
     */
    public function testBatchRescheduling(\DateTime $expectedScheduleDate, \DateTime $scheduledOnDate, string $localTimezone = 'UTC', ?\DateTime $specifiedHour = null, ?\DateTime $startTime = null, ?\DateTime $endTime = null, array $restrictedDays = []): void
    {
        $contact1 = $this->createMock(Lead::class);
        $contact1->method('getId')
            ->willReturn(1);
        $contact1->method('getTimezone')
            ->willReturn($localTimezone);
        $contacts = new ArrayCollection([$contact1]);

        $campaign = $this->createMock(Campaign::class);
        $campaign->method('getId')
            ->willReturn(1);

        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getCampaign')
            ->willReturn($campaign);
        if ($startTime) {
            $event->method('getTriggerRestrictedStartHour')
                ->willReturn($startTime);
        }
        if ($endTime) {
            $event->method('getTriggerRestrictedStopHour')
                ->willReturn($endTime);
        }
        if ($specifiedHour) {
            $event->method('getTriggerHour')
                ->willReturn($specifiedHour);
        }
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn($restrictedDays);

        $interval = $this->getInterval();
        $grouped  = $interval->groupContactsByDate($event, $contacts, $scheduledOnDate);

        $firstGroup    = reset($grouped);
        $executionDate = $firstGroup->getExecutionDate();

        Assert::assertEquals($expectedScheduleDate->format('Y-m-d H:i'), $executionDate->format('Y-m-d H:i'));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function provideBatchReschedulingData(): array
    {
        return [
            'test on specified hour'                     => [new \DateTime('2018-10-18 16:00'), new \DateTime('2018-10-18 16:00'), 'UTC', new \DateTime('2018-10-18 16:00')],
            'test on previous day specified hour'        => [new \DateTime('2018-10-17 16:00'), new \DateTime('2018-10-17 16:00'), 'UTC', new \DateTime('2018-10-18 16:00')],
            'test on next day specified hour'            => [new \DateTime('2018-10-19 16:00'), new \DateTime('2018-10-19 16:00'), 'UTC', new \DateTime('2018-10-18 16:00')],
            'test on start time'                         => [new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 10:00'), 'UTC', null, new \DateTime('2018-10-19 10:00')],
            'test on before start time'                  => [new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 8:00'), 'UTC', null, new \DateTime('2018-10-19 10:00')],
            'test on before start time previous day'     => [new \DateTime('2018-10-18 08:00'), new \DateTime('2018-10-18 8:00'), 'UTC', null, new \DateTime('2018-10-19 10:00')],
            'test on before start time next day'         => [new \DateTime('2018-10-20 08:00'), new \DateTime('2018-10-20 8:00'), 'UTC', null, new \DateTime('2018-10-19 10:00')],
            'test on after start time'                   => [new \DateTime('2018-10-19 12:00'), new \DateTime('2018-10-19 12:00'), 'UTC', null, new \DateTime('2018-10-19 10:00')],
            'test on after start time previous day'      => [new \DateTime('2018-10-18 12:00'), new \DateTime('2018-10-18 12:00'), 'UTC', null, new \DateTime('2018-10-19 10:00')],
            'test on after start time next day'          => [new \DateTime('2018-10-20 12:00'), new \DateTime('2018-10-20 12:00'), 'UTC', null, new \DateTime('2018-10-19 10:00')],
            'test on end time'                           => [new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 10:00'), 'UTC', null, null, new \DateTime('2018-10-19 10:00')],
            'test on before end time'                    => [new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 8:00'), 'UTC', null, null, new \DateTime('2018-10-19 10:00')],
            'test on before end time previous day'       => [new \DateTime('2018-10-18 08:00'), new \DateTime('2018-10-18 8:00'), 'UTC', null, null, new \DateTime('2018-10-19 10:00')],
            'test on before end time next day'           => [new \DateTime('2018-10-20 08:00'), new \DateTime('2018-10-20 8:00'), 'UTC', null, null, new \DateTime('2018-10-19 10:00')],
            'test on after end time'                     => [new \DateTime('2018-10-19 12:00'), new \DateTime('2018-10-19 12:00'), 'UTC', null, null, new \DateTime('2018-10-19 10:00')],
            'test on after end time previous day'        => [new \DateTime('2018-10-18 12:00'), new \DateTime('2018-10-18 12:00'), 'UTC', null, null, new \DateTime('2018-10-19 10:00')],
            'test on after end time next day'            => [new \DateTime('2018-10-20 12:00'), new \DateTime('2018-10-20 12:00'), 'UTC', null, null, new \DateTime('2018-10-19 10:00')],
            'test in time range'                         => [new \DateTime('2018-10-20 08:00'), new \DateTime('2018-10-19 10:00'), 'UTC', null, new \DateTime('2018-10-19 8:00'), new \DateTime('2018-10-19 10:00')],
            'test on before time range'                  => [new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 10:00'), 'UTC', null, new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 11:00')],
            'test on before time range previous day'     => [new \DateTime('2018-10-18 10:00'), new \DateTime('2018-10-18 10:00'), 'UTC', null, new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 11:00')],
            'test on before time range next day'         => [new \DateTime('2018-10-20 10:00'), new \DateTime('2018-10-20 10:00'), 'UTC', null, new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 11:00')],
            'test on after end time range'               => [new \DateTime('2018-10-20 08:00'), new \DateTime('2018-10-19 12:00'), 'UTC', null, new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 10:00')],
            'test on after end time range previous day'  => [new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-18 12:00'), 'UTC', null, new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 10:00')],
            'test on after end time range next day'      => [new \DateTime('2018-10-21 08:00'), new \DateTime('2018-10-20 12:00'), 'UTC', null, new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 10:00')],
            'test on allowed day'                        => [new \DateTime('2018-10-21 08:00'), new \DateTime('2018-10-21 8:00'), 'UTC', null, null, null, [0]],
            'test on restricted days'                    => [new \DateTime('2018-10-24 08:00'), new \DateTime('2018-10-21 08:00'), 'UTC', null, null, null, [3, 5]],
            'test on all restricted days'                => [new \DateTime('2018-10-21 08:00'), new \DateTime('2018-10-21 08:00'), 'UTC', null, null, null, []],
            'test in between wrong start/end time order' => [new \DateTime('2018-10-20 08:00'), new \DateTime('2018-10-19 10:00'), 'UTC', null, new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 8:00')],
            'test combination of rules'                  => [new \DateTime('2018-10-26 08:00'), new \DateTime('2018-10-20 12:00'), 'UTC', null, new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 10:00'), [5, 6]],
            'test valid timezone'                        => [new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 09:00'), 'America/New_York', null, new \DateTime('2018-10-19 8:00'), new \DateTime('2018-10-19 10:00')],
            'test invalid timezone'                      => [new \DateTime('2018-10-19 09:00'), new \DateTime('2018-10-19 13:00'), 'UTC2', null, new \DateTime('2018-10-19 8:00'), new \DateTime('2018-10-19 10:00')],
        ];
    }

    /**
     * @dataProvider provideReschedulingData
     *
     * @param array<int> $restrictedDays
     */
    public function testRescheduling(\DateTime $expectedScheduleDate, \DateTime $scheduledOnDate, ?\DateTime $specifiedHour = null, ?\DateTime $startTime = null, ?\DateTime $endTime = null, array $restrictedDays = [], int $triggerInterval = 0, string $intervalUnit = 'H'): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn($restrictedDays);
        $event->method('getTriggerInterval')
            ->willReturn($triggerInterval);
        $event->method('getTriggerIntervalUnit')
            ->willReturn($intervalUnit);
        if ($startTime) {
            $event->method('getTriggerRestrictedStartHour')
                ->willReturn($startTime);
        }
        if ($endTime) {
            $event->method('getTriggerRestrictedStopHour')
                ->willReturn($endTime);
        }
        if ($specifiedHour) {
            $event->method('getTriggerHour')
                ->willReturn($specifiedHour);
        }

        $interval         = $this->getInterval();
        $scheduledForDate = $interval->getExecutionDateTime($event, $scheduledOnDate, $scheduledOnDate);

        Assert::assertEquals($expectedScheduleDate->format('Y-m-d H:i'), $scheduledForDate->format('Y-m-d H:i'));
    }

    /**
     * @return array<string, array<mixed>>
     */
    public static function ProvidereschedulingData(): array
    {
        return [
            'test on specified hour'                     => [new \DateTime('2018-10-18 16:00'), new \DateTime('2018-10-18 16:00'), new \DateTime('2018-10-18 16:00')],
            'test on previous day specified hour'        => [new \DateTime('2018-10-17 16:00'), new \DateTime('2018-10-17 16:00'), new \DateTime('2018-10-18 16:00')],
            'test on next day specified hour'            => [new \DateTime('2018-10-19 16:00'), new \DateTime('2018-10-19 16:00'), new \DateTime('2018-10-18 16:00')],
            'test on start time'                         => [new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 10:00'), null, new \DateTime('2018-10-19 10:00')],
            'test on before start time'                  => [new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 8:00'), null, new \DateTime('2018-10-19 10:00')],
            'test on before start time previous day'     => [new \DateTime('2018-10-18 08:00'), new \DateTime('2018-10-18 8:00'), null, new \DateTime('2018-10-19 10:00')],
            'test on before start time next day'         => [new \DateTime('2018-10-20 08:00'), new \DateTime('2018-10-20 8:00'), null, new \DateTime('2018-10-19 10:00')],
            'test on after start time'                   => [new \DateTime('2018-10-19 12:00'), new \DateTime('2018-10-19 12:00'), null, new \DateTime('2018-10-19 10:00')],
            'test on after start time previous day'      => [new \DateTime('2018-10-18 12:00'), new \DateTime('2018-10-18 12:00'), null, new \DateTime('2018-10-19 10:00')],
            'test on after start time next day'          => [new \DateTime('2018-10-20 12:00'), new \DateTime('2018-10-20 12:00'), null, new \DateTime('2018-10-19 10:00')],
            'test on end time'                           => [new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 10:00'), null, null, new \DateTime('2018-10-19 10:00')],
            'test on before end time'                    => [new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 8:00'), null, null, new \DateTime('2018-10-19 10:00')],
            'test on before end time previous day'       => [new \DateTime('2018-10-18 08:00'), new \DateTime('2018-10-18 8:00'), null, null, new \DateTime('2018-10-19 10:00')],
            'test on before end time next day'           => [new \DateTime('2018-10-20 08:00'), new \DateTime('2018-10-20 8:00'), null, null, new \DateTime('2018-10-19 10:00')],
            'test on after end time'                     => [new \DateTime('2018-10-19 12:00'), new \DateTime('2018-10-19 12:00'), null, null, new \DateTime('2018-10-19 10:00')],
            'test on after end time previous day'        => [new \DateTime('2018-10-18 12:00'), new \DateTime('2018-10-18 12:00'), null, null, new \DateTime('2018-10-19 10:00')],
            'test on after end time next day'            => [new \DateTime('2018-10-20 12:00'), new \DateTime('2018-10-20 12:00'), null, null, new \DateTime('2018-10-19 10:00')],
            'test in time range'                         => [new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 10:00'), null, new \DateTime('2018-10-19 8:00'), new \DateTime('2018-10-19 10:00')],
            'test on before time range'                  => [new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 10:00'), null, new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 11:00')],
            'test on before time range previous day'     => [new \DateTime('2018-10-18 10:00'), new \DateTime('2018-10-18 10:00'), null, new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 11:00')],
            'test on before time range next day'         => [new \DateTime('2018-10-20 10:00'), new \DateTime('2018-10-20 10:00'), null, new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 11:00')],
            'test on after end time range'               => [new \DateTime('2018-10-19 12:00'), new \DateTime('2018-10-19 12:00'), null, new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 10:00')],
            'test on after end time range previous day'  => [new \DateTime('2018-10-18 12:00'), new \DateTime('2018-10-18 12:00'), null, new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 10:00')],
            'test on after end time range next day'      => [new \DateTime('2018-10-20 12:00'), new \DateTime('2018-10-20 12:00'), null, new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 10:00')],
            'test on allowed day'                        => [new \DateTime('2018-10-21 08:00'), new \DateTime('2018-10-21 8:00'), null, null, null, [0]],
            'test on restricted days'                    => [new \DateTime('2018-10-21 08:00'), new \DateTime('2018-10-21 08:00'), null, null, null, [3, 5]],
            'test on all restricted days'                => [new \DateTime('2018-10-21 08:00'), new \DateTime('2018-10-21 08:00'), null, null, null, []],
            'test in between wrong start/end time order' => [new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 10:00'), null, new \DateTime('2018-10-19 10:00'), new \DateTime('2018-10-19 8:00')],
            'test combination of rules'                  => [new \DateTime('2018-10-20 12:00'), new \DateTime('2018-10-20 12:00'), null, new \DateTime('2018-10-19 08:00'), new \DateTime('2018-10-19 10:00'), [5, 6]],
        ];
    }

    public function testGetExecutionDateTimeThrowsNotSchedulableException(): void
    {
        $scheduledOnDate = new \DateTime('now');

        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getTriggerInterval')
            ->willReturn(10);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('z');

        $interval = $this->getInterval();

        $this->expectException(NotSchedulableException::class);
        $interval->getExecutionDateTime($event, $scheduledOnDate, $scheduledOnDate);
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
        $event->method('getId')
            ->willReturn(1);

        $interval = $this->getInterval();
        $contact1 = new Lead();
        $contact1->setId(1);
        $contact1->setTimezone('America/Los_Angeles');

        $contact2 = new Lead();
        $contact2->setId(2);
        $contact2->setTimezone('America/Los_Angeles');

        $contact3 = new Lead();
        $contact3->setId(3);
        $contact3->setTimezone('America/North_Dakota/Center');

        $contact4 = new Lead();
        $contact4->setId(4);
        $contact4->setTimezone('America/North_Dakota/Center');

        $contact5 = new Lead();
        $contact5->setId(5);
        $contact5->setTimezone(''); // use default of New_York

        $contact6 = new Lead();
        $contact6->setId(6);
        $contact6->setTimezone(''); // use default of New_York

        $contact7 = new Lead();
        $contact7->setId(7);
        $contact7->setTimezone('Bad/Timezone'); // use default of New_York

        $contact8 = new Lead();
        $contact8->setId(8);
        $contact8->setTimezone('Bad/Timezone'); // use default of New_York

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
                    $this->assertEquals('2018-10-18 08:00', $executionDate->format('Y-m-d H:i'));
                    break;
                case 'America/New_York':
                    $this->assertCount(4, $groupExecutionDateDAO->getContacts());
                    $this->assertEquals([5, 6, 7, 8], $groupExecutionDateDAO->getContacts()->getKeys());
                    $this->assertEquals('2018-10-18 09:00', $executionDate->format('Y-m-d H:i'));
                    break;
            }
        }
    }

    public function testValidateExecutionDateTimeWhenIsContactSpecificExecutionDateRequiredIsTrue(): void
    {
        $expectedDateTime    = new \DateTime('now');
        $compareFromDateTime = new \DateTime('now');

        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('d');
        $event->method('getTriggerInterval')
            ->willReturn(1);
        $event->method('getTriggerHour')
            ->willReturn(new \DateTime('now'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $lead = $this->createMock(Lead::class);
        $lead->method('getTimezone')
            ->willReturn('UTC');
        $log = $this->createMock(LeadEventLog::class);
        $log->method('getEvent')
            ->willReturn($event);
        $log->method('getDateTriggered')
            ->willReturn(new \DateTime('now'));
        $log->method('getLead')
            ->willReturn($lead);

        $interval = $this->getInterval();

        Assert::assertTrue($interval->isContactSpecificExecutionDateRequired($event));
        Assert::assertEquals($expectedDateTime->modify('+1 day')->format('Y-m-d H:i'), $interval->validateExecutionDateTime($log, $compareFromDateTime)->format('Y-m-d H:i'));
    }

    public function testValidateExecutionDateTimeWhenIsContactSpecificExecutionDateRequiredIsFalse(): void
    {
        $expectedDateTime    = new \DateTime('now');
        $compareFromDateTime = new \DateTime('now');

        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('S');
        $event->method('getTriggerInterval')
            ->willReturn(0);
        $event->method('getTriggerHour')
            ->willReturn(new \DateTime('now'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);

        $lead = $this->createMock(Lead::class);
        $lead->method('getTimezone')
            ->willReturn('UTC');

        $log = $this->createMock(LeadEventLog::class);
        $log->method('getEvent')
            ->willReturn($event);
        $log->method('getDateTriggered')
            ->willReturn(new \DateTime('now'));
        $log->method('getLead')
            ->willReturn($lead);

        $interval = $this->getInterval();

        Assert::assertFalse($interval->isContactSpecificExecutionDateRequired($event));
        Assert::assertEquals($expectedDateTime->format('Y-m-d H:i'), $interval->validateExecutionDateTime($log, $compareFromDateTime)->format('Y-m-d H:i'));
    }

    public function testIsContactSpecificExecutionDateRequiredIsFalseWhenNotCorrectTriggerMode(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_DATE);
        $event2 = $this->createMock(Event::class);
        $event2->method('getId')
            ->willReturn(2);
        $event2->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_IMMEDIATE);

        $interval = $this->getInterval();

        Assert::assertFalse($interval->isContactSpecificExecutionDateRequired($event));
        Assert::assertFalse($interval->isContactSpecificExecutionDateRequired($event2));
    }

    public function testIsContactSpecificExecutionDateRequiredIsFalseWhenNotCorrectIntervalUnit(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('i');

        $event2 = $this->createMock(Event::class);
        $event2->method('getId')
            ->willReturn(1);
        $event2->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event2->method('getTriggerIntervalUnit')
            ->willReturn('h');

        $event3 = $this->createMock(Event::class);
        $event3->method('getId')
            ->willReturn(1);
        $event3->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event3->method('getTriggerIntervalUnit')
            ->willReturn('s');

        $interval = $this->getInterval();

        Assert::assertFalse($interval->isContactSpecificExecutionDateRequired($event));
        Assert::assertFalse($interval->isContactSpecificExecutionDateRequired($event2));
        Assert::assertFalse($interval->isContactSpecificExecutionDateRequired($event3));
    }

    public function testIsContactSpecificExecutionDateRequiredIsTrueWithValidTriggerHour(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('d');
        $event->method('getTriggerHour')
            ->willReturn(new \DateTime('now'));

        $interval = $this->getInterval();

        Assert::assertTrue($interval->isContactSpecificExecutionDateRequired($event));
    }

    public function testIsContactSpecificExecutionDateRequiredIsTrueWithDayOfWeekRestrictions(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('d');
        $event->method('getTriggerHour')
            ->willReturn(null);
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([0, 1, 2]);

        $interval = $this->getInterval();

        Assert::assertTrue($interval->isContactSpecificExecutionDateRequired($event));
    }

    public function testIsContactSpecificExecutionDateRequiredIsTrueWithStartAndStopHours(): void
    {
        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('d');
        $event->method('getTriggerHour')
            ->willReturn(null);
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getTriggerRestrictedStartHour')
            ->willReturn(new \DateTime('now'));
        $event->method('getTriggerRestrictedStopHour')
            ->willReturn(new \DateTime('now'));

        $interval = $this->getInterval();

        Assert::assertTrue($interval->isContactSpecificExecutionDateRequired($event));
    }

    private function getInterval(): Interval
    {
        $coreParametersHelper = $this->createMock(CoreParametersHelper::class);
        $coreParametersHelper->method('get')
            ->willReturnCallback(
                fn ($param, $default) => 'America/New_York'
            );

        return new Interval(new NullLogger(), $coreParametersHelper);
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
        /** @var \DateTime $executionDate */
        $executionDate  = $interval->validateExecutionDateTime($log, new \DateTime('2021-11-08 17:00:00'));
        $executionDate->setTimezone(new \DateTimeZone('UTC'));

        $this->assertEquals('2021-11-08 17:00', $executionDate->format('Y-m-d H:i'));
    }

    public function testValidateExecutionDateTimeWhenForExactHour(): void
    {
        $expectedDateTime    = new \DateTime('now');
        $compareFromDateTime = new \DateTime('now');

        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('i');
        $event->method('getTriggerInterval')
            ->willReturn(0);
        $event->method('getTriggerHour')
            ->willReturn(new \DateTime('now'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);

        $lead = $this->createMock(Lead::class);
        $lead->method('getTimezone')
            ->willReturn('UTC');

        $log = $this->createMock(LeadEventLog::class);
        $log->method('getEvent')
            ->willReturn($event);
        $log->method('getDateTriggered')
            ->willReturn(new \DateTime('now'));
        $log->method('getLead')
            ->willReturn($lead);

        $interval = $this->getInterval();

        Assert::assertEquals($expectedDateTime->format('Y-m-d H:i'), $interval->validateExecutionDateTime($log, $compareFromDateTime)->format('Y-m-d H:i'));
    }

    public function testIsContactSpecificExecutionDateRequiredShouldReturnFalseForNegativePathAction(): void
    {
        $parentEvent = $this->createMock(Event::class);
        $parentEvent->method('getEventType')
            ->willReturn(Event::TYPE_DECISION);
        $event = $this->createMock(Event::class);
        $event->method('getId')
            ->willReturn(1);
        $event->method('getTriggerMode')
            ->willReturn(Event::TRIGGER_MODE_INTERVAL);
        $event->method('getTriggerIntervalUnit')
            ->willReturn('i');
        $event->method('getTriggerInterval')
            ->willReturn(5);
        $event->method('getDecisionPath')
            ->willReturn(Event::PATH_INACTION);
        $event->method('getEventType')
            ->willReturn(Event::TYPE_ACTION);
        $event->method('getTriggerHour')
            ->willReturn(new \DateTime('now'));
        $event->method('getTriggerRestrictedDaysOfWeek')
            ->willReturn([]);
        $event->method('getParent')
            ->willReturn($parentEvent);

        $interval = $this->getInterval();

        Assert::assertFalse($interval->isContactSpecificExecutionDateRequired($event));
    }
}
