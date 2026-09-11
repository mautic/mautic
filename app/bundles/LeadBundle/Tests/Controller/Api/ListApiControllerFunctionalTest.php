<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\UpdateLeadListsCommand;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Helper\SegmentCountCacheHelper;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\ListModel;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ListApiControllerFunctionalTest extends MauticMysqlTestCase
{
    private ListModel $listModel;

    private string $prefix;

    private TranslatorInterface $translator;

    protected $useCleanupRollback = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listModel  = self::getContainer()->get(ListModel::class);
        $this->prefix     = self::getContainer()->getParameter('mautic.db_table_prefix');
        $this->translator = self::getContainer()->get(TranslatorInterface::class);
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(['categories']);
    }

    /**
     * @return iterable<array<string|int|null>>
     */
    public static function regexOperatorProvider(): iterable
    {
        yield [
            'regexp',
            '^{Test|Test string)', // invalid regex: the first parantheses should not be curly
            Response::HTTP_BAD_REQUEST,
            'error',
        ];

        yield [
            '!regexp',
            '^(Test|Test string))', // invalid regex: 2 ending parantheses
            Response::HTTP_BAD_REQUEST,
            'error',
        ];

        yield [
            'regexp',
            '^(Test|Test string)', // valid regex
            Response::HTTP_CREATED,
            null,
        ];
    }

    #[DataProvider('regexOperatorProvider')]
    public function testRegexOperatorValidation(string $operator, string $regex, int $expectedResponseCode, ?string $expectedErrorMessage): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/api/segments/new',
            [
                'name'    => 'Regex test',
                'filters' => [
                    [
                        'glue'       => 'and',
                        'field'      => 'city',
                        'object'     => 'lead',
                        'type'       => 'text',
                        'operator'   => $operator,
                        'properties' => ['filter' => $regex],
                    ],
                ],
            ]
        );

        self::assertResponseStatusCodeSame($expectedResponseCode);

        if ($expectedErrorMessage) {
            $this->assertStringContainsStringIgnoringCase($expectedErrorMessage, (string) json_decode($this->client->getResponse()->getContent(), true)['errors'][0]['message'], $this->client->getResponse()->getContent());
        }
    }

    public function testSingleSegmentWorkflow(): void
    {
        $payload = [
            'name'        => 'API segment',
            'description' => 'Segment created via API test',
            'filters'     => [
                // Legacy structure.
                [
                    'object'   => 'lead',
                    'glue'     => 'and',
                    'field'    => 'city',
                    'type'     => 'text',
                    'filter'   => 'Prague',
                    'display'  => null,
                    'operator' => '=',
                ],
                [
                    'object'   => 'lead',
                    'glue'     => 'and',
                    'field'    => 'owner_id',
                    'type'     => 'lookup_id',
                    'operator' => '=',
                    'display'  => 'John Doe',
                    'filter'   => '4',
                ],
                // Current structure.
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'city',
                    'type'       => 'text',
                    'properties' => ['filter' => 'Prague'],
                    'operator'   => '=',
                ],
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'owner_id',
                    'type'       => 'lookup_id',
                    'operator'   => '=',
                    'display'    => 'outdated name',
                    'filter'     => 'outdated_id',
                    'properties' => [
                        'display' => 'John Doe',
                        'filter'  => '4',
                    ],
                ],
                [
                    'glue'     => 'and',
                    'field'    => 'email',
                    'object'   => 'lead',
                    'type'     => 'email',
                    'operator' => '!empty',
                    'display'  => '',
                ],
            ],
        ];

        // Create:
        $this->client->request('POST', '/api/segments/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        if (!empty($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }

        $segmentId = $response['list']['id'];

        $this->assertResponseStatusCodeSame(201);
        $this->assertGreaterThan(0, $segmentId);
        $this->assertEquals($payload['name'], $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);
        $this->assertEquals([
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'city',
                'type'       => 'text',
                'properties' => ['filter' => 'Prague'],
                'operator'   => '=',
            ],
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'owner_id',
                'type'       => 'lookup_id',
                'operator'   => '=',
                'properties' => [
                    'display' => 'John Doe',
                    'filter'  => '4',
                ],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'city',
                'type'       => 'text',
                'properties' => ['filter' => 'Prague'],
                'operator'   => '=',
            ],
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'owner_id',
                'type'       => 'lookup_id',
                'operator'   => '=',
                'properties' => [
                    'display' => 'John Doe',
                    'filter'  => '4',
                ],
            ],
            [
                'object'     => 'lead',
                'glue'       => 'and',
                'field'      => 'email',
                'type'       => 'email',
                'operator'   => '!empty',
                'properties' => [
                    'filter'  => null,
                ],
            ],
        ],
            $response['list']['filters']
        );

        // Edit:
        $this->client->request('PATCH', "/api/segments/{$segmentId}/edit", ['name' => 'API segment renamed']);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame($segmentId, $response['list']['id'], 'ID of the created segment does not match with the edited one.');
        $this->assertEquals('API segment renamed', $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);

        // Get:
        $this->client->request('GET', "/api/segments/{$segmentId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertSame($segmentId, $response['list']['id'], 'ID of the created segment does not match with the fetched one.');
        $this->assertEquals('API segment renamed', $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);

        // Delete:
        $this->client->request('DELETE', "/api/segments/{$segmentId}/delete");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertNull($response['list']['id']);
        $this->assertEquals('API segment renamed', $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);

        // Get (ensure it's deleted):
        $this->client->request('GET', "/api/segments/{$segmentId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertResponseStatusCodeSame(404);
        $this->assertSame(404, $response['errors'][0]['code']);
    }

    public function testBatchSegmentWorkflow(): void
    {
        $payload = [
            [
                'name'        => 'API batch segment 1',
                'description' => 'Segment created via API test',
                'filters'     => [
                    // Legacy structure.
                    [
                        'object'   => 'lead',
                        'glue'     => 'and',
                        'field'    => 'city',
                        'type'     => 'text',
                        'filter'   => 'Prague',
                        'display'  => null,
                        'operator' => '=',
                    ],
                    // Current structure.
                    [
                        'object'     => 'lead',
                        'glue'       => 'and',
                        'field'      => 'city',
                        'type'       => 'text',
                        'properties' => ['filter' => 'Prague'],
                        'operator'   => '=',
                    ],
                ],
            ],
            [
                'name'        => 'API batch segment 2',
                'description' => 'Segment created via API test',
            ],
        ];

        $this->client->request('POST', '/api/segments/batch/new', $payload);
        $clientResponse  = $this->client->getResponse();
        $response1       = json_decode($clientResponse->getContent(), true);

        if (!empty($response1['errors'][0])) {
            $this->fail($response1['errors'][0]['code'].': '.$response1['errors'][0]['message']);
        }

        foreach ($response1['statusCodes'] as $statusCode) {
            $this->assertSame(201, $statusCode);
        }

        foreach ($response1['lists'] as $key => $segment) {
            $this->assertGreaterThan(0, $segment['id']);
            $this->assertTrue($segment['isPublished']);
            $this->assertTrue($segment['isGlobal']);
            $this->assertFalse($segment['isPreferenceCenter']);
            $this->assertSame($payload[$key]['name'], $segment['name']);
            $this->assertSame($payload[$key]['description'], $segment['description']);
            $this->assertIsArray($segment['filters']);

            // Make a change for the edit request:
            $response1['lists'][$key]['isPublished'] = false;
        }

        // Lets try to create the same segment to see that the values are not re-setted
        $this->client->request('PATCH', '/api/segments/batch/edit', $response1['lists']);
        $clientResponse  = $this->client->getResponse();
        $response2       = json_decode($clientResponse->getContent(), true);

        if (!empty($response2['errors'][0])) {
            $this->fail($response2['errors'][0]['code'].': '.$response2['errors'][0]['message']);
        }

        foreach ($response2['statusCodes'] as $statusCode) {
            $this->assertSame(200, $statusCode);
        }

        foreach ($response2['lists'] as $key => $segment) {
            $this->assertGreaterThan(0, $segment['id']);
            $this->assertFalse($segment['isPublished']);
            $this->assertTrue($segment['isGlobal']);
            $this->assertFalse($segment['isPreferenceCenter']);
            $this->assertSame($payload[$key]['name'], $segment['name']);
            $this->assertSame($payload[$key]['description'], $segment['description']);
        }

        $this->assertSame(
            [
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'city',
                    'type'       => 'text',
                    'operator'   => '=',
                    'properties' => ['filter' => 'Prague'],
                    'filter'     => 'Prague',
                    'display'    => null,
                ],
                [
                    'object'     => 'lead',
                    'glue'       => 'and',
                    'field'      => 'city',
                    'type'       => 'text',
                    'operator'   => '=',
                    'properties' => ['filter' => 'Prague'],
                    'filter'     => 'Prague',
                    'display'    => null,
                ],
            ],
            $response2['lists'][0]['filters']
        );

        $this->assertSame([], $response2['lists'][1]['filters']);
    }

    public function testWeGet422ResponseCodeIfSegmentIsBeingUsedInSomeCampaignAndWeUnpublishIt(): void
    {
        $segmentName = 'Segment1';
        $segment     = new LeadList();
        $segment->setName($segmentName);
        $segment->setPublicName($segmentName);
        $segment->setAlias(mb_strtolower($segmentName));
        $segment->setIsPublished(true);
        $this->em->persist($segment);

        $campaign     = new Campaign();
        $campaignName = 'Campaign1';
        $campaign->setName($campaignName);

        $this->em->persist($campaign);
        $this->em->flush();

        // insert unpublished record
        $this->connection->insert($this->prefix.'campaign_leadlist_xref', [
            'campaign_id'   => $campaign->getId(),
            'leadlist_id'   => $segment->getId(),
        ]);

        $this->client->request('PATCH', "/api/segments/{$segment->getId()}/edit", ['isPublished' => 0]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertArrayHasKey('errors', $response);
        $errorMessage = $this->translator->trans(
            'mautic.lead.lists.used_in_campaigns.unpublish',
            [
                '%count%'         => 1,
                '%campaignNames%' => '"'.$campaignName.'"',
                '%segmentNames%'  => 'Segment1',
            ],
            'validators'
        );
        $this->assertStringContainsString($errorMessage, (string) $response['errors'][0]['message']);
    }

    public function testWeGet200ResponseCodeIfSegmentIsNotUsedInCampaignsAndWeUnpublishIt(): void
    {
        $segmentName = 'Segment1';
        $segment     = new LeadList();
        $segment->setName($segmentName);
        $segment->setPublicName($segmentName);
        $segment->setAlias(mb_strtolower($segmentName));
        $segment->setIsPublished(true);
        $this->em->persist($segment);

        $campaign = new Campaign();
        $campaign->setName('campaign1');

        $this->em->persist($campaign);
        $this->em->flush();

        $this->client->request('PATCH', "/api/segments/{$segment->getId()}/edit", ['isPublished' => 0]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        self::assertResponseIsSuccessful();
        $this->assertArrayNotHasKey('errors', $response);
    }

    public function testUnpublishUsedSingleSegment(): void
    {
        $filter = [[
            'glue'     => 'and',
            'field'    => 'email',
            'object'   => 'lead',
            'type'     => 'email',
            'operator' => '!empty',
            'display'  => '',
        ]];
        $list1  = $this->saveSegment('s1', 's1', $filter);
        $filter = [[
            'object'     => 'lead',
            'glue'       => 'and',
            'field'      => 'leadlist',
            'type'       => 'leadlist',
            'operator'   => 'in',
            'properties' => [
                'filter' => [$list1->getId()],
            ],
            'display' => '',
        ]];
        $list2 = $this->saveSegment('s2', 's2', $filter);
        $this->em->clear();

        $this->client->request('PATCH', "/api/segments/{$list1->getId()}/edit", ['name' => 'API segment renamed', 'isPublished' => false]);
        $expectedErrorMessage = sprintf('isPublished: The segment %s is used in %s, please go back and check segments before unpublishing', 'API segment renamed', $list2->getName());

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertSame($expectedErrorMessage, $response['errors'][0]['message']);
    }

    public function testUnpublishUsedBatchSegment(): void
    {
        $filter = [[
            'glue'     => 'and',
            'field'    => 'email',
            'object'   => 'lead',
            'type'     => 'email',
            'operator' => '!empty',
            'display'  => '',
        ]];
        $list1  = $this->saveSegment('s1', 's1', $filter);
        $filter = [[
            'object'     => 'lead',
            'glue'       => 'and',
            'field'      => 'leadlist',
            'type'       => 'leadlist',
            'operator'   => 'in',
            'properties' => [
                'filter' => [$list1->getId()],
            ],
            'display' => '',
        ]];
        $list2 = $this->saveSegment('s2', 's2', $filter);
        $this->em->clear();
        $expectedErrorMessage = sprintf('isPublished: The segment %s is used in %s, please go back and check segments before unpublishing', $list1->getName(), $list2->getName());

        $segments = [
            ['id' => $list1->getId(), 'isPublished' => false],
            ['id' => $list2->getId(), 'isPublished' => false],
        ];

        $this->client->request('PATCH', '/api/segments/batch/edit', $segments);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $response['statusCodes'][0]);
        $this->assertSame($response['errors'][0]['message'], $expectedErrorMessage);

        $this->assertSame(Response::HTTP_OK, $response['statusCodes'][1]);
    }

    public function testUnpublishSegmentUsedInAnotherSegment(): void
    {
        $filter = [[
            'glue'     => 'and',
            'field'    => 'email',
            'object'   => 'lead',
            'type'     => 'email',
            'operator' => '!empty',
            'display'  => '',
        ]];
        $list1  = $this->saveSegment('s1', 's1', $filter);
        $filter = [[
            'object'     => 'lead',
            'glue'       => 'and',
            'field'      => 'leadlist',
            'type'       => 'leadlist',
            'operator'   => 'in',
            'properties' => [
                'filter' => [$list1->getId()],
            ],
            'display' => '',
        ]];
        $list2 = $this->saveSegment('s2', 's2', $filter);
        $this->em->clear();

        $this->client->request('PATCH', "/api/segments/{$list1->getId()}/edit", ['isPublished' => false]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        self::assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $this->assertArrayHasKey('errors', $response);

        $expectedErrorMessage = sprintf(
            'isPublished: The segment %s is used in %s, please go back and check segments before unpublishing',
            $list1->getName(),
            $list2->getName()
        );

        $this->assertSame($expectedErrorMessage, $response['errors'][0]['message']);
    }

    public function testSegmentWithCategory(): void
    {
        $categoryPayload = [
            'title'  => 'API Cat',
            'alias'  => 'kitty',
            'bundle' => 'segment',
        ];
        $this->client->request('POST', '/api/categories/new', $categoryPayload);
        $clientResponse     = $this->client->getResponse();
        $response           = json_decode($clientResponse->getContent(), true);
        $categoryId         = $response['category']['id'];

        $segmentPayload = [
            'name'        => 'API segment',
            'description' => 'Segment created via API test',
            'category'    => $categoryId,
        ];

        // Create:
        $this->client->request('POST', '/api/segments/new', $segmentPayload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        if (!empty($response['errors'][0])) {
            $this->fail($response['errors'][0]['code'].': '.$response['errors'][0]['message']);
        }

        $segmentId = $response['list']['id'];

        // Get segment with category by id:
        $this->client->request('GET', "/api/segments/{$segmentId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertEquals($segmentPayload['category'], $response['list']['category']['id']);

        // Search segments by category:
        $this->client->request('GET', '/api/segments?search=category:kitty');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertResponseIsSuccessful();
        $this->assertCount(1, $response['lists']);
    }

    public function testGetSegmentsWithContactCounts(): void
    {
        $segment = new LeadList();
        $segment->setName('Test Segment for Counts');
        $segment->setAlias('test-segment-counts');
        $segment->setPublicName('Test Segment');
        $segment->setFilters([
            [
                'glue'     => 'and',
                'field'    => 'email',
                'object'   => 'lead',
                'type'     => 'email',
                'operator' => '!empty',
            ],
        ]);
        $this->em->persist($segment);

        $contact1 = new Lead();
        $contact1->setEmail('test1@example.com');
        $this->em->persist($contact1);

        $contact2 = new Lead();
        $contact2->setEmail('test2@example.com');
        $this->em->persist($contact2);

        $this->em->flush();

        $listLead1 = new ListLead();
        $listLead1->setList($segment);
        $listLead1->setLead($contact1);
        $listLead1->setDateAdded(new \DateTime());
        $this->em->persist($listLead1);

        $listLead2 = new ListLead();
        $listLead2->setList($segment);
        $listLead2->setLead($contact2);
        $listLead2->setDateAdded(new \DateTime());
        $this->em->persist($listLead2);

        $this->em->flush();

        /** @var SegmentCountCacheHelper $segmentCountCacheHelper */
        $segmentCountCacheHelper = self::getContainer()->get(SegmentCountCacheHelper::class);
        $segmentCountCacheHelper->setSegmentContactCount($segment->getId(), 2);

        $this->client->request(Request::METHOD_GET, '/api/segments');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        self::assertResponseIsSuccessful();
        $this->assertArrayHasKey('lists', $response);
        $this->assertArrayHasKey($segment->getId(), $response['lists']);
        $this->assertArrayNotHasKey('contactCount', $response['lists'][$segment->getId()], 'contactCount should not be present without withCounts parameter');

        $this->client->request(Request::METHOD_GET, '/api/segments?withCounts');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        self::assertResponseIsSuccessful();
        $this->assertArrayHasKey('lists', $response);
        $this->assertArrayHasKey($segment->getId(), $response['lists']);
        $this->assertArrayHasKey('contactCount', $response['lists'][$segment->getId()], 'contactCount should be present with withCounts parameter');
        $this->assertSame(2, $response['lists'][$segment->getId()]['contactCount']);

        $contact3 = new Lead();
        $contact3->setEmail('test3@example.com');
        $this->em->persist($contact3);

        $listLead3 = new ListLead();
        $listLead3->setList($segment);
        $listLead3->setLead($contact3);
        $listLead3->setDateAdded(new \DateTime());
        $this->em->persist($listLead3);
        $this->em->flush();

        $this->client->request(Request::METHOD_GET, '/api/segments?withCounts');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        self::assertResponseIsSuccessful();
        $this->assertSame(2, $response['lists'][$segment->getId()]['contactCount'], 'Count should remain cached at 2 even after adding third contact');
    }

    public function testDeleteUsedInCampaignSegment(): void
    {
        $segmentName = 'Segment1';
        $segment     = $this->saveSegment($segmentName, mb_strtolower($segmentName));

        $campaign     = new Campaign();
        $campaignName = 'Campaign1';
        $campaign->setName($campaignName);

        $this->em->persist($campaign);
        $this->em->flush();

        $this->connection->insert($this->prefix.'campaign_leadlist_xref', [
            'campaign_id' => $campaign->getId(),
            'leadlist_id' => $segment->getId(),
        ]);

        $this->client->request('DELETE', "/api/segments/{$segment->getId()}/delete");

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $this->assertArrayHasKey('errors', $response);

        $expectedErrorMessage = $this->translator->trans(
            'mautic.api.dependent.entity.delete.error',
            [
                '%id%' => $segment->getId(),
            ],
            'validators'
        );

        $this->assertStringContainsString($expectedErrorMessage, (string) $response['errors'][0]['message']);

        $expectedErrorMessage = $this->translator->trans(
            'mautic.lead.lists.used_in_campaigns.delete',
            [
                '%campaignNames%' => '"'.$campaignName.'"',
                '%segmentNames%'  => $segmentName,
                '%count%'         => 1,
            ],
            'validators'
        );

        $this->assertStringContainsString($expectedErrorMessage, (string) $response['errors'][0]['details'][0]);
    }

    public function testBatchDeleteUsedInCampaignSegment(): void
    {
        $segment1 = $this->saveSegment('s1', 's1');
        $segment2 = $this->saveSegment('s2', 's2');

        $campaign = new Campaign();
        $campaign->setName('Campaign1');

        $this->em->persist($campaign);
        $this->em->flush();

        $this->connection->insert($this->prefix.'campaign_leadlist_xref', [
            'campaign_id' => $campaign->getId(),
            'leadlist_id' => $segment1->getId(),
        ]);

        $this->connection->insert($this->prefix.'campaign_leadlist_xref', [
            'campaign_id' => $campaign->getId(),
            'leadlist_id' => $segment2->getId(),
        ]);

        $ids = $segment1->getId().','.$segment2->getId();
        $this->client->request('DELETE', "/api/segments/batch/delete?ids={$ids}");

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        self::assertResponseIsSuccessful($clientResponse->getContent());
        $this->assertArrayHasKey('errors', $response);

        $expectedErrorMessage1 = $this->translator->trans(
            'mautic.api.dependent.entity.delete.error',
            [
                '%id%' => $segment1->getId(),
            ],
            'validators'
        );

        $expectedErrorMessage2 = $this->translator->trans(
            'mautic.api.dependent.entity.delete.error',
            [
                '%id%' => $segment2->getId(),
            ],
            'validators'
        );

        $expectedDetailMessage1 = $this->translator->trans(
            'mautic.lead.lists.used_in_campaigns.delete',
            [
                '%campaignNames%' => '"'.$campaign->getName().'"',
                '%segmentNames%'  => $segment1->getName(),
                '%count%'         => 1,
            ],
            'validators'
        );

        $expectedDetailMessage2 = $this->translator->trans(
            'mautic.lead.lists.used_in_campaigns.delete',
            [
                '%campaignNames%' => '"'.$campaign->getName().'"',
                '%segmentNames%'  => $segment2->getName(),
                '%count%'         => 1,
            ],
            'validators'
        );

        $allErrors = implode(' ', array_column($response['errors'], 'message'));
        $this->assertStringContainsString($expectedErrorMessage1, $allErrors);
        $this->assertStringContainsString($expectedErrorMessage2, $allErrors);

        $allDetails = implode(' ', array_column(array_column($response['errors'], 'details'), 0));
        $this->assertStringContainsString($expectedDetailMessage1, $allDetails);
        $this->assertStringContainsString($expectedDetailMessage2, $allDetails);
    }

    #[DataProvider('operatorProvider')]
    public function testInTheLastAndInTheNextFilter(string $operator, int $expected): void
    {
        $filters = [
            [
                'glue'        => 'and',
                'field'       => 'date_modified',
                'object'      => 'lead',
                'type'        => 'datetime',
                'operator'    => $operator,
                'properties'  => [
                    'filter' => [
                        'interval' => '1',
                        'unit'     => 'day',
                    ],
                ],
            ],
        ];

        $segment = $this->createSegment($filters);

        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $contactA = new Lead();
        $contactA->setDateModified(\DateTime::createFromImmutable($now->modify('-1 hour')));

        $contactB = new Lead();
        $contactB->setDateModified(\DateTime::createFromImmutable($now->modify('+1 day')));

        $contactC = new Lead();
        $contactC->setDateModified(\DateTime::createFromImmutable($now->modify('-1 day')));

        $contactD = new Lead();
        $contactD->setDateModified(\DateTime::createFromImmutable($now->modify('-2 day')));

        $contactE = new Lead();
        $contactE->setDateModified(\DateTime::createFromImmutable($now->modify('+2 day')));

        $this->em->persist($contactA);
        $this->em->persist($contactB);
        $this->em->persist($contactC);
        $this->em->persist($contactD);
        $this->em->persist($contactE);
        $this->em->flush();

        $commandTester = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, ['--list-id' => $segment->getId()]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $members = $this->em->getRepository(ListLead::class)->findBy(['list' => $segment->getId()]);

        $this->assertCount($expected, $members);

        $expectedMembersForOperator = [
            OperatorOptions::IN_LAST => [$contactA->getId(), $contactC->getId()],
            OperatorOptions::IN_NEXT => [$contactA->getId(), $contactB->getId()],
        ];

        $expectedMembers = $expectedMembersForOperator[$operator];

        $actualMembers   = array_map(fn (ListLead $segment) => $segment->getLead()->getId(), $members);
        sort($expectedMembers);
        sort($actualMembers);
        $this->assertSame($expectedMembers, $actualMembers);
    }

    #[DataProvider('operatorProvider')]
    public function testInLastAndInNextFilterForCompanyCustomField(string $operator, int $expectedCount): void
    {
        $company = $this->createCompanyWithDateCustomField('CompanyABC', $operator);

        $filters = [
            [
                'glue'        => 'and',
                'field'       => 'company_created_at',
                'object'      => 'company',
                'type'        => 'datetime',
                'operator'    => $operator,
                'properties'  => [
                    'filter' => [
                        'interval' => '1',
                        'unit'     => 'day',
                    ],
                ],
            ],
        ];
        $segment = $this->createSegment($filters);

        $contactA = new Lead();
        $contactA->setDateModified(new \DateTime('-1 hour'));

        $contactB = new Lead();
        $contactB->setDateModified((new \DateTime())->modify('+1 day'));

        $contactC = new Lead();
        $contactC->setDateModified((new \DateTime())->modify('-1 day'));

        $this->em->persist($contactA);
        $this->em->persist($contactB);
        $this->em->persist($contactC);

        $this->createCompanyLeadRelation($company, $contactB);
        $this->createCompanyLeadRelation($company, $contactC);

        $this->em->flush();

        $commandTester = $this->testSymfonyCommand(UpdateLeadListsCommand::NAME, ['--list-id' => $segment->getId()]);

        $this->assertSame(0, $commandTester->getStatusCode());

        $members = $this->em->getRepository(ListLead::class)->findBy(['list' => $segment->getId()]);

        $this->assertCount($expectedCount, $members);

        $expectedMembers = [$contactB->getId(), $contactC->getId()];

        $actualMembers   = array_map(fn (ListLead $segment) => $segment->getLead()->getId(), $members);
        sort($expectedMembers);
        sort($actualMembers);
        $this->assertSame($expectedMembers, $actualMembers);
    }

    public static function operatorProvider(): \Generator
    {
        yield [OperatorOptions::IN_LAST, 2];
        yield [OperatorOptions::IN_NEXT, 2];
    }

    /**
     * @param array<int, array<string, mixed>> $filters
     */
    private function saveSegment(string $name, string $alias, array $filters = [], ?LeadList $segment = null): LeadList
    {
        $segment ??= new LeadList();
        $segment->setName($name)->setPublicName($name)->setAlias($alias)->setFilters($filters);
        $this->listModel->saveEntity($segment);

        return $segment;
    }

    private function createCompanyWithDateCustomField(string $name, ?string $operator = null): Company
    {
        // create datetime company field
        $field = new LeadField();
        $field->setType('datetime');
        $field->setObject('company');
        $field->setGroup('core');
        $field->setLabel('Company Created At');
        $field->setAlias('company_created_at');

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get(FieldModel::class);
        $fieldModel->saveEntity($field);

        $timeStamp = new \DateTime();
        if (OperatorOptions::IN_LAST === $operator) {
            $timeStamp->modify('-1 day');
        } elseif (OperatorOptions::IN_NEXT === $operator) {
            $timeStamp->modify('+1 day');
        }

        /** @var CompanyRepository $companyRepo */
        $companyRepo = $this->em->getRepository(Company::class);
        $company     = new Company();
        $company->setName($name);
        $company->addUpdatedField('company_created_at', $timeStamp->format('Y-m-d H:i:s'));
        $companyRepo->saveEntity($company);

        return $company;
    }

    private function createCompanyLeadRelation(Company $company, Lead $lead): void
    {
        $companyLead = new CompanyLead();
        $companyLead->setCompany($company);
        $companyLead->setLead($lead);
        $companyLead->setDateAdded(new \DateTime());
        $companyLead->setPrimary(true);
        $this->em->persist($companyLead);
    }

    /**
     * @param array<mixed> $filter
     */
    private function createSegment(array $filter): LeadList
    {
        $segment = new LeadList();
        $segment->setName('Segment A');
        $segment->setPublicName($segment->getName());
        $segment->setAlias('segment-a');
        $segment->setFilters($filter);
        $this->em->persist($segment);

        return $segment;
    }
}
