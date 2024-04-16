<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;

class CampaignActionJumpToEventWithIntervalTriggerModeFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @dataProvider dataForCampaignWithJumpToEventWithIntervalTriggerMode
     */
    public function testCampaignWithJumpToEventWithIntervalTriggerMode(Event $adjustPointEvent, string $format, int $expected): void
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

        $adjustEventLog = $leadEventLogRepo->findOneBy(['event' => $adjustPointEvent->getId()]);

        $triggeredDate = $adjustEventLog->getTriggerDate();
        $dateTriggered = $adjustEventLog->getDateTriggered();

        $this->assertTrue($adjustEventLog->getIsScheduled());
        $this->assertEqualsWithDelta($expected, $dateTriggered->diff($triggeredDate)->format($format), 1);
    }

    /**
     * @return iterable<mixed>
     */
    public function dataForCampaignWithJumpToEventWithIntervalTriggerMode(): iterable
    {
        $event = new Event();
        $event->setName('Adjust points');
        $event->setEventType(Event::TYPE_ACTION);
        $event->setTriggerMode(Event::TRIGGER_MODE_INTERVAL);
        $event->setType('lead.changepoints');
        $event->setDecisionPath('no');
        $event->setTriggerInterval(0);
        $event->setTriggerIntervalUnit('i');
        $event->setOrder(2);

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerInterval(10);
        $adjustPointEvent->setTriggerIntervalUnit('i');

        yield 'Points Interval with 10 minutes' => [
            $adjustPointEvent,
            '%i',
            10,
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerHour((new \DateTime())->modify('-1 hour')->format('H:i'));

        yield 'Points at a relative time: Scheduled at - before one hour' => [
            $adjustPointEvent,
            '%h',
            23,
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerDate(new \DateTime());
        $adjustPointEvent->setTriggerInterval(1);
        $adjustPointEvent->setTriggerIntervalUnit('H');
        $adjustPointEvent->setTriggerHour((new \DateTime())->modify('-1 hour')->format('H:i'));

        yield 'Points at a relative time: Scheduled at - before one hour with delay of 1 hour' => [
            $adjustPointEvent,
            '%h',
            0,
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerDate(new \DateTime('tomorrow'));
        $adjustPointEvent->setTriggerRestrictedStartHour((new \DateTime('tomorrow'))->modify('+2 hour'));
        $adjustPointEvent->setTriggerRestrictedStopHour((new \DateTime('tomorrow'))->modify('+3 hour'));

        yield 'Points at a relative time: Between future start and stop time on same day' => [
            $adjustPointEvent,
            '%h',
            (int) $adjustPointEvent->getTriggerRestrictedStartHour()->diff(new \DateTime())->format('%h'),
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerRestrictedStartHour((new \DateTime())->modify('-2 hour'));
        $adjustPointEvent->setTriggerRestrictedStopHour((new \DateTime())->modify('-1 hour'));

        yield 'Points at a relative time: Between passed time' => [
            $adjustPointEvent,
            '%h',
            22,
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerRestrictedStartHour((new \DateTime('tomorrow'))->modify('+3 hour'));
        $adjustPointEvent->setTriggerRestrictedStopHour((new \DateTime('tomorrow'))->modify('+4 hour'));

        yield 'Points at a relative time: Between future time' => [
            $adjustPointEvent,
            '%h',
            (int) $adjustPointEvent->getTriggerRestrictedStartHour()->diff(new \DateTime())->format('%h'),
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerInterval(1);
        $adjustPointEvent->setTriggerIntervalUnit('h');
        $adjustPointEvent->setTriggerRestrictedDaysOfWeek([0, 1, 2, 3, 4, 5, 6]);

        yield 'Points at a relative time: One hour interval and All Days' => [
            $adjustPointEvent,
            '%h',
            1,
        ];

        $adjustPointEvent = clone $event;
        $adjustPointEvent->setTriggerMode(Event::TRIGGER_MODE_DATE);
        $adjustPointEvent->setTriggerDate((new \DateTime())->modify('+5 hour'));

        yield 'Points at specific date/time' => [
            $adjustPointEvent,
            '%h',
            5,
        ];
    }
}
