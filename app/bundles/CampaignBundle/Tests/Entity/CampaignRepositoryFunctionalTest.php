<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\CampaignRepository;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\Result\CountResult;
use Mautic\CampaignBundle\Executioner\ContactFinder\Limiter\ContactLimiter;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

class CampaignRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private CampaignRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = self::getContainer()->get('mautic.campaign.repository.campaign');
    }

    public function testGetCountsForPendingContactsWithEmptyData(): void
    {
        $result = $this->repository->getCountsForPendingContacts(
            1,
            [1, 2, 3],
            new ContactLimiter(100, null, null, null, [1, 2, 3])
        );

        Assert::assertEquals(
            new CountResult(0, 0, 0),
            $result,
            'There should not be any match as there are no campaign/lead records.'
        );
    }

    public function testGetCountsForPendingContactsWithoutEventLogs(): void
    {
        $campaign   = $this->createCampaign();
        $eventOne   = $this->createEvent($campaign);
        $eventTwo   = $this->createEvent($campaign);
        $eventThree = $this->createEvent($campaign);
        $leadOne    = $this->createLead($campaign);
        $leadTwo    = $this->createLead($campaign);
        $leadThree  = $this->createLead($campaign);
        $this->em->flush();

        $result = $this->repository->getCountsForPendingContacts(
            $campaign->getId(),
            [$eventOne->getId(), $eventTwo->getId(), $eventThree->getId()],
            new ContactLimiter(100, null, null, null, [$leadOne->getId(), $leadTwo->getId(), $leadThree->getId()])
        );

        Assert::assertEquals(
            new CountResult(3, $leadOne->getId(), $leadThree->getId()),
            $result,
            'All three leads should match as none of them have any event logs.'
        );
    }

    public function testGetCountsForPendingContactsWithEventLogs(): void
    {
        $campaign   = $this->createCampaign();
        $logOne     = $this->createEventLog($campaign);
        $leadOne    = $logOne->getLead();
        $eventOne   = $logOne->getEvent();
        $logTwo     = $this->createEventLog($campaign);
        $leadTwo    = $logTwo->getLead();
        $eventTwo   = $logTwo->getEvent();
        $leadThree  = $this->createLead($campaign);
        $eventThree = $this->createEvent($campaign);
        $this->em->flush();

        $result = $this->repository->getCountsForPendingContacts(
            $campaign->getId(),
            [$eventOne->getId(), $eventTwo->getId(), $eventThree->getId()],
            new ContactLimiter(100, null, null, null, [$leadOne->getId(), $leadTwo->getId(), $leadThree->getId()])
        );

        Assert::assertEquals(
            new CountResult(1, $leadThree->getId(), $leadThree->getId()),
            $result,
            'Only lead three should match as it is the only one who does not have any event log.'
        );
    }

    public function testGetCountsForPendingContactsWithEventLogsWithNonMatchingRotations(): void
    {
        $campaign   = $this->createCampaign();
        $logOne     = $this->createEventLog($campaign);
        $leadOne    = $logOne->getLead();
        $eventOne   = $logOne->getEvent();
        $logTwo     = $this->createEventLog($campaign, $campaignLeadTwo);
        $leadTwo    = $logTwo->getLead();
        $eventTwo   = $logTwo->getEvent();
        $logThree   = $this->createEventLog($campaign);
        $leadThree  = $logThree->getLead();
        $eventThree = $logThree->getEvent();
        $campaignLeadTwo->setRotation($logTwo->getRotation() + 1);
        $this->em->flush();

        $result = $this->repository->getCountsForPendingContacts(
            $campaign->getId(),
            [$eventOne->getId(), $eventTwo->getId(), $eventThree->getId()],
            new ContactLimiter(100, null, null, null, [$leadOne->getId(), $leadTwo->getId(), $leadThree->getId()])
        );

        Assert::assertEquals(
            new CountResult(1, $leadTwo->getId(), $leadTwo->getId()),
            $result,
            'Only lead two should match as it is the only one who has a non-matching rotation.'
        );
    }

    public function testGetCampaignPublishAndVersionData(): void
    {
        $campaign = $this->createCampaign();
        $this->em->flush();

        $result = $this->repository->getCampaignPublishAndVersionData($campaign->getId());

        Assert::assertIsArray($result);
        Assert::assertArrayHasKey('is_published', $result);
        Assert::assertArrayHasKey('version', $result);
        Assert::assertEquals('1', $result['is_published']);
        // Version should be a string representation of an integer
        Assert::assertIsString($result['version']);
        Assert::assertGreaterThanOrEqual('1', $result['version']);
    }

    public function testGetCampaignPublishAndVersionDataWithNonExistentCampaign(): void
    {
        $nonExistentId = 99999;

        $result = $this->repository->getCampaignPublishAndVersionData($nonExistentId);

        Assert::assertEquals([], $result);
    }

    private function createLead(Campaign $campaign, ?CampaignLead &$campaignLead = null): Lead // @phpstan-ignore parameterByRef.unusedType
    {
        $lead = new Lead();
        $this->em->persist($lead);

        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($campaign);
        $campaignLead->setLead($lead);
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);

        return $lead;
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Test campaign');
        $campaign->setIsPublished(true);
        $this->em->persist($campaign);

        return $campaign;
    }

    private function createEvent(Campaign $campaign): Event
    {
        $event = new Event();
        $event->setName('Test event');
        $event->setCampaign($campaign);
        $event->setType('lead.changepoints');
        $event->setEventType(Event::TYPE_ACTION);
        $this->em->persist($event);

        return $event;
    }

    private function createEventLog(Campaign $campaign, ?CampaignLead &$campaignLead = null): LeadEventLog
    {
        $event        = $this->createEvent($campaign);
        $lead         = $this->createLead($campaign, $campaignLead);
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setLead($lead);
        $leadEventLog->setEvent($event);
        $leadEventLog->setTriggerDate(new \DateTime());
        $this->em->persist($leadEventLog);

        return $leadEventLog;
    }
}
