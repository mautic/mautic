<?php

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\HttpFoundation\Response;

class ListApiControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var ListModel
     */
    protected $listModel;

    protected function setUp(): void
    {
        parent::setUp();
        /* @var ListModel $listModel */
        $this->listModel = self::$container->get('mautic.lead.model.list');
    }

    public function testSingleSegmentWorkflow()
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

    public function testBatchSegmentWorkflow()
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
        $segment = $segment ?? new LeadList();
        $segment->setName($name)->setAlias($alias)->setFilters($filters);
        $this->listModel->saveEntity($segment);

        return $segment;
    }
}
