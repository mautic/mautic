<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Controller\Api;

use Mautic\CampaignBundle\Entity\Lead as CampaignMember;
use Mautic\CampaignBundle\Tests\Campaign\AbstractCampaignTest;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class ContactCampaignApiControllerFunctionalTest extends AbstractCampaignTest
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
        Assert::assertTrue($clientResponse->isOk(), $clientResponse->getContent());
        Assert::assertSame('{"success":1}', $clientResponse->getContent());

        // Assert that the campaign member was really added.
        /** @var CampaignMember[] $campaignMembers */
        $campaignMembers = $campaignMemberRepository->findBy(['lead' => $contact->getId(), 'campaign' => $campaign->getId()]);
        Assert::assertCount(1, $campaignMembers);
        Assert::assertTrue($campaignMembers[0]->getManuallyAdded());
        Assert::assertFalse($campaignMembers[0]->getManuallyRemoved());

        // Get the contact's campaigns.
        $this->client->request(Request::METHOD_GET, "/api/contacts/{$contact->getId()}/campaigns");
        $clientResponse = $this->client->getResponse();
        Assert::assertTrue($clientResponse->isOk(), $clientResponse->getContent());
        $body = json_decode($clientResponse->getContent(), true);
        Assert::assertSame(1, $body['total'], $clientResponse->getContent());
        Assert::assertSame($campaign->getId(), $body['campaigns'][$campaign->getId()]['id'], $clientResponse->getContent());
        Assert::assertSame($campaign->getName(), $body['campaigns'][$campaign->getId()]['name'], $clientResponse->getContent());
        Assert::assertNotEmpty($body['campaigns'][$campaign->getId()]['dateAdded'], $clientResponse->getContent());
        Assert::assertFalse($body['campaigns'][$campaign->getId()]['manuallyRemoved'], $clientResponse->getContent());
        Assert::assertTrue($body['campaigns'][$campaign->getId()]['manuallyAdded'], $clientResponse->getContent());

        // Get campaign contacts API endpoint.
        $this->client->request(Request::METHOD_GET, "/api/campaigns/{$campaign->getId()}/contacts");
        $clientResponse = $this->client->getResponse();
        Assert::assertTrue($clientResponse->isOk(), $clientResponse->getContent());
        $body = json_decode($clientResponse->getContent(), true);
        Assert::assertSame(3, (int) $body['total']);
        Assert::assertSame($contact->getId(), (int) $body['contacts'][2]['lead_id']);

        // Remove the contact from the campaign.
        $this->client->request(Request::METHOD_POST, "/api/campaigns/{$campaign->getId()}/contact/{$contact->getId()}/remove");
        $clientResponse = $this->client->getResponse();
        Assert::assertTrue($clientResponse->isOk(), $clientResponse->getContent());
        Assert::assertSame('{"success":1}', $clientResponse->getContent());

        // Assert that the campaign member was really removed.
        /** @var CampaignMember[] $campaignMembers */
        $campaignMembers = $campaignMemberRepository->findBy(['lead' => $contact->getId(), 'campaign' => $campaign->getId()]);
        Assert::assertCount(1, $campaignMembers);
        Assert::assertFalse($campaignMembers[0]->getManuallyAdded());
        Assert::assertTrue($campaignMembers[0]->getManuallyRemoved());
    }
}
