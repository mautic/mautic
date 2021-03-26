<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\DataFixtures\ORM\LoadCategoryData;

class ListApiControllerFunctionalTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadCategoryData::class]);
    }

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement(['categories']);
    }

    public function testSingleSegmentWorkflow()
    {
        $payload = [
            'name'        => 'API segment',
            'description' => 'Segment created via API test',
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
            $this->assertIsArray($segment['filters']);
        }
    }
}
