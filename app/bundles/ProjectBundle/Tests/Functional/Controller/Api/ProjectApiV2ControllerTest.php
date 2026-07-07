<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProjectApiV2ControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['api_enabled'] = true;

        parent::setUp();

        $this->createProject('API V2 Project 1', 'First API V2 Project');
        $this->createProject('API V2 Project 2', 'Second API V2 Project');
    }

    public function testFetchAllProjectsV2(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/v2/projects',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = $this->getJsonResponseContent();

        $this->assertIsArray($content);
        $this->assertArrayHasKey('member', $content);
        $this->assertArrayHasKey('totalItems', $content);
        $this->assertCount(2, $content['member'], 'Should return all projects');

        $projectNames = array_map(fn (array $project) => $project['name'], $content['member']);
        $this->assertContains('API V2 Project 1', $projectNames);
        $this->assertContains('API V2 Project 2', $projectNames);
    }

    public function testFetchProjectByIdV2(): void
    {
        $projectId = $this->getProjectIdByName('API V2 Project 1');

        $this->client->request(
            Request::METHOD_GET,
            '/api/v2/projects/'.$projectId,
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ]
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $content = $this->getJsonResponseContent();

        $this->assertIsArray($content);
        $this->assertArrayHasKey('id', $content);

        $this->assertSame($projectId, $content['id']);
        $this->assertSame('API V2 Project 1', $content['name']);
        $this->assertSame('First API V2 Project', $content['description']);
        $this->assertArrayHasKey('properties', $content);
    }

    public function testFetchProjectByIdV2ReturnsNotFound(): void
    {
        $this->client->request(
            Request::METHOD_GET,
            '/api/v2/projects/99999',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ]
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testCreateNewProjectV2(): void
    {
        $payload = [
            'name'        => 'API V2 Project 3',
            'description' => 'Third API V2 Project',
            'properties'  => ['key' => 'value'],
        ];

        $this->client->request(
            Request::METHOD_POST,
            '/api/v2/projects',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ],
            json_encode($payload)
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $content = $this->getJsonResponseContent();

        $this->assertIsArray($content);
        $this->assertArrayHasKey('id', $content);

        $this->assertIsInt($content['id']);
        $this->assertSame('API V2 Project 3', $content['name']);
        $this->assertSame('Third API V2 Project', $content['description']);
        $this->assertSame(['key' => 'value'], $content['properties']);

        $persistedProject = $this->getProjectByName('API V2 Project 3');
        $this->assertNotNull($persistedProject);
        $this->assertCount(3, $this->getAllProjects());
    }

    public function testCreateProjectV2WithMinimalPayload(): void
    {
        $payload = [
            'name' => 'Minimal Project',
        ];

        $this->client->request(
            Request::METHOD_POST,
            '/api/v2/projects',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ],
            json_encode($payload)
        );

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $content = $this->getJsonResponseContent();

        $this->assertSame('Minimal Project', $content['name']);
        $this->assertNull($content['description'] ?? null);
    }

    #[DataProvider('invalidCreateProjectV2PayloadProvider')]
    public function testCreateProjectV2ValidationErrors(array $payload, int $expectedStatusCode): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/api/v2/projects',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ],
            json_encode($payload)
        );

        $this->assertResponseStatusCodeSame($expectedStatusCode);
    }

    public function testDeleteProjectByIdV2(): void
    {
        $projectId = $this->getProjectIdByName('API V2 Project 1');

        $this->client->request(Request::METHOD_DELETE, '/api/v2/projects/'.$projectId);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        $deletedProject = $this->em->getRepository(Project::class)->find($projectId);
        $this->assertNull($deletedProject, 'Project should be deleted from database');

        $this->assertCount(1, $this->getAllProjects());
    }

    public function testDeleteProjectByIdV2ReturnsNotFound(): void
    {
        $this->client->request(Request::METHOD_DELETE, '/api/v2/projects/99999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testDeleteProjectByIdV2WithInvalidId(): void
    {
        $this->client->request(Request::METHOD_DELETE, '/api/v2/projects/invalid');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    /**
     * @return iterable<string, array{0: array<string, mixed>, 1: int}>
     */
    public static function invalidCreateProjectV2PayloadProvider(): iterable
    {
        yield 'missing name' => [
            ['description' => 'Project without name'],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ];

        yield 'duplicate name (case insensitive)' => [
            ['name' => 'API V2 PROJECT 1', 'description' => 'Duplicate name'],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ];

        yield 'empty payload' => [
            [],
            Response::HTTP_UNPROCESSABLE_ENTITY,
        ];
    }

    private function createProject(string $name, ?string $description = null): Project
    {
        $project = new Project();
        $project->setName($name);
        $project->setDescription($description);
        $this->em->persist($project);
        $this->em->flush();

        return $project;
    }

    /**
     * @return array<string, mixed>
     */
    private function getJsonResponseContent(): array
    {
        $content = $this->client->getResponse()->getContent();
        $this->assertIsString($content);

        $decodedContent = json_decode($content, true);
        $this->assertIsArray($decodedContent);

        return $decodedContent;
    }

    private function getProjectIdByName(string $name): int
    {
        $project = $this->getProjectByName($name);

        $this->assertNotNull($project);
        $projectId = $project->getId();

        $this->assertIsInt($projectId);

        return $projectId;
    }

    private function getProjectByName(string $name): ?Project
    {
        $project = $this->em->getRepository(Project::class)->findOneBy(['name' => $name]);

        if (null !== $project) {
            $this->assertInstanceOf(Project::class, $project);
        }

        return $project;
    }

    /**
     * @return list<Project>
     */
    private function getAllProjects(): array
    {
        $projects = $this->em->getRepository(Project::class)->findAll();

        foreach ($projects as $project) {
            $this->assertInstanceOf(Project::class, $project);
        }

        return $projects;
    }
}
