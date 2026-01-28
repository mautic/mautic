<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Entity;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class LeadEventLogRepositoryTest extends MauticMysqlTestCase
{
    private LeadEventLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->em->getRepository(LeadEventLog::class);
    }

    public function testThatRemoveEventLogsByCampaignIdMethodRemovesLogs(): void
    {
        $campaign = $this->createCampaign();
        $event    = $this->createEvent($campaign);
        $this->createEventLog($campaign, $event);
        $this->createEventLog($campaign, $event);
        $this->createEventLog($campaign, $event);
        $this->em->flush();

        Assert::assertCount(3, $this->repository->findAll());
        $this->repository->removeEventLogsByCampaignId($campaign->getId());
        Assert::assertCount(0, $this->repository->findAll());
    }

    public function testMarkEventLogsQueued(): void
    {
        $campaign = $this->createCampaign();
        $event    = $this->createEvent($campaign);
        $log1     = $this->createEventLog($campaign, $event);
        $log2     = $this->createEventLog($campaign, $event);
        $log3     = $this->createEventLog($campaign, $event);
        $this->em->flush();

        Assert::assertCount(3, $this->repository->findAll());
        Assert::assertEmpty($log1->getDateQueued());
        Assert::assertEmpty($log2->getDateQueued());
        Assert::assertEmpty($log3->getDateQueued());

        $this->repository->markEventLogsQueued([(string) $log1->getId(), (string) $log3->getId()]);
        $this->em->refresh($log1);
        $this->em->refresh($log2);
        $this->em->refresh($log3);

        Assert::assertNotEmpty($log1->getDateQueued());
        Assert::assertEmpty($log2->getDateQueued());
        Assert::assertNotEmpty($log3->getDateQueued());
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Test');
        $this->em->persist($lead);

        return $lead;
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Campaign');
        $this->em->persist($campaign);

        return $campaign;
    }

    private function createEvent(Campaign $campaign): Event
    {
        $event = new Event();
        $event->setName('Event');
        $event->setCampaign($campaign);
        $event->setType('page.devicehit');
        $event->setEventType(Event::TYPE_DECISION);
        $this->em->persist($event);

        return $event;
    }

    private function createEventLog(Campaign $campaign, ?Event $event = null): LeadEventLog
    {
        $event        = $event ?: $this->createEvent($campaign);
        $lead         = $this->createLead();
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setLead($lead);
        $leadEventLog->setEvent($event);
        $leadEventLog->setTriggerDate(new \DateTime());
        $leadEventLog->setIsScheduled(true);
        $this->em->persist($leadEventLog);

        return $leadEventLog;
    }
}
