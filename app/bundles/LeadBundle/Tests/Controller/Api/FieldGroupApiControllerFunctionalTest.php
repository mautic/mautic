<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\FieldGroup;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldGroupModel;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\HttpFoundation\Response;

final class FieldGroupApiControllerFunctionalTest extends MauticMysqlTestCase
{
    // The delete-guard test creates a LeadField (schema change), which commits
    // the cleanup transaction; disable rollback so each test gets a fresh schema.
    protected $useCleanupRollback = false;

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

    /**
     * A group that still has fields must NOT be deletable through the REST API;
     * deleting it would orphan the lead_fields rows that reference its alias.
     * Expected post-fix: 409 Conflict, group and field left intact.
     */
    public function testApiDeleteIsBlockedWhenGroupHasFields(): void
    {
        $fieldGroupModel = self::getContainer()->get(FieldGroupModel::class);
        $group           = new FieldGroup();
        $group->setName('Api Guarded');
        $fieldGroupModel->saveEntity($group);
        $groupId = $group->getId();

        $fieldModel = self::getContainer()->get(FieldModel::class);
        $field      = new LeadField();
        $field->setLabel('Api Guard Field');
        $field->setAlias('api_guard_field');
        $field->setType('text');
        $field->setObject('lead');
        $field->setGroup($group->getAlias());
        $fieldModel->saveEntity($field);

        $this->client->request('DELETE', "/api/field-groups/{$groupId}/delete");
        $this->assertResponseStatusCodeSame(
            Response::HTTP_CONFLICT,
            'Deleting a group that still has fields must be rejected with 409.'
        );

        // The group and its field must survive the rejected delete.
        $this->em->clear();
        $this->assertNotNull(
            $fieldGroupModel->getRepository()->find($groupId),
            'A group with fields must not be deleted via the API.'
        );
        $this->assertNotNull(
            $fieldModel->getRepository()->findOneBy(['alias' => 'api_guard_field']),
            'The assigned field must remain intact.'
        );
    }

    public function testCreateRequiresName(): void
    {
        $this->client->request('POST', '/api/field-groups/new', ['description' => 'no name']);
        // Validation failure on the required name field (400 Bad Request or 422 Unprocessable Entity).
        $status = $this->client->getResponse()->getStatusCode();
        $this->assertContains($status, [Response::HTTP_BAD_REQUEST, Response::HTTP_UNPROCESSABLE_ENTITY], 'Missing required name must fail validation.');
    }
}
