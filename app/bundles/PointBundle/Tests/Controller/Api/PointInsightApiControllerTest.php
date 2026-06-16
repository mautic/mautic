<?php

namespace Mautic\PointBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Symfony\Component\HttpFoundation\Response;

class PointInsightApiControllerTest extends MauticMysqlTestCase
{
    public function testPointInsightCRUDActions(): void
    {
        /** @var Translator $translator */
        $translator = static::getContainer()->get('translator');

        $this->client->request('POST', '/api/points/insights/new', [
            'name'          => 'New Point Insight',
            'description'   => 'Description of the new point insight',
            'insightType'   => 'compare_point_groups',
            'insightAction' => 'set_custom_field',
        ]);

        $createResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $createResponse->getStatusCode());
        $responseData = json_decode($createResponse->getContent(), true);
        $this->assertArrayHasKey('insight', $responseData);
        $createdData = $responseData['insight'];
        $this->assertArrayHasKey('id', $createdData);
        $this->assertEquals('New Point Insight', $createdData['name']);
        $this->assertEquals('Description of the new point insight', $createdData['description']);
        $this->assertArrayHasKey('insightType', $createdData);
        $this->assertEquals('compare_point_groups', $createdData['insightType']);
        $this->assertArrayHasKey('insightAction', $createdData);
        $this->assertEquals('set_custom_field', $createdData['insightAction']);

        $this->client->request('GET', '/api/points/insights');
        $getAllResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $getAllResponse->getStatusCode());
        $responseData = json_decode($getAllResponse->getContent(), true);
        $this->assertArrayHasKey('insights', $responseData);
        $this->assertEquals(1, $responseData['total']);
        $allData = $responseData['insights'];
        $this->assertIsArray($allData);
        $this->assertArrayHasKey(0, $allData);
        $this->assertCount(1, $allData);

        $updatePayload = [
            'name'          => 'Updated Point Insight',
            'insightType'   => 'compare_point_groups',
            'insightAction' => 'set_custom_field',
        ];

        $this->client->request('PATCH', "/api/points/insights/{$createdData['id']}/edit", $updatePayload);
        $updateResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $updateResponse->getStatusCode());
        $responseData = json_decode($updateResponse->getContent(), true);
        $this->assertArrayHasKey('insight', $responseData);
        $updatedData = $responseData['insight'];
        $this->assertEquals('Updated Point Insight', $updatedData['name']);

        $this->client->request('DELETE', "/api/points/insights/{$createdData['id']}/delete");
        $deleteResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $deleteResponse->getStatusCode());
        $responseData = json_decode($deleteResponse->getContent(), true);
        $this->assertArrayHasKey('insight', $responseData);
        $deleteData = $responseData['insight'];
        $this->assertEquals('Updated Point Insight', $deleteData['name']);

        $this->client->request('GET', "/api/points/insights/{$createdData['id']}");
        $getResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $getResponse->getStatusCode());
        $responseData = json_decode($getResponse->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertCount(1, $responseData['errors']);
        $this->assertSame(Response::HTTP_NOT_FOUND, $responseData['errors'][0]['code']);
        $this->assertSame($translator->trans('mautic.core.error.notfound', [], 'flashes'), $responseData['errors'][0]['message']);
    }
}
