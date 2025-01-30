<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\PointsChangeLog;
use Mautic\PointBundle\Entity\Group;
use Symfony\Component\HttpFoundation\Response;

final class PointGroupsApiControllerTest extends MauticMysqlTestCase
{
    public function testPointGroupCRUDActions(): void
    {
        /** @var Translator $translator */
        $translator = static::getContainer()->get('translator');

        // Create a new point group
        $this->client->request('POST', '/api/points/groups/new', [
            'name'        => 'New Point Group',
            'description' => 'Description of the new point group',
        ]);

        $createResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_CREATED, $createResponse->getStatusCode());
        $responseData = json_decode($createResponse->getContent(), true);
        $this->assertArrayHasKey('pointGroup', $responseData);
        $createdData = $responseData['pointGroup'];
        $this->assertArrayHasKey('id', $createdData);
        $this->assertEquals('New Point Group', $createdData['name']);
        $this->assertEquals('Description of the new point group', $createdData['description']);

        // Retrieve all point groups
        $this->client->request('GET', '/api/points/groups');
        $getAllResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $getAllResponse->getStatusCode());
        $responseData = json_decode($getAllResponse->getContent(), true);
        $this->assertArrayHasKey('pointGroups', $responseData);
        $this->assertEquals(1, $responseData['total']);
        $allData = $responseData['pointGroups'];
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
        $this->assertArrayHasKey('pointGroup', $responseData);
        $updatedData = $responseData['pointGroup'];
        $this->assertEquals('Updated Point Group Name', $updatedData['name']);
        $this->assertEquals('Updated description of the point group', $updatedData['description']);

        // Delete the created point group
        $this->client->request('DELETE', "/api/points/groups/{$createdData['id']}/delete");
        $deleteResponse = $this->client->getResponse();

        $this->assertSame(Response::HTTP_OK, $deleteResponse->getStatusCode());
        $responseData = json_decode($deleteResponse->getContent(), true);
        $this->assertArrayHasKey('pointGroup', $responseData);
        $deleteData = $responseData['pointGroup'];
        $this->assertEquals('Updated Point Group Name', $deleteData['name']);
        $this->assertEquals('Updated description of the point group', $deleteData['description']);

        // Try to GET the group that should no longer exist
        $this->client->request('GET', "/api/points/groups/{$createdData['id']}");
        $getResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $getResponse->getStatusCode());
        $responseData = json_decode($getResponse->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertCount(1, $responseData['errors']);
        $this->assertSame(Response::HTTP_NOT_FOUND, $responseData['errors'][0]['code']);
        $this->assertSame($translator->trans('mautic.core.error.notfound', [], 'flashes'), $responseData['errors'][0]['message']);
    }

    public function testContactGroupPointsActions(): void
    {
        /** @var Translator $translator */
        $translator = static::getContainer()->get('translator');

        // Arrange
        $contact     = $this->createContact('test@example.com');
        $pointGroupA = $this->createGroup('Group A');
        $pointGroupB = $this->createGroup('Group B');
        $this->em->flush();

        // Act & Assert
        $this->adjustPointsAndAssert($contact, $pointGroupA, 'plus', 10, 10);
        $this->adjustPointsAndAssert($contact, $pointGroupA, 'minus', 2, 8);
        $this->adjustPointsAndAssert($contact, $pointGroupA, 'divide', 2, 4);
        $this->adjustPointsAndAssert($contact, $pointGroupA, 'times', 4, 16);
        $this->adjustPointsAndAssert($contact, $pointGroupB, 'set', 21, 21);

        // Test GET all contact's point groups endpoint
        $this->assertContactPointGroups($contact, [
            [
                'score' => 16,
                'group' => [
                    'id'          => $pointGroupA->getId(),
                    'name'        => 'Group A',
                    'description' => '',
                ],
            ],
            [
                'score' => 21,
                'group' => [
                    'id'          => $pointGroupB->getId(),
                    'name'        => 'Group B',
                    'description' => '',
                ],
            ],
        ]);

        // Test GET single contact's point group endpoint
        $this->assertContactSinglePointGroup($contact, $pointGroupA, 16);
        $this->assertContactSinglePointGroup($contact, $pointGroupB, 21);

        $this->assertPointsChangeLogEntries($contact, [
            ['delta' => 10, 'groupId' => $pointGroupA->getId()],
            ['delta' => -2, 'groupId' => $pointGroupA->getId()],
            ['delta' => -4, 'groupId' => $pointGroupA->getId()],
            ['delta' => 12, 'groupId' => $pointGroupA->getId()],
            ['delta' => 21, 'groupId' => $pointGroupB->getId()],
        ]);

        // Try to GET the group points that should not exist
        $this->client->request('GET', "/api/contacts/{$contact->getId()}/points/groups/0");
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertCount(1, $responseData['errors']);
        $this->assertSame(Response::HTTP_NOT_FOUND, $responseData['errors'][0]['code']);
        $this->assertSame($translator->trans('mautic.lead.event.api.point.group.not.found'), $responseData['errors'][0]['message']);

        // Try to GET the group points for a contact that should not exist
        $this->client->request('GET', '/api/contacts/0/points/groups/0');
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('errors', $responseData);
        $this->assertCount(1, $responseData['errors']);
        $this->assertSame(Response::HTTP_NOT_FOUND, $responseData['errors'][0]['code']);
        $this->assertSame($translator->trans('mautic.lead.event.api.lead.not.found'), $responseData['errors'][0]['message']);
    }

    private function adjustPointsAndAssert(Lead $contact, Group $pointGroup, string $operator, int $value, int $expectedScore): void
    {
        $this->client->request('POST', "/api/contacts/{$contact->getId()}/points/groups/{$pointGroup->getId()}/$operator/{$value}");
        $adjustResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $adjustResponse->getStatusCode());
        $responseData = json_decode($adjustResponse->getContent(), true);
        $this->assertSame($expectedScore, $responseData['groupScore']['score']);
    }

    /**
     * @param array<int, array<string, mixed>> $expectedGroups
     */
    private function assertContactPointGroups(Lead $contact, array $expectedGroups): void
    {
        $this->client->request('GET', "/api/contacts/{$contact->getId()}/points/groups");
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertSame(count($expectedGroups), $responseData['total']);
        $this->assertSame($expectedGroups, $responseData['groupScores']);
    }

    private function assertContactSinglePointGroup(Lead $contact, Group $pointGroup, int $expectedScore): void
    {
        $this->client->request('GET', "/api/contacts/{$contact->getId()}/points/groups/{$pointGroup->getId()}");
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $responseData = json_decode($response->getContent(), true);
        $this->assertSame($expectedScore, $responseData['groupScore']['score']);
    }

    /**
     * @param array<int, array<string, mixed>> $expectedEntries
     */
    private function assertPointsChangeLogEntries(Lead $contact, array $expectedEntries): void
    {
        $logs = $this->em->getRepository(PointsChangeLog::class)->findBy(['lead' => $contact->getId()]);
        $this->assertCount(count($expectedEntries), $logs);
        foreach ($expectedEntries as $index => $expectedEntry) {
            $this->assertEquals($expectedEntry['delta'], $logs[$index]->getDelta());
            $this->assertEquals($expectedEntry['groupId'], $logs[$index]->getGroup()->getId());
        }
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->em->persist($contact);

        return $contact;
    }

    private function createGroup(
        string $name,
    ): Group {
        $group = new Group();
        $group->setName($name);
        $this->em->persist($group);

        return $group;
    }
}
