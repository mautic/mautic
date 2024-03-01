<?php

namespace Mautic\PointBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Response;

final class PointGroupsApiControllerTest extends MauticMysqlTestCase
{
    public function testPointGroupCRUDActions(): void
    {
        // Create a new point group
        $this->client->request('POST', '/api/points/groups/new', [
            'name'        => 'New Point Group',
            'description' => 'Description of the new point group',
        ]);

        $createResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $createResponse->getStatusCode());
        $responseData = json_decode($createResponse->getContent(), true);
        $this->assertArrayHasKey('group', $responseData);
        $createdData = $responseData['group'];
        $this->assertArrayHasKey('id', $createdData);
        $this->assertEquals('New Point Group', $createdData['name']);
        $this->assertEquals('Description of the new point group', $createdData['description']);

        // Retrieve all point groups
        $this->client->request('GET', '/api/points/groups');
        $getAllResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $getAllResponse->getStatusCode());
        $responseData = json_decode($getAllResponse->getContent(), true);
        $this->assertArrayHasKey('groups', $responseData);
        $this->assertEquals(1, $responseData['total']);
        $allData = $responseData['groups'];
        $this->assertIsArray($allData);
        $this->assertArrayHasKey(0, $allData);  // Ensure the response is array-indexed from 0
        $this->assertCount(1, $allData);

        // Update the created point group
        $updatePayload = [
            'name'        => 'Updated Point Group Name',
            'description' => 'Updated description of the point group',
        ];

        $this->client->request('PATCH', "/api/points/groups/{$createdData['id']}/edit", $updatePayload);
        $updateResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $updateResponse->getStatusCode());
        $responseData = json_decode($updateResponse->getContent(), true);
        $this->assertArrayHasKey('group', $responseData);
        $updatedData = $responseData['group'];
        $this->assertEquals('Updated Point Group Name', $updatedData['name']);
        $this->assertEquals('Updated description of the point group', $updatedData['description']);

        // Delete the created point group
        $this->client->request('DELETE', "/api/points/groups/{$createdData['id']}/delete");
        $deleteResponse = $this->client->getResponse();

        $this->assertSame(200, $deleteResponse->getStatusCode());
        $responseData = json_decode($deleteResponse->getContent(), true);
        $this->assertArrayHasKey('group', $responseData);
        $deleteData = $responseData['group'];
        $this->assertEquals('Updated Point Group Name', $deleteData['name']);
        $this->assertEquals('Updated description of the point group', $deleteData['description']);
    }
}
