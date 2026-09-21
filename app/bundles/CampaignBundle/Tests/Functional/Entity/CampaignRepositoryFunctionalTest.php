<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Entity;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;

final class CampaignRepositoryFunctionalTest extends MauticMysqlTestCase
{
    public function testGetCampaignsSegmentShare(): void
    {
        // Create contacts
        $contacts = [];
        foreach (range(1, 5) as $index) {
            $contacts[$index] = $this->createLead(sprintf('user-%s@example.com', $index));
        }

        // Create segments and add contacts
        $segment = $this->createSegment();
        $this->addContactsToSegment($segment, $contacts);

        // Create campaign and add contacts
        $campaign = $this->createCampaign();
        $this->addContactToCampaign($campaign, $contacts);

        // Call getCampaignsSegmentShare
        /** @var CampaignRepository $campaignRepo */
        $campaignRepo = $this->em->getRepository(Campaign::class);

        $shareDetails = $campaignRepo->getCampaignsSegmentShare($segment->getId(), [$campaign->getId()]);

        $this->assertCount(1, $shareDetails);
        $this->assertSame('Campaign name', $shareDetails[0]['name']);
        $this->assertSame('100.0', $shareDetails[0]['segmentCampaignShare']);
    }

    private function createLead(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);

        $this->em->persist($lead);

        return $lead;
    }

    private function createSegment(): LeadList
    {
        $segment = new LeadList();
        $segment->setName('Segment 1');
        $segment->setPublicName('Segment 1');
        $segment->setAlias('alias');

        $this->em->persist($segment);

        return $segment;
    }

    /**
     * @param Lead[] $leads
     */
    private function addContactsToSegment(LeadList $segment, array $leads): void
    {
        foreach ($leads as $lead) {
            $listLead = new ListLead();
            $listLead->setLead($lead);
            $listLead->setList($segment);
            $listLead->setDateAdded(new \DateTime());

            $this->em->persist($listLead);
        }

        $this->em->flush();
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Campaign name');
        $campaign->setIsPublished(true);

        $this->em->persist($campaign);
        $this->em->flush();

        $event = new Event();
        $event->setCampaign($campaign);
        $event->setName('Adjust contact points');
        $event->setType('lead.changepoints');
        $event->setEventType('action');
        $event->setTriggerInterval(20);
        $event->setTriggerIntervalUnit('H');

        $this->em->persist($campaign);
        $this->em->flush();

        $campaign->addEvent(0, $event);

        return $campaign;
    }

    /**
     * @param Lead[] $contacts
     */
    private function addContactToCampaign(Campaign $campaign, array $contacts): void
    {
        foreach ($contacts as $contact) {
            $ref = new CampaignLead();
            $ref->setCampaign($campaign);
            $ref->setLead($contact);
            $ref->setDateAdded(new \DateTime());

            $this->em->persist($ref);
        }

        $this->em->flush();
    }
}
