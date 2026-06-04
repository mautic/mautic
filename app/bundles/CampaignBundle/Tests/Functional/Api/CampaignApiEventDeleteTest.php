<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional\Api;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;

final class CampaignApiEventDeleteTest extends MauticMysqlTestCase
{
    public function testEventAndSourceDeleteViaPutReproducesApiBug(): void
    {
        $campaign = new Campaign();
        $campaign->setName('Test Campaign');

        $segment = new LeadList();
        $segment->setName('Test');
        $segment->setPublicName('Test');
        $segment->setAlias('test');

        // Create events
        $event1 = new Event();
        $event1->setName('Event 1');
        $event1->setType('email.send');
        $event1->setEventType('action');
        $event1->setCampaign($campaign);

        $event2 = new Event();
        $event2->setName('Event 2');
        $event2->setType('lead.changescore');
        $event2->setEventType('action');
        $event2->setCampaign($campaign);

        $campaign->addEvent('event1', $event1);
        $campaign->addEvent('event2', $event2);
        $campaign->addList($segment);

        $this->em->persist($segment);
        $this->em->persist($event1);
        $this->em->persist($event2);
        $this->em->persist($campaign);
        $this->em->flush();
        $this->assertGreaterThan(0, $campaign->getId(), 'Campaign should be saved with an ID');
        $this->client->request('GET', '/api/campaigns');
        $this->assertResponseIsSuccessful();

        // Step 1: GET the campaign (like API Library test does)
        $this->client->request('GET', "/api/campaigns/{$campaign->getId()}");
        $this->assertResponseIsSuccessful();

        $data   = json_decode($this->client->getResponse()->getContent(), true);
        $events = $data['campaign']['events'];

        // Step 2: Remove last event with array_pop (exactly like API Library test)
        array_pop($events);

        // Step 3: PUT request to update campaign (this triggers the SQL error)
        $payload = [
            'name'   => $campaign->getName(),
            'events' => $events,
            'lists'  => [['id' => $segment->getId()]],
        ];

        $this->client->request('PUT', "/api/campaigns/{$campaign->getId()}/edit", $payload);
        $this->assertResponseIsSuccessful();
    }
}
