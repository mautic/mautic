<?php

declare(strict_types=1);

namespace MauticPlugin\MauticSocialBundle\Tests\Functional\V2API;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use MauticPlugin\MauticSocialBundle\Entity\Monitoring;
use Symfony\Component\HttpFoundation\Response;

final class MonitoringV2ApiTest extends MauticMysqlTestCase
{
    private function createMonitoring(
        string $title = 'Test Monitoring',
        string $networkType = 'type',
        ?string $description = null,
    ): Monitoring {
        $monitoring = new Monitoring();
        $monitoring->setTitle($title);
        $monitoring->setNetworkType($networkType);
        if (null !== $description) {
            $monitoring->setDescription($description);
        }
        $this->em->persist($monitoring);
        $this->em->flush();

        return $monitoring;
    }

    /**
     * @param array<string,mixed>  $data
     * @param array<string,string> $headers
     *
     * @return array<string,?mixed>
     */
    private function sendRequest(
        string $method,
        string $uri,
        array $data = [],
        array $headers = [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT'  => 'application/ld+json',
        ],
    ): array {
        $this->client->request(
            $method,
            $uri,
            [],
            [],
            $headers,
            !empty($data) ? json_encode($data) : null
        );

        return [
            'status'  => $this->client->getResponse()->getStatusCode(),
            'content' => $this->client->getResponse()->getContent(),
        ];
    }

    public function testGetOperationWorks(): void
    {
        $monitoring   = $this->createMonitoring();
        $monitoringId = $monitoring->getId();

        $response = $this->sendRequest('GET', '/api/v2/monitorings/'.$monitoringId);

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response['content'], true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertSame($monitoringId, $responseData['id']);
        $this->assertSame('Test Monitoring', $responseData['title']);
        $this->assertSame('type', $responseData['networkType']);
        $this->assertArrayHasKey('uuid', $responseData);
    }

    public function testPutOperationWorksGloballyForMonitoringEntity(): void
    {
        $monitoring = $this->createMonitoring('Original Monitoring', 'type', 'Test Description');
        $originalId = $monitoring->getId();

        $response = $this->sendRequest(
            'PUT',
            '/api/v2/monitorings/'.$originalId,
            [
                'title'       => 'Updated Monitoring',
                'networkType' => 'type',
                'description' => 'Test Description Updated',
            ]
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response['content'], true);

        $this->assertSame($originalId, $responseData['id']);
        $this->assertSame('Updated Monitoring', $responseData['title']);
        $this->assertSame('Test Description Updated', $responseData['description']);
    }

    public function testPutOperationUpdatesExistingMonitoring(): void
    {
        $monitoring = $this->createMonitoring('Original Monitoring');
        $originalId = $monitoring->getId();

        $response = $this->sendRequest(
            'PUT',
            '/api/v2/monitorings/'.$originalId,
            [
                'title'       => 'Updated Monitoring',
                'networkType' => 'type',
            ]
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response['content'], true);

        $this->assertSame($originalId, $responseData['id']);
        $this->assertSame('Updated Monitoring', $responseData['title']);
        $this->assertSame('type', $responseData['networkType']);

        $this->em->clear();
        $monitorings = $this->em->getRepository(Monitoring::class)->findAll();
        $this->assertCount(1, $monitorings);
        $this->assertSame($originalId, $monitorings[0]->getId());
        $this->assertSame('Updated Monitoring', $monitorings[0]->getTitle());
        $this->assertSame('type', $monitorings[0]->getNetworkType());
    }

    public function testPutOperationReturns404ForNonExistentMonitoring(): void
    {
        $nonExistentId = 99999;

        $response = $this->sendRequest(
            'PUT',
            '/api/v2/monitorings/'.$nonExistentId,
            [
                'title'       => 'Test Monitoring',
                'networkType' => 'type',
            ]
        );

        $this->assertSame(Response::HTTP_NOT_FOUND, $response['status']);
    }

    public function testPostOperationCreatesNewMonitoring(): void
    {
        $response = $this->sendRequest(
            'POST',
            '/api/v2/monitorings',
            [
                'title'       => 'New Monitoring',
                'networkType' => 'type',
            ]
        );

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($response['content'], true);

        $this->assertIsInt($responseData['id']);
        $this->assertSame('New Monitoring', $responseData['title']);
        $this->assertSame('type', $responseData['networkType']);

        $this->em->clear();
        $monitoring = $this->em->getRepository(Monitoring::class)->find($responseData['id']);
        $this->assertInstanceOf(Monitoring::class, $monitoring);
        $this->assertSame('New Monitoring', $monitoring->getTitle());
    }

    public function testPutOperationReplacesEntireResource(): void
    {
        $monitoring = $this->createMonitoring('Original Monitoring', 'type', 'Original Description');
        $originalId = $monitoring->getId();

        $response = $this->sendRequest(
            'PUT',
            '/api/v2/monitorings/'.$originalId,
            [
                'title'       => 'Updated Monitoring Title Only',
                'networkType' => 'type',
                // description intentionally omitted
            ]
        );

        $this->assertSame(200, $response['status']);

        $responseData = json_decode($response['content'], true);

        $this->assertSame($originalId, $responseData['id']);
        $this->assertSame('Updated Monitoring Title Only', $responseData['title']);

        if (array_key_exists('description', $responseData)) {
            $this->assertNull($responseData['description']);
        }

        $this->em->clear();
        $updatedMonitoring = $this->em->getRepository(Monitoring::class)->find($originalId);
        $this->assertInstanceOf(Monitoring::class, $updatedMonitoring);
        $this->assertSame('Updated Monitoring Title Only', $updatedMonitoring->getTitle());
        $this->assertNull($updatedMonitoring->getDescription());
    }

    public function testDeleteOperationWorks(): void
    {
        $monitoring   = $this->createMonitoring();
        $monitoringId = $monitoring->getId();

        $this->sendRequest('DELETE', '/api/v2/monitorings/'.$monitoringId);

        $this->assertResponseIsSuccessful();

        $this->em->clear();
        $monitoring = $this->em->getRepository(Monitoring::class)->find($monitoringId);
        $this->assertNotInstanceOf(Monitoring::class, $monitoring);
    }

    public function testPatchOperationReplacesOnlyResourceProperty(): void
    {
        $monitoring = $this->createMonitoring('Original Monitoring', 'type', 'Original Description');
        $originalId = $monitoring->getId();

        $response = $this->sendRequest(
            'PATCH',
            '/api/v2/monitorings/'.$originalId,
            [
                'title'       => 'Updated Monitoring Title Only',
                // description intentionally omitted
            ],
            [
                'CONTENT_TYPE' => 'application/merge-patch+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ]
        );

        $this->assertSame(200, $response['status']);

        $responseData = json_decode($response['content'], true);

        $this->assertSame($originalId, $responseData['id']);
        $this->assertSame('Updated Monitoring Title Only', $responseData['title']);
        $this->assertSame('Original Description', $responseData['description']);

        $this->em->clear();
        $updatedMonitoring = $this->em->getRepository(Monitoring::class)->find($originalId);
        $this->assertInstanceOf(Monitoring::class, $updatedMonitoring);
        $this->assertSame('Updated Monitoring Title Only', $updatedMonitoring->getTitle());
        $this->assertSame('Original Description', $updatedMonitoring->getDescription());
    }
}
