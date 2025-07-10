<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class CampaignActionJumpToEventWithIntervalTriggerModeFunctionalTest extends MauticMysqlTestCase
{
    private string $originalTimezone;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $timezone = 'UTC';
        $nowUTC   = new \DateTime('now', new \DateTimeZone($timezone));
        if ($nowUTC->format('G') <= 4) {
            $timezone = 'Asia/Bangkok'; // +07:00
        } elseif ($nowUTC->format('G') >= 20) {
            $timezone = 'America/Phoenix'; // -07:00
        }

        $this->originalTimezone = date_default_timezone_get();

        $this->configParams += [
            'default_timezone' => $timezone,
        ];
    }

    protected function setUp(): void
    {
        // Tear down of the base class will restore timezone to UTC.
        date_default_timezone_set($this->configParams['default_timezone']);

        parent::setUp();
    }

    /**
     * @dataProvider dataForCampaignWithJumpToEventWithIntervalTriggerMode
     */
    public function testCampaignWithJumpToEventWithIntervalTriggerMode(Event $adjustPointEvent, callable $assertEventLog): void
    {
        // Create Campaign
        $campaign = new Campaign();
        $campaign->setName('Campaign With Jump');
        $campaign->setIsPublished(true);
        $campaign->setAllowRestart(true);

        $this->em->persist($campaign);

        // Create event: Condition
        $fieldValueEvent = new Event();
        $fieldValueEvent->setCampaign($campaign);
        $fieldValueEvent->setName('Field Value');
        $fieldValueEvent->setType('lead.field_value');
        $fieldValueEvent->setEventType(Event::TYPE_CONDITION);
        $fieldValueEvent->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $fieldValueEvent->setProperties([
            'field'      => 'firstname',
            'operator'   => '!empty',
            'value'      => null,
            'properties' => [
                'field'    => 'firstname',
                'operator' => '!empty',
                'value'    => null,
            ],
        ]);
        $fieldValueEvent->setOrder(1);

        $this->em->persist($fieldValueEvent);
        $this->em->flush();

        // Event: Adjust point
        $adjustPointEvent->setCampaign($campaign);
        $adjustPointEvent->setParent($fieldValueEvent);

        $this->em->persist($adjustPointEvent);
        $this->em->flush();

        // Create event: Jump to action
        $jumpToEvent = new Event();
        $jumpToEvent->setCampaign($campaign);
        $jumpToEvent->setName('Jump to Condition');
        $jumpToEvent->setType('campaign.jump_to_event');
        $jumpToEvent->setEventType(Event::TYPE_ACTION);
        $jumpToEvent->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $jumpToEvent->setProperties(['jumpToEvent' => $adjustPointEvent->getId()]);
        $jumpToEvent->setParent($fieldValueEvent);
        $jumpToEvent->setDecisionPath('yes');
        $jumpToEvent->setOrder(3);

        $this->em->persist($jumpToEvent);
        $this->em->flush();

        // Create Lead
        $lead = new Lead();
        $lead->setFirstname('First Name');
        $this->em->persist($lead);

        // Create Campaign Lead
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());

        $this->em->persist($campaignLead);
        $this->em->flush();
        $this->em->clear();

        // Execute Campaign
        $this->testSymfonyCommand(
            'mautic:campaigns:trigger',
            ['--campaign-id' => $campaign->getId()]
        );

        // Search the logs
        $leadEventLogRepo = $this->em->getRepository(LeadEventLog::class);
        $adjustEventLog   = $leadEventLogRepo->findOneBy(['event' => $adjustPointEvent->getId()]);

        $assertEventLog($adjustEventLog);
    }

    /**
     * @return iterable<mixed>
     */
    public function dataForCampaignWithJumpToEventWithIntervalTriggerMode(): iterable
    {
        date_default_timezone_set($this->configParams['default_timezone']);
        // Event times starts when the PHPUNIT suite starts. The closures can run minutes later
        // which breaks the test in the CI. Use this time in the closures to avoid flaky tests.
        $testNow = new \DateTime();

        $event = new Event();
        $event->setName('Adjust points');
        $event->setEventType(Event::TYPE_ACTION);
        $event->setTriggerMode(Event::TRIGGER_MODE_INTERVAL);
        $event->setType('lead.changepoints');
        $event->setProperties(['points' => 10]);
        $event->setDecisionPath('no');
        $event->setTriggerInterval(0);
        $event->setTriggerIntervalUnit('i');
        $event->setOrder(2);

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerInterval(10);
        $adjustPointEvent->setTriggerIntervalUnit('i');

        yield 'Points Interval with 10 minutes' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog): void {
                Assert::assertTrue($eventLog->getIsScheduled());
                Assert::assertEqualsWithDelta(10, $eventLog->getDateTriggered()->diff($eventLog->getTriggerDate())->format('%i'), 1);
            },
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerHour((new \DateTime())->modify('-1 hour')->format('H:00:00'));

        yield 'Points at a relative time: Scheduled at - before one hour. Should trigger now.' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog) use ($testNow): void {
                Assert::assertFalse($eventLog->getIsScheduled());
                $this->assertPlusMinusOneMinuteOf($testNow->format('Y-m-d H:00:00'), $eventLog->getTriggerDate()->format('Y-m-d H:00:00'));
            },
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerDate(new \DateTime());
        $adjustPointEvent->setTriggerInterval(1);
        $adjustPointEvent->setTriggerIntervalUnit('H');
        $adjustPointEvent->setTriggerHour((new \DateTime())->modify('-1 hour')->format('H:i'));

        yield 'Points at a relative time: Scheduled at - before one hour with delay of 1 hour' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog): void {
                Assert::assertTrue($eventLog->getIsScheduled());
                Assert::assertEqualsWithDelta(0, $eventLog->getDateTriggered()->diff($eventLog->getTriggerDate())->format('%h'), 1);
            },
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerInterval(1);
        $adjustPointEvent->setTriggerIntervalUnit('d');
        $adjustPointEvent->setTriggerRestrictedStartHour((new \DateTime())->modify('+2 hours'));
        $adjustPointEvent->setTriggerRestrictedStopHour((new \DateTime())->modify('+3 hours'));

        yield 'Points at a relative time: Between future start and stop time with 1 day delay will trigger tomorrow when the time slot starts' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog) use ($testNow): void {
                $testNow = clone $testNow;
                Assert::assertTrue($eventLog->getIsScheduled());
                $this->assertPlusMinusOneMinuteOf($testNow->modify('+1 day')->modify('+2 hours')->format('Y-m-d H:i'), $eventLog->getTriggerDate()->format('Y-m-d H:i'));
            },
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerRestrictedStartHour((new \DateTime())->modify('-2 hours'));
        $adjustPointEvent->setTriggerRestrictedStopHour((new \DateTime())->modify('-1 hours'));

        yield 'Points at a relative time: Between passed time' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog): void {
                Assert::assertTrue($eventLog->getIsScheduled());
                Assert::assertEqualsWithDelta(22, $eventLog->getDateTriggered()->diff($eventLog->getTriggerDate())->format('%h'), 1);
            },
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerRestrictedStartHour((new \DateTime())->modify('+3 hour'));
        $adjustPointEvent->setTriggerRestrictedStopHour((new \DateTime())->modify('+4 hour'));

        yield 'Points at a relative time: Between future time today will schedule for today when the window starts' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog) use ($testNow): void {
                $testNow = clone $testNow;
                Assert::assertTrue($eventLog->getIsScheduled());
                $this->assertPlusMinusOneMinuteOf($testNow->modify('+3 hour')->format('Y-m-d H:i'), $eventLog->getTriggerDate()->format('Y-m-d H:i'));
            },
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerRestrictedStartHour((new \DateTime())->modify('-1 hour'));
        $adjustPointEvent->setTriggerRestrictedStopHour((new \DateTime())->modify('+1 hour'));

        yield 'Points at a relative time: Between future time today will execute immediatelly as the window is open right now' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog): void {
                Assert::assertFalse($eventLog->getIsScheduled());
                $this->assertPlusMinusOneMinuteOf((new \DateTime())->format('Y-m-d H:i'), $eventLog->getTriggerDate()->format('Y-m-d H:i'));
            },
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerInterval(1);
        $adjustPointEvent->setTriggerIntervalUnit('h');
        $adjustPointEvent->setTriggerRestrictedDaysOfWeek([0, 1, 2, 3, 4, 5, 6]);

        yield 'Points at a relative time: One hour interval and All Days' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog): void {
                Assert::assertTrue($eventLog->getIsScheduled());
                Assert::assertEqualsWithDelta(1, $eventLog->getDateTriggered()->diff($eventLog->getTriggerDate())->format('%h'), 1);
            },
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerMode(Event::TRIGGER_MODE_DATE);
        $adjustPointEvent->setTriggerDate((new \DateTime())->modify('+5 hour'));

        yield 'Points at specific date/time' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog): void {
                Assert::assertTrue($eventLog->getIsScheduled());
                Assert::assertEqualsWithDelta(5, $eventLog->getDateTriggered()->diff($eventLog->getTriggerDate())->format('%h'), 1);
            },
        ];

        $triggerHourDate  = (new \DateTime())->modify('+3 hours');
        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerMode(Event::TRIGGER_MODE_INTERVAL);
        $adjustPointEvent->setTriggerHour($triggerHourDate->format('H:00:00'));
        $adjustPointEvent->setTriggerIntervalUnit('d');
        // This must conform the format of the date in the \Mautic\CampaignBundle\Executioner\Scheduler\Mode\Interval::getGroupExecutionDateTime
        $adjustPointEvent->setTriggerRestrictedDaysOfWeek([(new \DateTime())->format('w')]);

        yield 'Schedule the event when Send From is in the future on the selected day when the day is today' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog) use ($triggerHourDate): void {
                Assert::assertTrue($eventLog->getIsScheduled());
                $this->assertPlusMinusOneMinuteOf($triggerHourDate->format('Y-m-d H:00:00'), $eventLog->getTriggerDate()->format('Y-m-d H:00:00'));
            },
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerMode(Event::TRIGGER_MODE_INTERVAL);
        $adjustPointEvent->setTriggerHour('15:00:00');
        $adjustPointEvent->setTriggerIntervalUnit('d');
        $adjustPointEvent->setTriggerRestrictedDaysOfWeek([(new \DateTime('tomorrow'))->format('w')]);

        yield 'Schedule the event when Send From is in the future on the selected day when the day is tomorrow' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog): void {
                Assert::assertTrue($eventLog->getIsScheduled());
                // In this case firstly the time is set as 15:00 if less then that or right now if more, then the date is set to tomorrow.
                // So the range can be tomorrow 15:00 - tomorrow 23:59:59
                Assert::assertLessThanOrEqual((new \DateTime('tomorrow'))->format('Y-m-d 23:59:59'), $eventLog->getTriggerDate()->format('Y-m-d H:i:s'));
                Assert::assertGreaterThanOrEqual((new \DateTime('tomorrow'))->format('Y-m-d 15:00:00'), $eventLog->getTriggerDate()->format('Y-m-d H:i:s'));
            },
        ];

        $triggerHourDate  = (new \DateTime())->modify('-3 hours');
        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerMode(Event::TRIGGER_MODE_INTERVAL);
        $adjustPointEvent->setTriggerHour($triggerHourDate->format('H:00:00'));
        $adjustPointEvent->setTriggerIntervalUnit('d');
        $adjustPointEvent->setTriggerRestrictedDaysOfWeek([(new \DateTime())->format('w')]);

        yield 'Execute the event when Send From is in the past on the selected day when the day is today' => [
            $adjustPointEvent,
            function (LeadEventLog $eventLog) use ($testNow): void {
                Assert::assertFalse($eventLog->getIsScheduled());
                $this->assertPlusMinusOneMinuteOf($testNow->format('Y-m-d H:00:00'), $eventLog->getTriggerDate()->format('Y-m-d H:00:00'));
            },
        ];

        // Need to reset timezone for next date providers call
        date_default_timezone_set($this->originalTimezone);
    }

    /**
     * Avoid flaky test when executing the test right whe the minute is increasing.
     */
    private function assertPlusMinusOneMinuteOf(string $expectedDateString, string $actualDateString): void
    {
        $expectedDate = new \DateTime($expectedDateString);
        $actualDate   = new \DateTime($actualDateString);
        Assert::assertLessThanOrEqual($expectedDate->modify('+1 minute'), $actualDate);
        Assert::assertGreaterThanOrEqual($expectedDate->modify('-2 minute'), $actualDate);
    }
}
