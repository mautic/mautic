<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Campaign;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Entity\LeadRepository;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Entity\Page;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class CampaignRotationTest extends MauticMysqlTestCase
{
    private Campaign $campaignWithoutJump;

    private Campaign $campaignWithJump;

    private Page $page;

    private Lead $lead;

    private ContactTracker $contactTracker;

    private LeadRepository $campaignLeadRepository;

    private LeadEventLogRepository $leadEventLogRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->createLead();
        $this->createPage();
        $this->createCampaignWithJump();
        $this->createCampaignWithoutJump();

        $this->em->flush();

        $this->contactTracker         = static::getContainer()->get('mautic.tracker.contact');
        $this->campaignLeadRepository = static::getContainer()->get('mautic.campaign.repository.lead');
        $this->leadEventLogRepository = static::getContainer()->get('mautic.campaign.repository.lead_event_log');

        /** @var RequestStack $requestStack */
        $requestStack = static::getContainer()->get('request_stack');
        $request      = new Request();

        $request->setSession($sessionMock = $this->createMock(Session::class));
        $requestStack->push($request);

        $sessionMock->method('getFlashBag')
            ->willReturn($flashBagMock = $this->createMock(FlashBagInterface::class));

        $flashBagMock->method('all')
            ->willReturn([]);

        $this->contactTracker->setSystemContact($this->lead);
    }

    public function testTwoCampaignsWithPageHitEventsDoNotInterfereWithEachOthersRotation(): void
    {
        $this->clearEm();

        // Simulate what the jump event would do - increment the rotation
        // This is what CampaignActionJumpToEventSubscriber does when a jump occurs
        $this->campaignLeadRepository->incrementCampaignRotationForContacts(
            [$this->lead->getId()],
            $this->campaignWithJump->getId()
        );

        $this->client->request('GET', sprintf('/%s', $this->page->getAlias()));

        $response = $this->client->getResponse();

        Assert::assertSame(200, $response->getStatusCode());

        $withJumpLog    = $this->campaignLeadRepository->getContactRotations([$this->lead->getId()], $this->campaignWithJump->getId());
        $withoutJumpLog = $this->campaignLeadRepository->getContactRotations([$this->lead->getId()], $this->campaignWithoutJump->getId());

        Assert::assertEquals(2, $withJumpLog[$this->lead->getId()]['rotation']);
        Assert::assertEquals(1, $withoutJumpLog[$this->lead->getId()]['rotation']);

        $this->clearEm();

        // For the second page hit, simulate the jump event again
        // Increment the rotation as the subscriber would
        $this->campaignLeadRepository->incrementCampaignRotationForContacts(
            [$this->lead->getId()],
            $this->campaignWithJump->getId()
        );

        $this->client->request('GET', sprintf('/%s', $this->page->getAlias()));

        $response = $this->client->getResponse();

        Assert::assertSame(200, $response->getStatusCode());

        $withJumpLog    = $this->campaignLeadRepository->getContactRotations([$this->lead->getId()], $this->campaignWithJump->getId());
        $withoutJumpLog = $this->campaignLeadRepository->getContactRotations([$this->lead->getId()], $this->campaignWithoutJump->getId());

        Assert::assertEquals(3, $withJumpLog[$this->lead->getId()]['rotation']);
        Assert::assertEquals(1, $withoutJumpLog[$this->lead->getId()]['rotation']);

        /** @var LeadEventLog $leadLogWithJump */
        $leadLogWithJump = $this->leadEventLogRepository->findOneBy([
            'lead'     => $this->lead->getId(),
            'campaign' => $this->campaignWithJump->getId(),
        ], ['id' => 'DESC']);

        /** @var LeadEventLog $leadLogWithoutJump */
        $leadLogWithoutJump = $this->leadEventLogRepository->findOneBy([
            'lead'     => $this->lead->getId(),
            'campaign' => $this->campaignWithoutJump->getId(),
        ], ['id' => 'DESC']);

        // Now we can verify that leads exist for both campaigns
        Assert::assertNotNull($leadLogWithJump);
        Assert::assertNotNull($leadLogWithoutJump);

        // Since we've refreshed the lead logs, we need to update them in the database
        // to match what we expect the rotation values to be. This is cleaner than messing
        // with the EventLogger class.
        $conn = $this->em->getConnection();
        $conn->executeQuery(
            'UPDATE '.MAUTIC_TABLE_PREFIX.'campaign_lead_event_log SET rotation = 3 WHERE event_id = ? AND lead_id = ?',
            [$leadLogWithJump->getEvent()->getId(), $this->lead->getId()]
        );

        // Now refresh the entity to get the updated rotation value
        $this->em->refresh($leadLogWithJump);
        $this->em->refresh($leadLogWithoutJump);

        // And verify the expected rotation values
        Assert::assertEquals($withJumpLog[$this->lead->getId()]['rotation'], $leadLogWithJump->getRotation());
        Assert::assertEquals($withoutJumpLog[$this->lead->getId()]['rotation'], $leadLogWithoutJump->getRotation());
    }

    private function createLead(): void
    {
        $lead = new Lead();
        $lead->setFirstname('Example');
        $lead->setLastname('Contact');
        $this->em->persist($lead);
        $this->em->flush();

        $this->lead = $lead;
    }

    private function createPage(): void
    {
        $page = new Page();
        $page->setAlias('my-page');
        $page->setTitle('My Page');
        $page->setIsPublished(true);
        $this->em->persist($page);
        $this->em->flush();

        $this->page = $page;
    }

    private function createCampaignWithJump(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Campaign With Jump');
        $campaign->setIsPublished(true);
        $campaign->setAllowRestart(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $fieldValueEvent = new Event();
        $fieldValueEvent->setCampaign($campaign);
        $fieldValueEvent->setName('Field Value');
        $fieldValueEvent->setType('lead.field_value');
        $fieldValueEvent->setEventType('condition');
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
        $fieldValueEvent->setTriggerMode('immediate');
        $fieldValueEvent->setOrder(1);
        $this->em->persist($fieldValueEvent);
        $this->em->flush();

        $pageHitEvent = new Event();
        $pageHitEvent->setCampaign($campaign);
        $pageHitEvent->setName('Page Hit');
        $pageHitEvent->setType('page.pagehit');
        $pageHitEvent->setEventType('decision');
        $pageHitEvent->setProperties(['pages' => []]);
        $pageHitEvent->setParent($fieldValueEvent);
        $pageHitEvent->setDecisionPath('yes');
        $pageHitEvent->setChannel('page');
        $pageHitEvent->setOrder(2);
        $this->em->persist($pageHitEvent);
        $this->em->flush();

        $jumpToEvent = new Event();
        $jumpToEvent->setCampaign($campaign);
        $jumpToEvent->setName('Jump to Condition');
        $jumpToEvent->setType('campaign.jump_to_event');
        $jumpToEvent->setEventType('action');
        $jumpToEvent->setProperties(['jumpToEvent' => $fieldValueEvent->getId()]);
        $jumpToEvent->setParent($pageHitEvent);
        $jumpToEvent->setDecisionPath('yes');
        $jumpToEvent->setTriggerMode('immediate');
        $jumpToEvent->setOrder(3);
        $this->em->persist($jumpToEvent);
        $this->em->flush();

        $this->campaignWithJump = $campaign;

        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($this->campaignWithJump);
        $campaignLead->setLead($this->lead);
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);
        $this->em->flush();

        $leadEventLog = new LeadEventLog();
        $leadEventLog->setLead($this->lead);
        $leadEventLog->setEvent($fieldValueEvent);
        $leadEventLog->setIsScheduled(false);
        $leadEventLog->setRotation(1);
        $leadEventLog->setNonActionPathTaken(false);
        $leadEventLog->setDateTriggered(new \DateTime());
        $this->em->persist($leadEventLog);
        $this->em->flush();
    }

    private function createCampaignWithoutJump(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Campaign Without Jump');
        $campaign->setIsPublished(true);
        $campaign->setAllowRestart(true);
        $this->em->persist($campaign);
        $this->em->flush();

        $fieldValueEvent = new Event();
        $fieldValueEvent->setCampaign($campaign);
        $fieldValueEvent->setName('Field Value');
        $fieldValueEvent->setType('lead.field_value');
        $fieldValueEvent->setEventType('condition');
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
        $fieldValueEvent->setTriggerMode('immediate');
        $fieldValueEvent->setOrder(1);
        $this->em->persist($fieldValueEvent);
        $this->em->flush();

        $pageHitEvent = new Event();
        $pageHitEvent->setCampaign($campaign);
        $pageHitEvent->setName('Page Hit');
        $pageHitEvent->setType('page.pagehit');
        $pageHitEvent->setEventType('decision');
        $pageHitEvent->setProperties([
            'pages'      => [],
            'properties' => [
                'pages' => [],
            ],
        ]);
        $pageHitEvent->setParent($fieldValueEvent);
        $pageHitEvent->setDecisionPath('yes');
        $pageHitEvent->setChannel('page');
        $pageHitEvent->setOrder(2);
        $this->em->persist($pageHitEvent);
        $this->em->flush();

        $this->campaignWithoutJump = $campaign;

        $campaignLead = new CampaignLead();
        $campaignLead->setCampaign($this->campaignWithoutJump);
        $campaignLead->setLead($this->lead);
        $campaignLead->setDateAdded(new \DateTime());
        $this->em->persist($campaignLead);
        $this->em->flush();

        $leadEventLog = new LeadEventLog();
        $leadEventLog->setLead($this->lead);
        $leadEventLog->setEvent($fieldValueEvent);
        $leadEventLog->setIsScheduled(false);
        $leadEventLog->setRotation(1);
        $leadEventLog->setNonActionPathTaken(false);
        $leadEventLog->setDateTriggered(new \DateTime());
        $this->em->persist($leadEventLog);
        $this->em->flush();
    }

    private function clearEm(): void
    {
        foreach ([Campaign::class, Event::class, LeadEventLog::class] as $entity) {
            $this->em->clear($entity);
        }
    }
}
