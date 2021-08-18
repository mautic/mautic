<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

class AjaxControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        defined('MAUTIC_TABLE_PREFIX') or define('MAUTIC_TABLE_PREFIX', '');
    }

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

    /**
     * @throws MappingException
     */
    public function testSegmentDependencyTreeWithNotExistingSegment(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=lead:getSegmentDependencyTree&id=9999');
        $response = $this->client->getResponse();
        Assert::assertSame(404, $response->getStatusCode());
        Assert::assertSame('{"message":"Segment 9999 could not be found."}', $response->getContent());
    }

    /**
     * @throws MappingException
     */
    public function testSegmentDependencyTree(): void
    {
        $segmentA = new LeadList();
        $segmentA->setName('Segment A');
        $segmentA->setAlias('segment-a');

        $segmentB = new LeadList();
        $segmentB->setName('Segment B');
        $segmentB->setAlias('segment-b');

        $segmentC = new LeadList();
        $segmentC->setName('Segment C');
        $segmentC->setAlias('segment-c');

        $segmentD = new LeadList();
        $segmentD->setName('Segment D');
        $segmentD->setAlias('segment-d');

        $segmentE = new LeadList();
        $segmentE->setName('Segment E');
        $segmentE->setAlias('segment-e');

        $this->em->persist($segmentA);
        $this->em->persist($segmentB);
        $this->em->persist($segmentC);
        $this->em->persist($segmentD);
        $this->em->persist($segmentE);
        $this->em->flush();

        $segmentA->setFilters(
            [
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'leadlist',
                    'type'       => 'leadlist',
                    'operator'   => 'in',
                    'properties' => ['filter' => [$segmentB->getId()]],
                ], [
                    'object'     => 'lead',
                    'glue'       => 'or',
                    'field'      => 'leadlist',
                    'type'       => 'leadlist',
                    'operator'   => '!in',
                    'properties' => ['filter' => [$segmentC->getId(), $segmentD->getId()]],
                ],
            ]
        );

        $segmentC->setFilters(
            [
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'leadlist',
                    'type'       => 'leadlist',
                    'operator'   => 'in',
                    'properties' => ['filter' => [$segmentE->getId()]],
                ],
            ]
        );

        $this->em->persist($segmentA);
        $this->em->persist($segmentC);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, "/s/ajax?action=lead:getSegmentDependencyTree&id={$segmentA->getId()}");
        $response = $this->client->getResponse();
        self::assertTrue($response->isOk(), $response->getContent());

        Assert::assertSame(
            [
                'nodes' => [
                    ['id' => $segmentA->getId(), 'name' => $segmentA->getId()],
                    ['id' => $segmentB->getId(), 'name' => $segmentB->getId()],
                    ['id' => $segmentC->getId(), 'name' => $segmentC->getId()],
                    ['id' => $segmentE->getId(), 'name' => $segmentE->getId()],
                    ['id' => $segmentD->getId(), 'name' => $segmentD->getId()],
                ],
                'edges' => [
                    ['source' => $segmentA->getId(), 'target' => $segmentB->getId()],
                    ['source' => $segmentA->getId(), 'target' => $segmentC->getId()],
                    ['source' => $segmentA->getId(), 'target' => $segmentD->getId()],
                    ['source' => $segmentC->getId(), 'target' => $segmentE->getId()],
                ],
            ],
            json_decode($response->getContent(), true)
        );
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
