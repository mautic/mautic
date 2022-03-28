<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Functional\Executioner\Scheduler\Mode;

use Doctrine\Common\Collections\ArrayCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class IntervalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testBatchReschedulingOneDayAhead(): void
    {
        $campaign = new Campaign();
        $campaign->setName('test campaign');

        $event = new Event();
        $event->setName('test event');
        $event->setEventType(Event::TYPE_ACTION);
        $event->setTriggerMode(Event::TRIGGER_MODE_INTERVAL);
        $event->setType('lead.changepoints');
        $event->setCampaign($campaign);
        $event->setTriggerInterval(20);
        $event->setTriggerIntervalUnit('H');

        $events = new ArrayCollection([$event]);
        $lead   = new Lead();
        $leads  = new ArrayCollection([$lead]);

        $this->em->persist($lead);
        $this->em->persist($campaign);
        $this->em->persist($event);
        $this->em->flush();

        $eventExecutioner = $this->getContainer()->get(EventExecutioner::class);
        \assert($eventExecutioner instanceof EventExecutioner);
        $result = $eventExecutioner->executeEventsForContacts($events, $leads);

        $leadEventLogRepo = $this->em->getRepository(LeadEventLog::class);
        $leadEventLog     = $leadEventLogRepo->findOneBy(['event' => $event->getId()]);
        \assert($leadEventLog instanceof LeadEventLog);
        $triggeredDate = $leadEventLog->getTriggerDate();
        $dateTriggered = $leadEventLog->getDateTriggered();

        Assert::assertNull($result);
        Assert::assertEquals($event->getId(), $leadEventLog->getEvent()->getId());
        Assert::assertEquals($lead->getId(), $leadEventLog->getLead()->getId());
        Assert::assertEquals($campaign->getId(), $leadEventLog->getCampaign()->getId());
        Assert::assertTrue($leadEventLog->getIsScheduled());
        Assert::assertEqualsWithDelta(20, $dateTriggered->diff($triggeredDate)->format('%h'), 1);
    }

    public function testBatchReschedulingZeroIntervalAndDayRestrictions(): void
    {
        $today        = new \DateTimeImmutable();
        $yesterday    = $today->modify('-1 day');
        $scheduledDay = $yesterday->modify('+7 days');

        $campaign = new Campaign();
        $campaign->setName('test campaign');

        $event = new Event();
        $event->setName('test event');
        $event->setEventType(Event::TYPE_ACTION);
        $event->setTriggerMode(Event::TRIGGER_MODE_INTERVAL);
        $event->setType('lead.changepoints');
        $event->setCampaign($campaign);
        $event->setTriggerInterval(0);
        $event->setTriggerIntervalUnit('D');
        $event->setTriggerRestrictedDaysOfWeek([$yesterday->format('w')]);

        $events = new ArrayCollection([$event]);
        $lead   = new Lead();
        $leads  = new ArrayCollection([$lead]);

        $this->em->persist($lead);
        $this->em->persist($campaign);
        $this->em->persist($event);
        $this->em->flush();

        $eventExecutioner = $this->getContainer()->get(EventExecutioner::class);
        \assert($eventExecutioner instanceof EventExecutioner);
        $result = $eventExecutioner->executeEventsForContacts($events, $leads);

        $leadEventLogRepo = $this->em->getRepository(LeadEventLog::class);
        $leadEventLog     = $leadEventLogRepo->findOneBy(['event' => $event->getId()]);
        \assert($leadEventLog instanceof LeadEventLog);
        $triggeredDate = $leadEventLog->getTriggerDate();

        Assert::assertNull($result);
        Assert::assertEquals($event->getId(), $leadEventLog->getEvent()->getId());
        Assert::assertEquals($lead->getId(), $leadEventLog->getLead()->getId());
        Assert::assertEquals($campaign->getId(), $leadEventLog->getCampaign()->getId());
        Assert::assertTrue($leadEventLog->getIsScheduled());
        Assert::assertEquals($triggeredDate->format('Y-m-d'), $scheduledDay->format('Y-m-d'));
    }
}
