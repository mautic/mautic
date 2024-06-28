<?php

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListApiControllerFunctionalTest extends MauticMysqlTestCase
{
    protected ListModel $listModel;

    private string $prefix;

    private TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->listModel  = static::getContainer()->get('mautic.lead.model.list');
        $this->prefix     = static::getContainer()->getParameter('mautic.db_table_prefix');
        $this->translator = static::getContainer()->get('translator');
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(['categories']);
    }

    /**
     * @return iterable<array<string|int|null>>
     */
    public function regexOperatorProvider(): iterable
    {
        yield [
            'regexp',
            '^{Test|Test string)', // invalid regex: the first parantheses should not be curly
            Response::HTTP_BAD_REQUEST,
            'filter: Got error \'unmatched parentheses at offset 18\' from regexp',
        ];

        yield [
            '!regexp',
            '^(Test|Test string))', // invalid regex: 2 ending parantheses
            Response::HTTP_BAD_REQUEST,
            'filter: Got error \'unmatched parentheses at offset 19\' from regexp',
        ];

        yield [
            'regexp',
            '^(Test|Test string)', // valid regex
            Response::HTTP_CREATED,
            null,
        ];
    }

    /**
     * @dataProvider regexOperatorProvider
     */
    public function testRegexOperatorValidation(string $operator, string $regex, int $expectedResponseCode, ?string $expectedErrorMessage): void
    {
        $version = $this->connection->executeQuery('SELECT VERSION()')->fetchOne();
        version_compare($version, '8.0.0', '<') ? $this->markTestSkipped('MySQL 5.7.0 does not throw error for invalid REGEXP') : null;

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

        Assert::assertSame($expectedResponseCode, $this->client->getResponse()->getStatusCode());

        if ($expectedErrorMessage) {
            Assert::assertSame(
                $expectedErrorMessage,
                json_decode($this->client->getResponse()->getContent(), true)['errors'][0]['message'],
                $this->client->getResponse()->getContent()
            );
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

        $this->assertSame(201, $clientResponse->getStatusCode());
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

        $this->assertSame(200, $clientResponse->getStatusCode());
        $this->assertSame($segmentId, $response['list']['id'], 'ID of the created segment does not match with the edited one.');
        $this->assertEquals('API segment renamed', $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);

        // Get:
        $this->client->request('GET', "/api/segments/{$segmentId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode());
        $this->assertSame($segmentId, $response['list']['id'], 'ID of the created segment does not match with the fetched one.');
        $this->assertEquals('API segment renamed', $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);

        // Delete:
        $this->client->request('DELETE', "/api/segments/{$segmentId}/delete");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(200, $clientResponse->getStatusCode());
        $this->assertNull($response['list']['id']);
        $this->assertEquals('API segment renamed', $response['list']['name']);
        $this->assertEquals($payload['description'], $response['list']['description']);

        // Get (ensure it's deleted):
        $this->client->request('GET', "/api/segments/{$segmentId}");
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertSame(404, $clientResponse->getStatusCode());
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
        Assert::assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $clientResponse->getStatusCode());
        Assert::assertArrayHasKey('errors', $response);
        $errorMessage = $this->translator->trans(
            'mautic.lead.lists.used_in_campaigns',
            [
                '%count%'         => '1',
                '%campaignNames%' => '"'.$campaignName.'"',
            ],
            'validators'
        );
        Assert::assertStringContainsString($errorMessage, $response['errors'][0]['message']);
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
        Assert::assertSame(Response::HTTP_OK, $clientResponse->getStatusCode());
        Assert::assertArrayNotHasKey('errors', $response);
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
        $expectedErrorMessage = sprintf('leadlist: This segment is used in %s, please go back and check segments before unpublishing', $list2->getName());

        $this->client->request('PATCH', "/api/segments/{$list1->getId()}/edit", ['name' => 'API segment renamed', 'isPublished' => false]);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $clientResponse->getStatusCode());
        $this->assertSame($response['errors'][0]['message'], $expectedErrorMessage);
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
        $expectedErrorMessage = sprintf('leadlist: This segment is used in %s, please go back and check segments before unpublishing', $list2->getName());

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

        $this->assertTrue($clientResponse->isOk());
        $this->assertEquals($segmentPayload['category'], $response['list']['category']['id']);

        // Search segments by category:
        $this->client->request('GET', '/api/segments?search=category:kitty');
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertTrue($clientResponse->isOk());
        $this->assertCount(1, $response['lists']);
    }

    private function saveSegment(string $name, string $alias, array $filters = [], LeadList $segment = null): LeadList
    {
        $segment ??= new LeadList();
        $segment->setName($name)->setPublicName($name)->setAlias($alias)->setFilters($filters);
        $this->listModel->saveEntity($segment);

        return $segment;
    }
}
