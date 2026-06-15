<?php

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Response;

class FieldGroupApiControllerFunctionalTest extends MauticMysqlTestCase
{
    public function testFieldGroupWorkflow(): void
    {
        // Create
        $this->client->request('POST', '/api/field-groups/new', [
            'name'        => 'Billing',
            'description' => 'Invoicing fields',
        ]);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED, 'Return code must be 201.');

        $groupId = $response['fieldGroup']['id'];
        $this->assertGreaterThan(0, $groupId);
        $this->assertSame('Billing', $response['fieldGroup']['name']);
        $this->assertSame('billing', $response['fieldGroup']['alias'], 'Alias must be auto-generated from the name.');

        // Rename via PATCH — alias must stay immutable
        $this->client->request('PATCH', "/api/field-groups/{$groupId}/edit", [
            'name'  => 'Billing Updated',
            'alias' => 'should_be_ignored',
        ]);
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        $this->assertSame('Billing Updated', $response['fieldGroup']['name']);
        $this->assertSame('billing', $response['fieldGroup']['alias'], 'Alias must not change on rename or via payload.');

        // Get one
        $this->client->request('GET', "/api/field-groups/{$groupId}");
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        $this->assertSame($groupId, $response['fieldGroup']['id']);

        // List
        $this->client->request('GET', '/api/field-groups');
        $response = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThanOrEqual(1, (int) $response['total']);

        // Delete
        $this->client->request('DELETE', "/api/field-groups/{$groupId}/delete");
        $this->assertResponseIsSuccessful($this->client->getResponse()->getContent());

        // Confirm gone
        $this->client->request('GET', "/api/field-groups/{$groupId}");
        $this->assertResponseStatusCodeSame(404);
    }

    public function testCreateRequiresName(): void
    {
        $this->client->request('POST', '/api/field-groups/new', ['description' => 'no name']);
        // Validation failure on the required name field (400 Bad Request or 422 Unprocessable Entity).
        $status = $this->client->getResponse()->getStatusCode();
        $this->assertContains($status, [Response::HTTP_BAD_REQUEST, Response::HTTP_UNPROCESSABLE_ENTITY], 'Missing required name must fail validation.');
    }
}
