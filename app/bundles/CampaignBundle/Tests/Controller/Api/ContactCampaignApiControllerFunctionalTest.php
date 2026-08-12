<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller\Api;

use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

#[Group('database')]
final class ContactCampaignApiControllerFunctionalTest extends AbstractCampaignTestCase
{
    public function testContactCampaignApiEndpoints(): void
    {
        $campaign = $this->saveSomeCampaignLeadEventLogs();
        $contact  = new Lead();
        $contact->setEmail('campaign@tester.email');

        $this->em->persist($contact);
        $this->em->flush();

        $campaignMemberRepository = $this->em->getRepository(CampaignMember::class);

        // Add the contact to the campaign.
        $this->client->request(Request::METHOD_POST, "/api/campaigns/{$campaign->getId()}/contact/{$contact->getId()}/add");
        $clientResponse = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $this->assertSame('{"success":1}', $clientResponse->getContent());

        // Assert that the campaign member was really added.
        /** @var CampaignMember[] $campaignMembers */
        $campaignMembers = $campaignMemberRepository->findBy(['lead' => $contact->getId(), 'campaign' => $campaign->getId()]);
        $this->assertCount(1, $campaignMembers);
        $this->assertTrue($campaignMembers[0]->getManuallyAdded());
        $this->assertFalse($campaignMembers[0]->getManuallyRemoved());

        // Get the contact's campaigns.
        $this->client->request(Request::METHOD_GET, "/api/contacts/{$contact->getId()}/campaigns");
        $clientResponse = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $body = json_decode($clientResponse->getContent(), true);
        $this->assertSame(1, $body['total'], (string) $clientResponse->getContent());
        $this->assertSame($campaign->getId(), $body['campaigns'][$campaign->getId()]['id'], (string) $clientResponse->getContent());
        $this->assertSame($campaign->getName(), $body['campaigns'][$campaign->getId()]['name'], (string) $clientResponse->getContent());
        $this->assertNotEmpty($body['campaigns'][$campaign->getId()]['dateAdded'], $clientResponse->getContent());
        $this->assertFalse($body['campaigns'][$campaign->getId()]['manuallyRemoved'], $clientResponse->getContent());
        $this->assertTrue($body['campaigns'][$campaign->getId()]['manuallyAdded'], $clientResponse->getContent());

        // Get campaign contacts API endpoint.
        $this->client->request(Request::METHOD_GET, "/api/campaigns/{$campaign->getId()}/contacts");
        $clientResponse = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $body = json_decode($clientResponse->getContent(), true);
        $this->assertSame(3, (int) $body['total']);
        $this->assertSame($contact->getId(), (int) $body['contacts'][2]['lead_id']);

        // Remove the contact from the campaign.
        $this->client->request(Request::METHOD_POST, "/api/campaigns/{$campaign->getId()}/contact/{$contact->getId()}/remove");
        $clientResponse = $this->client->getResponse();
        self::assertResponseIsSuccessful();
        $this->assertSame('{"success":1}', $clientResponse->getContent());

        // Assert that the campaign member was really removed.
        /** @var CampaignMember[] $campaignMembers */
        $campaignMembers = $campaignMemberRepository->findBy(['lead' => $contact->getId(), 'campaign' => $campaign->getId()]);
        $this->assertCount(1, $campaignMembers);
        $this->assertFalse($campaignMembers[0]->getManuallyAdded());
        $this->assertTrue($campaignMembers[0]->getManuallyRemoved());
    }
}
