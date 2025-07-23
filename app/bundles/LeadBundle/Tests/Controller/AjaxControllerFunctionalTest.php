<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\UserBundle\Entity\Role;
use Mautic\UserBundle\Entity\RoleRepository;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use MauticPlugin\MauticTagManagerBundle\Entity\Tag;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

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
        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(Request::METHOD_POST, '/s/ajax', $payload);
        $this->assertResponseIsSuccessful();
        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Ensure the contact 1 is a campaign 1 member now.
        $this->assertSame(
            [['lead_id' => (string) $contact->getId(), 'manually_added' => '1', 'manually_removed' => '0']],
            $this->getMembersForCampaign($campaign->getId()),
            $this->client->getResponse()->getContent()
        );

        $this->assertTrue(isset($response['success']), 'The response does not contain the `success` param.');
        $this->assertSame(1, $response['success']);

        // Let's remove the member now.
        $payload = [
            'action'         => 'lead:toggleLeadCampaign',
            'leadId'         => $contact->getId(),
            'campaignId'     => $campaign->getId(),
            'campaignAction' => 'remove',
        ];

        $this->client->xmlHttpRequest(Request::METHOD_POST, '/s/ajax', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        // Ensure the contact 1 was removed as a member of campaign 1 member now.
        $this->assertSame([['lead_id' => (string) $contact->getId(), 'manually_added' => '0', 'manually_removed' => '1']], $this->getMembersForCampaign($campaign->getId()));

        $this->assertTrue($clientResponse->isOk(), $clientResponse->getContent());
        $this->assertTrue(isset($response['success']), 'The response does not contain the `success` param.');
        $this->assertSame(1, $response['success']);
    }

    public function testSegmentDependencyTreeWithNotExistingSegment(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=lead:getSegmentDependencyTree&id=9999');
        $response = $this->client->getResponse();
        Assert::assertSame(404, $response->getStatusCode());
        Assert::assertSame('{"message":"Segment 9999 could not be found."}', $response->getContent());
    }

    public function testCompanyLookupWithNoCompanySelected(): void
    {
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=lead:getLookupChoiceList&searchKey=lead.company&lead.company=unicorn');
        $response = $this->client->getResponse();
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('[]', $response->getContent());
    }

    public function testCompanyLookupWithCompanySelected(): void
    {
        $company = new Company();
        $company->setName('SaaS Company');
        $this->em->persist($company);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/ajax?action=lead:getLookupChoiceList&searchKey=lead.company&lead.company=sa');
        $response = $this->client->getResponse();
        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertSame('[{"text":"SaaS Company","value":"'.$company->getId().'"}]', $response->getContent());
    }

    public function testCompanyLookupWithNoModelSet(): void
    {
        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax?action=lead:getLookupChoiceList&lead.company=unicorn');
        $response = $this->client->getResponse();
        Assert::assertSame(400, $response->getStatusCode());
        Assert::assertStringContainsString('Bad Request - The searchKey parameter is required', $response->getContent());
    }

    public function testCompanyLookupWithLimit(): void
    {
        $company1 = new Company();
        $company1->setName('Company 1');
        $this->em->persist($company1);

        $company2 = new Company();
        $company2->setName('Company 2');
        $this->em->persist($company2);

        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/s/ajax?action=lead:getLookupChoiceList&searchKey=lead.company&lead.company=Company&limit=1');

        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), true);

        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertIsArray($content);
        Assert::assertCount(1, $content, 'The result should contain only one element');
        Assert::assertSame('Company 1', $content[0]['text']);
        Assert::assertSame($company1->getId(), (int) $content[0]['value']);

        $this->client->request(Request::METHOD_GET, '/s/ajax?action=lead:getLookupChoiceList&searchKey=lead.company&lead.company=Company&limit=1&start=1');
        $response = $this->client->getResponse();
        $content  = json_decode($response->getContent(), true);

        Assert::assertSame(200, $response->getStatusCode());
        Assert::assertIsArray($content);
        Assert::assertCount(1, $content, 'The result should contain only one element');
        Assert::assertSame('Company 2', $content[0]['text']);
        Assert::assertSame($company2->getId(), (int) $content[0]['value']);
    }

    public function testSegmentDependencyTree(): void
    {
        $segmentA = new LeadList();
        $segmentA->setName('Segment A');
        $segmentA->setPublicName('Segment A');
        $segmentA->setAlias('segment-a');

        $segmentB = new LeadList();
        $segmentB->setName('Segment B');
        $segmentB->setPublicName('Segment B');
        $segmentB->setAlias('segment-b');

        $segmentC = new LeadList();
        $segmentC->setName('Segment C');
        $segmentC->setPublicName('Segment C');
        $segmentC->setAlias('segment-c');

        $segmentD = new LeadList();
        $segmentD->setName('Segment D');
        $segmentD->setPublicName('Segment D');
        $segmentD->setAlias('segment-d');

        $segmentE = new LeadList();
        $segmentE->setName('Segment E');
        $segmentE->setPublicName('Segment E');
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
                'levels' => [
                    [
                        'nodes' => [
                            ['id' => "0-{$segmentA->getId()}", 'name' => $segmentA->getName(), 'link' => "/s/segments/view/{$segmentA->getId()}"],
                        ],
                    ],
                    [
                        'nodes' => [
                            ['id' => "{$segmentA->getId()}-{$segmentB->getId()}", 'name' => $segmentB->getName(), 'link' => "/s/segments/view/{$segmentB->getId()}"],
                            ['id' => "{$segmentA->getId()}-{$segmentC->getId()}", 'name' => $segmentC->getName(), 'link' => "/s/segments/view/{$segmentC->getId()}"],
                            ['id' => "{$segmentA->getId()}-{$segmentD->getId()}", 'name' => $segmentD->getName(), 'link' => "/s/segments/view/{$segmentD->getId()}"],
                        ],
                    ],
                    [
                        'nodes' => [
                            ['id' => "{$segmentC->getId()}-{$segmentE->getId()}", 'name' => $segmentE->getName(), 'link' => "/s/segments/view/{$segmentE->getId()}"],
                        ],
                    ],
                ],
                'edges' => [
                    ['source' => "0-{$segmentA->getId()}", 'target' => "{$segmentA->getId()}-{$segmentB->getId()}"],
                    ['source' => "0-{$segmentA->getId()}", 'target' => "{$segmentA->getId()}-{$segmentC->getId()}"],
                    ['source' => "0-{$segmentA->getId()}", 'target' => "{$segmentA->getId()}-{$segmentD->getId()}"],
                    ['source' => "{$segmentA->getId()}-{$segmentC->getId()}", 'target' => "{$segmentC->getId()}-{$segmentE->getId()}"],
                ],
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testSegmentDependencyTreeWithLoop(): void
    {
        $segmentA = new LeadList();
        $segmentA->setName('Segment A');
        $segmentA->setPublicName('Segment A');
        $segmentA->setAlias('segment-a');

        $segmentB = new LeadList();
        $segmentB->setName('Segment B');
        $segmentB->setPublicName('Segment B');
        $segmentB->setAlias('segment-b');

        $segmentC = new LeadList();
        $segmentC->setName('Segment C');
        $segmentC->setPublicName('Segment C');
        $segmentC->setAlias('segment-c');

        $segmentD = new LeadList();
        $segmentD->setName('Segment D');
        $segmentD->setPublicName('Segment D');
        $segmentD->setAlias('segment-d');

        $segmentE = new LeadList();
        $segmentE->setName('Segment E');
        $segmentE->setPublicName('Segment E');
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

        $segmentE->setFilters(
            [
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'leadlist',
                    'type'       => 'leadlist',
                    'operator'   => 'in',
                    'properties' => ['filter' => [$segmentA->getId()]],
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
                'levels' => [
                    [
                        'nodes' => [
                            ['id' => "0-{$segmentA->getId()}", 'name' => $segmentA->getName(), 'link' => "/s/segments/view/{$segmentA->getId()}"],
                        ],
                    ],
                    [
                        'nodes' => [
                            ['id' => "{$segmentA->getId()}-{$segmentB->getId()}", 'name' => $segmentB->getName(), 'link' => "/s/segments/view/{$segmentB->getId()}"],
                            ['id' => "{$segmentA->getId()}-{$segmentC->getId()}", 'name' => $segmentC->getName(), 'link' => "/s/segments/view/{$segmentC->getId()}"],
                            ['id' => "{$segmentA->getId()}-{$segmentD->getId()}", 'name' => $segmentD->getName(), 'link' => "/s/segments/view/{$segmentD->getId()}"],
                        ],
                    ],
                    [
                        'nodes' => [
                            ['id' => "{$segmentC->getId()}-{$segmentE->getId()}", 'name' => $segmentE->getName(), 'link' => "/s/segments/view/{$segmentE->getId()}"],
                        ],
                    ],
                    [
                        'nodes' => [
                            [
                                'id'      => "{$segmentE->getId()}-{$segmentA->getId()}",
                                'name'    => $segmentA->getName(),
                                'link'    => "/s/segments/view/{$segmentA->getId()}",
                                'message' => 'This segment already exists in the segment dependency tree',
                            ],
                        ],
                    ],
                ],
                'edges' => [
                    ['source' => "0-{$segmentA->getId()}", 'target' => "{$segmentA->getId()}-{$segmentB->getId()}"],
                    ['source' => "0-{$segmentA->getId()}", 'target' => "{$segmentA->getId()}-{$segmentC->getId()}"],
                    ['source' => "0-{$segmentA->getId()}", 'target' => "{$segmentA->getId()}-{$segmentD->getId()}"],
                    ['source' => "{$segmentA->getId()}-{$segmentC->getId()}", 'target' => "{$segmentC->getId()}-{$segmentE->getId()}"],
                    ['source' => "{$segmentC->getId()}-{$segmentE->getId()}", 'target' => "{$segmentE->getId()}-{$segmentA->getId()}"],
                ],
            ],
            json_decode($response->getContent(), true)
        );
    }

    public function testRemoveTagFromLeadAction(): void
    {
        // Create a lead
        $lead = $this->createContact('test@email.com');
        // ... set other properties as needed

        // Create a tag
        $tag = new Tag();
        $tag->setTag('Test Tag');
        // ... set other properties as needed

        // Link the lead and tag
        $lead->addTag($tag);

        // Persist the lead and tag
        $this->em->persist($lead);
        $this->em->persist($tag);
        $this->em->flush();

        // Call the removeTagFromLeadAction
        $this->client->request(Request::METHOD_POST, '/s/ajax?action=lead:removeTagFromLead', [
            'leadId' => $lead->getId(),
            'tagId'  => $tag->getId(),
        ]);
        $clientResponse = $this->client->getResponse();

        $response = json_decode($clientResponse->getContent(), true);
        $this->assertTrue($clientResponse->isOk(), $clientResponse->getContent());

        // Assert the tag is removed from the lead
        $updatedLead = $this->em->getRepository(Lead::class)->find($lead->getId());
        $this->assertFalse(in_array($tag, $updatedLead->getTags()->toArray()));
    }

    public function testContactListActionSuggestionsByAdminUser(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->em->getRepository(User::class);

        /** @var User $adminUser */
        $adminUser = $userRepository->findOneBy(['username' => 'admin']);
        self::assertInstanceOf(User::class, $adminUser);

        $salesUser = $userRepository->findOneBy(['username' => 'sales']);
        self::assertInstanceOf(User::class, $salesUser);

        $leads = [];

        // Create 4 leads with two owned by admin and sales users respectively.
        for ($i = 1; $i <= 4; ++$i) {
            $owner = $adminUser;

            if ($i > 2) {
                $owner = $salesUser;
            }

            $lead = new Lead();
            $lead->setFirstname("User $i");
            $lead->setOwner($owner);
            $leads[] = $lead;
        }

        /** @var LeadRepository $leadRepository */
        $leadRepository = $this->em->getRepository(Lead::class);
        $leadRepository->saveEntities($leads);
        $this->em->clear();

        // Check suggestions for admin user.
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=lead:contactList&field=undefined&filter=user');
        $response = $this->client->getResponse();
        self::assertTrue($response->isOk());

        $data       = json_decode($response->getContent(), true);
        $foundNames = array_column($data, 'value');

        self::assertCount(4, $foundNames);

        foreach ($foundNames as $key => $name) {
            self::assertSame('User '.($key + 1), $name);
        }
    }

    public function testContactListActionSuggestionsByNonAdminUser(): void
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->em->getRepository(User::class);

        $adminUser = $userRepository->findOneBy(['username' => 'admin']);
        self::assertInstanceOf(User::class, $adminUser);

        $leads = [];

        // Create 2 leads with owned by admin user.
        for ($i = 1; $i <= 2; ++$i) {
            $lead = new Lead();
            $lead->setFirstname("User $i");
            $lead->setOwner($adminUser);
            $leads[] = $lead;
        }

        /** @var LeadRepository $leadRepository */
        $leadRepository = $this->em->getRepository(Lead::class);
        $leadRepository->saveEntities($leads);
        $this->em->clear();

        $role = new Role();
        $role->setName('Role');
        $role->setIsAdmin(false);
        $role->setRawPermissions(['lead:leads' => ['viewown']]);

        /** @var RoleRepository $roleRepository */
        $roleRepository = $this->em->getRepository(Role::class);
        $roleRepository->saveEntity($role);

        // Create a non admin user with view own contacts permission.
        $user = new User();
        $user->setFirstName('Non');
        $user->setLastName('Admin');
        $user->setEmail('non-admin-user@test.com');
        $user->setUsername('non-admin-user');
        $user->setRole($role);

        /** @var PasswordHasherInterface $hasher */
        $hasher = static::getContainer()->get('security.password_hasher_factory')->getPasswordHasher($user);

        $passwordNonAdmin = 'Maut1cR0cks!';
        $user->setPassword($hasher->hash($passwordNonAdmin));
        $userRepository->saveEntity($user);

        /** @var User $nonAdminUser */
        $nonAdminUser = $userRepository->findOneBy(['email' => 'non-admin-user@test.com']);

        $nonAdminLeads = [];

        // Create 2 leads with owned by non-admin user.
        for ($i = 3; $i <= 4; ++$i) {
            $lead = new Lead();
            $lead->setFirstname("User $i");
            $lead->setOwner($nonAdminUser);
            $nonAdminLeads[] = $lead;
        }

        $leadRepository->saveEntities($nonAdminLeads);
        $this->em->clear();

        $this->logoutUser();

        // Check suggestions for a non admin user.
        $this->client->loginUser($nonAdminUser, 'mautic');
        $this->client->setServerParameter('PHP_AUTH_USER', 'non-admin-user');
        // Set the new password, because new authenticator system checks for it.
        $this->client->setServerParameter('PHP_AUTH_PW', $passwordNonAdmin);
        $this->client->request(Request::METHOD_GET, '/s/ajax?action=lead:contactList&field=undefined&filter=user');
        $response = $this->client->getResponse();
        self::assertTrue($response->isOk());

        $data       = json_decode($response->getContent(), true);
        $foundNames = array_column($data, 'value');

        self::assertCount(2, $foundNames);
        self::assertSame('User 3', $foundNames[0]);
        self::assertSame('User 4', $foundNames[1]);
    }

    /**
     * @dataProvider leadFieldOrderChoiceListProvider
     *
     * @param string[] $expectedOptions
     */
    public function testUpdateLeadFieldOrderChoiceListAction(string $object, string $group, array $expectedOptions): void
    {
        $payload = [
            'action' => 'lead:updateLeadFieldOrderChoiceList',
            'object' => $object,
            'group'  => $group,
        ];

        $this->setCsrfHeader();
        $this->client->xmlHttpRequest(Request::METHOD_POST, '/s/ajax', $payload);

        // Get the response HTML
        $response    = $this->client->getResponse();
        $htmlContent = $response->getContent();

        // Assert the response is successful
        $this->assertTrue($response->isOk(), "Response was not OK for object: $object, group: $group");
        $this->assertStringNotContainsString('<form', $htmlContent, 'Response contains a form instead of just field order.');
        $this->assertStringContainsString('<select', $htmlContent, 'Response contains select tag.');

        // Parse the HTML content using DOMDocument
        $dom = new \DOMDocument();
        @$dom->loadHTML($htmlContent);
        $select  = $dom->getElementsByTagName('select')->item(0);
        $options = $select->getElementsByTagName('option');

        $actualOptions = [];
        foreach ($options as $option) {
            if ($option->textContent) {
                // Get the text content of each <option>
                $actualOptions[] = trim($option->textContent);
            }
        }
        // Assert that the actual options match the expected options
        if (empty($expectedOptions)) {
            $this->assertEmpty($actualOptions);
        }
        foreach ($expectedOptions as $expectedValue) {
            $this->assertContains($expectedValue, $actualOptions, "Missing expected option '$expectedValue' for object: $object, group: $group");
        }
    }

    public function leadFieldOrderChoiceListProvider(): \Generator
    {
        yield ['lead', 'core', ['Fax', 'Website']];
        yield ['lead', 'social', ['Facebook', 'Foursquare', 'Instagram']];
        yield ['company', 'core', []];
    }

    private function getMembersForCampaign(int $campaignId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('cl.lead_id, cl.manually_added, cl.manually_removed')
            ->from(MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl')
            ->where("cl.campaign_id = {$campaignId}")
            ->executeQuery()
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
