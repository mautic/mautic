<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Command;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Command\RemoveAnonymousContactsCommand;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use PHPUnit\Framework\Assert;

class RemoveAnonymousContactsCommandFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws \Exception
     */
    public function testDeleteAnonymousContactCommand(): void
    {
        $lead    = $this->createAnonymousLead();
        $segment = $this->createSegment();
        $this->createListLead($segment, $lead);

        $campaign = $this->createCampaign();
        $event    = $this->createEvent('Event 1', $campaign);
        $this->createCampaignLead($campaign, $lead);
        $this->createEventLog($lead, $event, $campaign);

        $this->em->flush();

        Assert::assertCount(1, $this->em->getRepository(ListLead::class)->findBy(['list' => $segment]));
        Assert::assertCount(1, $this->em->getRepository(CampaignLead::class)->findBy(['campaign' => $campaign]));
        Assert::assertCount(1, $this->em->getRepository(LeadEventLog::class)->findBy(['campaign' => $campaign, 'lead' => $lead], ['event' => 'ASC']));

        $this->testSymfonyCommand(RemoveAnonymousContactsCommand::COMMAND_NAME);

        Assert::assertCount(1, $this->em->getRepository(Lead::class)->findAll());
        Assert::assertCount(0, $this->em->getRepository(ListLead::class)->findBy(['list' => $segment]));
        Assert::assertCount(0, $this->em->getRepository(CampaignLead::class)->findBy(['campaign' => $campaign]));
        Assert::assertCount(0, $this->em->getRepository(LeadEventLog::class)->findBy(['campaign' => $campaign, 'lead' => $lead], ['event' => 'ASC']));
    }

    private function createAnonymousLead(): Lead
    {
        $lead = new Lead();
        $this->em->persist($lead);

        return $lead;
    }

    private function createSegment(): LeadList
    {
        $segment = new LeadList();
        $segment->setName('Segment A');
        $segment->setAlias('segment-a');
        $segment->setPublicName('segment-a');
        $segment->setFilters([]);
        $this->em->persist($segment);

        return $segment;
    }

    protected function createListLead(LeadList $segment, Lead $lead): void
    {
        $segmentRef = new ListLead();
        $segmentRef->setLead($lead);
        $segmentRef->setList($segment);
        $segmentRef->setDateAdded(new \DateTime());
        $this->em->persist($segmentRef);
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('My campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);

        return $campaign;
    }

    private function createEvent(string $name, Campaign $campaign): Event
    {
        $event = new Event();
        $event->setName($name);
        $event->setCampaign($campaign);
        $event->setType('email.send');
        $event->setEventType('action');
        $event->setTriggerInterval(1);
        $event->setTriggerMode('immediate');
        $this->em->persist($event);

        return $event;
    }

    private function createEventLog(Lead $lead, Event $event, Campaign $campaign): LeadEventLog
    {
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setLead($lead);
        $leadEventLog->setEvent($event);
        $leadEventLog->setCampaign($campaign);
        $this->em->persist($leadEventLog);

        return $leadEventLog;
    }

    protected function createCampaignLead(Campaign $campaign, Lead $lead): CampaignLead
    {
        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);

        return $campaignLead;
    }
}
