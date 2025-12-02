<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Functional\Serializer;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PageBundle\Entity\Page;
use Mautic\ProjectBundle\Entity\Project;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test to verify that PUT operations update existing entities
 * instead of creating new ones. This tests the PutProcessor fix end-to-end.
 */
final class PutOperationTest extends MauticMysqlTestCase
{
    /**
     * Test that API Platform GET endpoints work correctly.
     * This helps isolate whether the issue is with PUT specifically or API Platform in general.
     */
    public function testGetOperationWorks(): void
    {
        // Create a project
        $project = new Project();
        $project->setName('Test Project');
        $project->setDescription('Test Description');
        $this->em->persist($project);
        $this->em->flush();

        $projectId = $project->getId();

        // Make a GET request to retrieve the project
        $this->client->request('GET', '/api/v2/projects/'.$projectId);

        $this->assertResponseIsSuccessful();

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        Assert::assertArrayHasKey('id', $responseData);
        Assert::assertSame($projectId, $responseData['id']);
        Assert::assertSame('Test Project', $responseData['name']);
        Assert::assertSame('Test Description', $responseData['description']);
    }

    /**
     * Test that PUT operations work globally for different entities (Page example).
     * This verifies that our global PutProcessor fix works for all API Platform entities.
     */
    public function testPutOperationWorksGloballyForPageEntity(): void
    {
        // Create a page
        $page = new Page();
        $page->setTitle('Original Page Title');
        $page->setAlias('original-page-alias');
        $page->setMetaDescription('Original Meta Description');
        $this->em->persist($page);
        $this->em->flush();

        $originalId = $page->getId();
        Assert::assertNotNull($originalId, 'Page should have an ID after persisting');

        // Update the page via PUT request
        $this->client->request(
            'PUT',
            '/api/v2/pages/'.$originalId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ],
            json_encode([
                'title'           => 'Updated Page Title',
                'alias'           => 'updated-page-alias',
                'metaDescription' => 'Updated Meta Description',
            ])
        );

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        // The key assertion: ID should remain the same (global fix working)
        Assert::assertSame($originalId, $response['id'], 'PUT should update the existing page, not create a new one');
        Assert::assertSame('Updated Page Title', $response['title']);
        Assert::assertSame('updated-page-alias', $response['alias']);
        Assert::assertSame('Updated Meta Description', $response['metaDescription']);
    }

    /**
     * Test that PUT operation updates existing entity instead of creating a new one.
     * This is the main regression test for the EntityContextBuilder fix.
     */
    public function testPutOperationUpdatesExistingProject(): void
    {
        // Create initial project
        $project = new Project();
        $project->setName('Original Project');
        $project->setDescription('Original Description');

        $this->em->persist($project);
        $this->em->flush();

        $originalId = $project->getId();
        Assert::assertNotNull($originalId, 'Project should have an ID after persisting');

        // Update the project via PUT request
        $this->client->request(
            'PUT',
            '/api/v2/projects/'.$originalId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ],
            json_encode([
                'name'        => 'Updated Project',
                'description' => 'Updated Description',
            ])
        );

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        // The key assertion: ID should remain the same (not creating a new entity)
        Assert::assertSame($originalId, $response['id'], 'PUT should update the existing entity, not create a new one');
        Assert::assertSame('Updated Project', $response['name']);
        Assert::assertSame('Updated Description', $response['description']);

        // Verify in database that only one project exists with the updated data
        $this->em->clear();
        $projects = $this->em->getRepository(Project::class)->findAll();
        Assert::assertCount(1, $projects, 'Should only have one project in database after PUT');
        Assert::assertSame($originalId, $projects[0]->getId());
        Assert::assertSame('Updated Project', $projects[0]->getName());
        Assert::assertSame('Updated Description', $projects[0]->getDescription());
    }

    /**
     * Test that PUT request for non-existent entity returns 404.
     */
    public function testPutOperationReturns404ForNonExistentProject(): void
    {
        $nonExistentId = 99999;

        $this->client->request(
            'PUT',
            '/api/v2/projects/'.$nonExistentId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ],
            json_encode([
                'name'        => 'Test Project',
                'description' => 'Test Description',
            ])
        );

        Assert::assertSame(Response::HTTP_NOT_FOUND, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test that POST operations still work correctly (create new entities).
     */
    public function testPostOperationCreatesNewProject(): void
    {
        $this->client->request(
            'POST',
            '/api/v2/projects',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ],
            json_encode([
                'name'        => 'New Project',
                'description' => 'New Description',
            ])
        );

        $this->assertResponseIsSuccessful();

        $response = json_decode($this->client->getResponse()->getContent(), true);

        Assert::assertIsInt($response['id']);
        Assert::assertSame('New Project', $response['name']);
        Assert::assertSame('New Description', $response['description']);

        // Verify project was created in database
        $this->em->clear();
        $project = $this->em->getRepository(Project::class)->find($response['id']);
        Assert::assertNotNull($project);
        Assert::assertSame('New Project', $project->getName());
    }

    /**
     * Test that PUT operation completely replaces the resource (proper HTTP PUT semantics).
     * If a field is missing from the PUT request, it should be set to null in the existing entity.
     */
    public function testPutOperationReplacesEntireResource(): void
    {
        // Create initial project with both name and description
        $project = new Project();
        $project->setName('Original Project');
        $project->setDescription('Original Description');

        $this->em->persist($project);
        $this->em->flush();

        $originalId = $project->getId();
        Assert::assertNotNull($originalId, 'Project should have an ID after persisting');

        // Update the project via PUT request with only name (no description)
        // According to HTTP PUT semantics, this should clear the description
        $this->client->request(
            'PUT',
            '/api/v2/projects/'.$originalId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ],
            json_encode([
                'name' => 'Updated Project Name Only',
                // Note: description is intentionally omitted
            ])
        );

        Assert::assertSame(200, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);

        // Verify the response shows the field was replaced (cleared)
        Assert::assertSame($originalId, $response['id'], 'Should update existing project, not create new one');
        Assert::assertSame('Updated Project Name Only', $response['name']);

        // The API may not include null fields in the response, so check if key exists
        if (array_key_exists('description', $response)) {
            Assert::assertNull($response['description'], 'Description should be null since it was not provided in PUT request');
        }

        // Verify in database that the description was actually cleared
        $this->em->clear();
        $updatedProject = $this->em->getRepository(Project::class)->find($originalId);
        Assert::assertNotNull($updatedProject);
        Assert::assertSame('Updated Project Name Only', $updatedProject->getName());
        Assert::assertNull($updatedProject->getDescription(), 'Description should be cleared in database');
    }
}
