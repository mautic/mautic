<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;

class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'leads',
            'campaigns',
        ]);
    }

    public function testToggleLeadCampaignAction(): void
    {
        $campaign = $this->createCampaign();
        $contact  = $this->createContact('blabla@contact.email');

        // Ensure there is no member for campaign 1 yet.
        $this->assertSame([], $this->getMembersForCampaign($campaign->getId()));

        // Create the member now.
        $payload = [
            'action'         => 'lead:toggleLeadCampaign',
            'leadId'         => $contact->getId(),
            'campaignId'     => $campaign->getId(),
            'campaignAction' => 'add',
        ];

        $this->client->request(Request::METHOD_POST, '/s/ajax', $payload, [], $this->createAjaxHeaders());
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertTrue($clientResponse->isOk(), $clientResponse->getContent());

        // Ensure the contact 1 is a campaign 1 member now.
        $this->assertSame([['lead_id' => (string) $contact->getId(), 'manually_added' => '1', 'manually_removed' => '0']], $this->getMembersForCampaign($campaign->getId()));

        $this->assertTrue(isset($response['success']), 'The response does not contain the `success` param.');
        $this->assertSame(1, $response['success']);

        $this->client->restart();
        // Let's remove the member now.
        $payload = [
            'action'         => 'lead:toggleLeadCampaign',
            'leadId'         => $contact->getId(),
            'campaignId'     => $campaign->getId(),
            'campaignAction' => 'remove',
        ];

        $this->client->request(Request::METHOD_POST, '/s/ajax', $payload, [], $this->createAjaxHeaders());
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        // Ensure the contact 1 was removed as a member of campaign 1 member now.
        $this->assertSame([['lead_id' => (string) $contact->getId(), 'manually_added' => '0', 'manually_removed' => '1']], $this->getMembersForCampaign($campaign->getId()));

        $this->assertTrue($clientResponse->isOk(), $clientResponse->getContent());
        $this->assertTrue(isset($response['success']), 'The response does not contain the `success` param.');
        $this->assertSame(1, $response['success']);
    }

    private function getMembersForCampaign(int $campaignId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('cl.lead_id, cl.manually_added, cl.manually_removed')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where("cl.campaign_id = {$campaignId}")
            ->execute()
            ->fetchAllAssociative();
    }

    private function createContact(string $email): Lead
    {
        $lead = new Lead();
        $lead->setEmail($email);

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();

        $campaign->setName('Campaign A');
        $campaign->setCanvasSettings(
            [
                'nodes' => [
                    [
                        'id'        => '148',
                        'positionX' => '760',
                        'positionY' => '155',
                    ],
                    [
                        'id'        => 'lists',
                        'positionX' => '860',
                        'positionY' => '50',
                    ],
                ],
                'connections' => [
                    [
                        'sourceId' => 'lists',
                        'targetId' => '148',
                        'anchors'  => [
                            'source' => 'leadsource',
                            'target' => 'top',
                        ],
                    ],
                ],
            ]
        );

        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }
}
