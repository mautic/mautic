<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller\Api;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;

final class EventLogApiControllerTest extends MauticMysqlTestCase
{
    public function testBatchEditEventsPut(): void
    {
        $contact1 = new Lead();
        $contact1->setEmail('johana@doe.nohama');

        $contact2 = new Lead();
        $contact2->setEmail('johana@doe.mohana');

        $contact3 = new Lead();
        $contact3->setEmail('johana@doe.monana');

        $campaign = new Campaign();
        $campaign->setName('Test Campaign');

        $campaignMember1 = new CampaignMember();
        $campaignMember1->setLead($contact1);
        $campaignMember1->setCampaign($campaign);
        $campaignMember1->setManuallyAdded(true);
        $campaignMember1->setDateAdded(new \DateTime());

        $campaignMember2 = new CampaignMember();
        $campaignMember2->setLead($contact2);
        $campaignMember2->setCampaign($campaign);
        $campaignMember2->setManuallyAdded(true);
        $campaignMember2->setDateAdded(new \DateTime());

        $event1 = new Event();
        $event1->setCampaign($campaign);
        $event1->setType('lead.changepoints');
        $event1->setEventType('action');
        $event1->setName('Test Event 1');

        $event2 = new Event();
        $event2->setCampaign($campaign);
        $event2->setType('lead.changepoints');
        $event2->setEventType('action');
        $event2->setName('Test Event 2');

        $event3 = new Event();
        $event3->setCampaign($campaign);
        $event3->setType('asset.download');
        $event3->setEventType('decision');
        $event3->setName('Test Event 3');

        $campaign->addEvent(0, $event1);
        $campaign->addEvent(1, $event2);
        $campaign->addEvent(1, $event3);

        $this->em->persist($contact1);
        $this->em->persist($contact2);
        $this->em->persist($contact3);
        $this->em->persist($event1);
        $this->em->persist($event2);
        $this->em->persist($event3);
        $this->em->persist($campaign);
        $this->em->persist($campaignMember1);
        $this->em->persist($campaignMember2);
        $this->em->flush();
        $this->em->clear();

        $payload = [
            // This will fail because it already has dateTriggered.
            [
                'contactId'     => $contact1->getId(),
                'eventId'       => $event1->getId(),
                'dateTriggered' => '2016-01-10 00:00:00',
            ],
            [
                'contactId'   => $contact2->getId(),
                'eventId'     => $event1->getId(),
                'triggerDate' => '2017-01-10 00:00:00',
            ],
            [
                'contactId'   => $contact1->getId(),
                'eventId'     => $event2->getId(),
                'triggerDate' => '2016-01-11 00:00:00',
            ],
            [
                'contactId'   => $contact2->getId(),
                'eventId'     => $event2->getId(),
                'triggerDate' => '2016-01-11 00:00:00',
            ],
            // This will fail because this contact isn't a campaign member.
            [
                'contactId'   => $contact3->getId(),
                'eventId'     => $event2->getId(),
                'triggerDate' => '2017-01-10 00:00:00',
            ],
            // This will fail because decision cannot be scheduled.
            [
                'contactId'   => $contact1->getId(),
                'eventId'     => $event3->getId(),
                'triggerDate' => '2016-01-11 00:00:00',
            ],
        ];

        $this->client->request('PUT', '/api/campaigns/events/batch/edit', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        Assert::assertCount(1, $response['events'][$event1->getId()]['contactLog']);
        Assert::assertCount(2, $response['events'][$event2->getId()]['contactLog']);
        Assert::assertCount(0, $response['events'][$event3->getId()]['contactLog']);

        $errorMessages = array_map(
            fn (array $error) => $error['message'],
            $response['errors']
        );

        Assert::assertContains("The event {$event1->getId()} in the campaign {$campaign->getId()} has already been executed at 2016-01-10T00:00:00+00:00 for the contact {$contact2->getId()}.", $errorMessages);
        Assert::assertContains("The contact {$contact3->getId()} is not in the campaign {$campaign->getId()}.", $errorMessages);
        Assert::assertContains("A decision type event cannot be scheduled. Event: {$event3->getId()}, campaign: {$campaign->getId()}, contact: {$contact1->getId()}.", $errorMessages);
    }
}
