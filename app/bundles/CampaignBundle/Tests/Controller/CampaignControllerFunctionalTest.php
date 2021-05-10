<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller;

use Doctrine\ORM\EntityManager;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignLead;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTest;
use PHPUnit\Framework\Assert;
use Symfony\Component\DomCrawler\Crawler;

class CampaignControllerFunctionalTest extends AbstractCampaignTest
{
    private const CAMPAIGN_NAME = 'Campaign Event Functional test';
    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * index action test.
     */
    public function testIndexAction(): void
    {
        $this->client->request('GET', '/s/campaigns');
        $response = $this->client->getResponse();

        $this->assertSame(200, $response->getStatusCode());

        // check page layout
        $crawler = new Crawler($response->getContent());
        $class   = $crawler->filter('h4.fw-sb')->getNode(0)->getNodePath();
        $this->assertEquals('/html/body/div[4]/div/div/div[1]/div/div[1]/div/div[1]/h4', $class);
    }

    public function testCreateCampaignAction(): void
    {
        $crawler = $this->client->request('GET', '/s/campaigns/new');
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $form = $crawler->selectButton('campaign_buttons_apply')->form();
        $form['campaign[name]']->setValue(self::CAMPAIGN_NAME);

        $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        // get campaign id
        $campaign = $this->em->getRepository(Campaign::class)->findOneBy(['name' => self::CAMPAIGN_NAME]);
        $id       = $campaign->getId();

        // campaign edit page
        $crawler = $this->client->request('GET', '/s/campaigns/edit/'.$id);
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('campaign_buttons_apply')->form();

        $form['campaign[name]']->setValue('Campaign new name');

        $this->client->submit($form);

        $this->assertTrue($this->client->getResponse()->isOk());

        $campaign = $this->em->getRepository(Campaign::class)->getEntity($id);
        $this->assertEquals('Campaign new name', $campaign->getName());

        // clone campaign
        $this->client->request('GET', '/s/campaigns/clone/'.$id);
        $this->assertTrue($this->client->getResponse()->isOk());

        $campaign = $this->em->getRepository(Campaign::class)->findOneBy(['name' => 'Campaign new name - clone']);
        $cloneId  = $campaign->getId();
        $this->assertTrue($cloneId > 0);
        $this->assertTrue($cloneId !== $id);

        // and finally delete campaign
        $this->client->request('POST', '/s/campaigns/delete/'.$id);
        // redirect
        $this->assertTrue($this->client->getResponse()->isRedirect());

        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isOk());

        $campaign = $this->em->getRepository(Campaign::class)->findOneBy(['name' => 'Campaign new name']);
        $this->assertNull($campaign);

        $this->client->request('POST', '/s/campaigns/delete/'.$cloneId);
        $this->client->followRedirect();
        $this->assertTrue($this->client->getResponse()->isOk());

        $campaign = $this->em->getRepository(Campaign::class)->findOneBy(['name' => 'Campaign new name - clone']);
        $this->assertNull($campaign);
    }

    public function testViewActionHtmlVariant(): void
    {
        $campaign = $this->saveSomeCampaignLeadEventLogs();

        // check contact count
        $this->client->request('GET', sprintf('/s/campaigns/view/%s', $campaign->getId()));
        $response = $this->client->getResponse();
        Assert::assertTrue($response->isOk());

        $crawler = new Crawler($response->getContent());
        $node    = $crawler->filter('button:contains("View Details")')->getNode(0);

        // extract "data-stats-event-id" from a button
        $eventId = $node->attributes->getNamedItem('data-stats-event-id')->value;
        $event   = $this->em->getRepository(Event::class)->find($eventId);
        $this->assertSame(Event::TYPE_DECISION, $event->getEventType());

        // check data is OK
        $this->assertSame(2, $event->getStats()['count']);
        $this->assertSame(0, $event->getStats()['failed']);
        $this->assertSame(2, $event->getStats()['processed']);
        $this->assertSame(100, $event->getStats()['progress']);

        // check contact count
        $campaignId = $campaign->getId();
        // "pending" contacts
        $pendingContactsCount = $this->campaignModel->getRepository()->getCampaignLeadCount($campaignId, null, []);
        $this->assertSame(2, $pendingContactsCount);

        // all campaign contacts
        $allContactsCount = $this->campaignModel->getRepository()->getCampaignLeadCount($campaignId);
        $this->assertSame(2, $allContactsCount);

        // Manually remove contact to make sure the counts can filter them out
        $campaignLeadRepo = $this->campaignModel->getCampaignLeadRepository();
        $contact          = $campaignLeadRepo->findOneBy(['lead' => 2, 'campaign' => $campaignId]);
        $contact->setManuallyRemoved(true);
        $campaignLeadRepo->saveEntity($contact);

        // Contacts with at least one positive event log
        $logsCount = $this->campaignModel->getRepository()->getCampaignLeadCount($campaignId, null, []);
        $this->assertSame(1, $logsCount);

        // All contacts except those who were manually removed
        $allContactsCount = $this->campaignModel->getRepository()->getCampaignLeadCount($campaignId);
        $this->assertSame(1, $allContactsCount);

        // check metrics action
        $from = date('Y-m-d', strtotime('-2 months'));
        $to   = date('Y-m-d', strtotime('-1 month'));

        $stats = $this->campaignModel->getCampaignMetricsLineChartData(
            null,
            new \DateTime('2020-10-21'),
            new \DateTime('2020-11-22'),
            null,
            ['groupBy' => 'h']
        );

        $this->checkCampaignMetrics($stats);
    }

    private function checkCampaignMetrics(array $stats): void
    {
        $labels = $stats['labels'];
        $this->assertCount(792, $labels);
        foreach ($labels as $label) {
            $this->assertMatchesRegularExpression('/^[A-Z]{3} \d\d?, \d\d?:/', $label);
        }

        $datasets = $stats['datasets'];
        $this->assertCount(4, $datasets);

        $pending    = $datasets[0];
        $completed  = $datasets[1];
        $notriggers = $datasets[2];
        $failed     = $datasets[3];

        $this->assertEquals('Pending', $pending['label']);
        $this->assertEquals('rgba(219, 136, 14, 0.8)', $pending['backgroundColor']);
        $this->assertEquals('rgba(219, 136, 14, 0.8)', $pending['borderColor']);
        $this->assertEquals(false, $pending['fill']);

        $this->assertEquals('Completed', $completed['label']);
        $this->assertEquals('rgba(44, 151, 71, 0.8)', $completed['backgroundColor']);
        $this->assertEquals('rgba(44, 151, 71, 0.8)', $completed['borderColor']);
        $this->assertEquals(false, $completed['fill']);

        $this->assertEquals('Triggered but not executed', $notriggers['label']);
        $this->assertEquals('rgba(134, 65, 244, 0.8)', $notriggers['backgroundColor']);
        $this->assertEquals('rgba(134, 65, 244, 0.8)', $notriggers['borderColor']);
        $this->assertEquals(false, $notriggers['fill']);

        $this->assertEquals('Failed', $failed['label']);
        $this->assertEquals('rgba(188, 38, 28, 0.8)', $failed['backgroundColor']);
        $this->assertEquals('rgba(188, 38, 28, 0.8)', $failed['borderColor']);
        $this->assertEquals(false, $failed['fill']);

        $this->assertCount(792, $pending['data']);
        $this->assertCount(792, $completed['data']);
        $this->assertCount(792, $notriggers['data']);
        $this->assertCount(792, $failed['data']);

        $this->assertEquals(0, array_sum($completed['data']));
        $this->assertEquals(0, array_sum($notriggers['data']));
        $this->assertEquals(0, array_sum($failed['data']));
    }

    public function testCampaignEventLeadLogs(): void
    {
        $campaign   = $this->saveSomeCampaignLeadEventLogs(true);
        $campaignId = $campaign->getId();
        $eventId    = $campaign->getAllEvents()['decision'][0]->getId();

        // Check the stats based on different date ranges
        $this->client->request('GET', sprintf('s/campaigns/events/stats/%d/%s/%s', $eventId, '2020-10-24 13:20:44', '2020-11-22 16:34:00'));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $body = json_decode($response->getContent(), true);
        $this->assertCount(1, $body['related']);
        $this->assertCount(2, $body['data']);

        $this->client->request('GET', sprintf('s/campaigns/events/stats/%d/%s/%s', $eventId, '2020-10-24 13:20:44', '2020-10-24 13:20:44'));
        $response = $this->client->getResponse();
        $this->assertTrue($response->isOk());
        $body = json_decode($response->getContent(), true);
        $this->assertCount(0, $body['related']);
        $this->assertCount(0, $body['data']);
    }

    public function testCampaignViewEvents(): void
    {
        $from     = date('Y-m-d', strtotime('-2 months'));
        $to       = date('Y-m-d', strtotime('-1 month'));
        $campaign = $this->saveSomeCampaignLeadEventLogs();
        $this->client->request('GET', sprintf('s/campaigns/event/stats/%d/%s/%s', $campaign->getId(), '2020-11-20 16:34:00', '2020-11-22 16:34:00'));
        $response = $this->client->getResponse();

        $body     = json_decode($response->getContent(), true);
        self::assertCount(2, $body);
        self::arrayHasKey('actions');
        self::assertStringContainsString('100% 2 0 Event A mautic.campaign.type.a 100% 2 0 Event B mautic.campaign.type.b', preg_replace('/\s+/', ' ', strip_tags($body['actions'])));
    }

    public function testCampaignViewGraph(): void
    {
        $campaign = $this->saveSomeCampaignLeadEventLogs();
        $this->client->request('GET', sprintf('s/campaigns/graph/%d/%s/%s', $campaign->getId(), '2020-10-21', '2020-11-22'));
        $response = $this->client->getResponse();

        $body = json_decode($response->getContent(), true);
        self::assertCount(1, $body);
        self::arrayHasKey('graph');
        self::assertStringContainsString('cy.elements', $body['graph']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->em            = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');
        $this->campaignModel = self::$kernel->getContainer()->get('mautic.campaign.model.campaign');
    }

    /**
     * Create a campaign, two events, and two contacts who moved through those events.
     *
     * @throws \Doctrine\DBAL\Exception
     */
    private function saveSomeCampaignLeadEventLogs(bool $decision = false): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('Campaign event log test');
        $campaign->setCanvasSettings(['settings' => [], 'nodes' => []]);

        $now    = new \DateTime();
        $events = [];
        // Create action event A
        $events['a'] = new Event();

        if ($decision) {
            $events['a']->setEventType(Event::TYPE_DECISION);
            $events['a']->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
            $events['a']->setName('Event A');
            $events['a']->setType('mautic.campaign.type.a');

            // Create action event C
            $events['c'] = new Event();
            $events['c']->setEventType(Event::TYPE_DECISION);
            $events['c']->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
            $events['c']->setName('Event C');
            $events['c']->setType('mautic.campaign.type.c');

            $events['a']->setChildrenFlowIds([3]);
            $events['a']->setPositionX(100);
            $events['a']->setPositionY(100);
            $events['c']->setParentId($events['a']->getId());
            $events['c']->setPositionX(200);
            $events['c']->setPositionY(200);
        } else {
            $events['a']->setEventType(Event::TYPE_ACTION);
            $events['a']->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
            $events['a']->setName('Event A');
            $events['a']->setType('mautic.campaign.type.a');
        }

        // Create action event B
        $events['b'] = new Event();
        $events['b']->setEventType(Event::TYPE_ACTION);
        $events['b']->setTriggerMode(Event::TRIGGER_MODE_IMMEDIATE);
        $events['b']->setName('Event B');
        $events['b']->setType('mautic.campaign.type.b');

        foreach ($events as $key => $event) {
            $campaign->addEvent($key, $event);
            $this->em->persist($event);
        }

        // Save campaign
        $this->campaignModel->saveEntity($campaign);

        // Create a lead
        $lead1 = $this->createLeads(1)[0];
        $lead2 = $this->createLeads(1, 2)[0];

        // Add lead to the campaign
        $campaignLead1 = new CampaignLead();
        $campaignLead1->setCampaign($campaign);
        $campaignLead1->setLead($lead1);
        $campaignLead1->setDateAdded($now);
        $this->em->persist($campaignLead1);

        $campaignLead2 = new CampaignLead();
        $campaignLead2->setCampaign($campaign);
        $campaignLead2->setLead($lead2);
        $campaignLead2->setDateAdded($now);
        $this->em->persist($campaignLead2);

        // Trigger events for lead 1
        $this->addLeadEventLog($lead1, $events['a'], true, $campaign);
        $this->addLeadEventLog($lead1, $events['b'], true, $campaign);

        // Trigger events for lead 2
        $this->addLeadEventLog($lead2, $events['a'], true, $campaign);
        $this->addLeadEventLog($lead2, $events['b'], true, $campaign);

        // Make sure that events counts are there
        foreach ($events as $event) {
            $this->preloadEventCounts($event);
        }

        $this->em->flush();
        $this->em->clear();

        return $campaign;
    }

    private function addLeadEventLog(object $lead, Event $event, bool $isScheduled, Campaign $campaign): void
    {
        $log = new LeadEventLog();
        $log->setLead($lead);
        $log->setEvent($event);
        $log->setCampaign($campaign);
        $log->setDateTriggered(new \DateTime());
        $log->setIsScheduled($isScheduled);
        $this->em->persist($log);
    }

    private function preloadEventCounts(Event $event): void
    {
        $qb     = $this->em->getConnection()->createQueryBuilder();
        $counts = $qb->select('COUNT(*) as count, SUM(ll.is_scheduled) as scheduled, SUM(ll.triggered) as triggered')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_lead_event_log', 'll')
            ->where(
                $qb->expr()->and(
                    $qb->expr()->eq('ll.event_id', $event->getId())
                )
            )->execute()->fetchAssociative();

        $event->setTriggerCount($counts['triggered']);
        $event->setExecutionCount($counts['count'] - $counts['scheduled']);
    }
}
