<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProjectApiControllerTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        $this->configParams['api_enabled'] = true;

        parent::setUp();

        $project1 = new Project();
        $project1->setName('Project 1');
        $project1->setDescription('First Project');
        $this->em->persist($project1);

        $project2 = new Project();
        $project2->setName('Project 2');
        $project2->setDescription('Second Project');
        $this->em->persist($project2);

        $this->em->flush();
    }

    public function testGetBulkProjects(): void
    {
        $this->client->request(Request::METHOD_GET, '/api/projects');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(2, $content['projects'], 'The bulk GET API should return all projects');
    }

    public function testGetProject(): void
    {
        $projectId = $this->em->getRepository(Project::class)->findOneBy(['name' => 'Project 1'])->getId();
        $this->client->request(Request::METHOD_GET, '/api/projects/'.$projectId);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Project 1', $content['project']['name']);
        $this->assertEquals('First Project', $content['project']['description']);
        $this->assertCount(1, $content, 'The GET API should return the requested project');
    }

    public function testCreateProject(): void
    {
        $payload = ['name' => 'Project 3', 'description' => 'Third Project'];
        $this->client->request(Request::METHOD_POST, '/api/projects/new', $payload);

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->assertCount(3, $this->em->getRepository(Project::class)->findAll());
        $this->assertNotNull($this->em->getRepository(Project::class)->findOneBy(['name' => 'Project 3']));
    }

    public function testUpdateProject(): void
    {
        $projectId = $this->em->getRepository(Project::class)->findOneBy(['name' => 'Project 1'])->getId();

        $payload = ['description' => 'Updated First Project'];
        $this->client->request(Request::METHOD_PATCH, '/api/projects/'.$projectId.'/edit', $payload);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $updatedProject = $this->em->getRepository(Project::class)->find($projectId);

        $this->assertEquals('Project 1', $updatedProject->getName());
        $this->assertEquals('Updated First Project', $updatedProject->getDescription(), 'The project should be updated');
    }

    public function testDeleteProject(): void
    {
        $projectId = $this->em->getRepository(Project::class)->findOneBy(['name' => 'Project 1'])->getId();

        $this->client->request(Request::METHOD_DELETE, '/api/projects/'.$projectId.'/delete');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertCount(1, $this->em->getRepository(Project::class)->findAll());
        $this->assertNull($this->em->getRepository(Project::class)->find($projectId));
    }

    public function testBulkDeleteProjects(): void
    {
        $project1Id = $this->em->getRepository(Project::class)->findOneBy(['name' => 'Project 1'])->getId();
        $project2Id = $this->em->getRepository(Project::class)->findOneBy(['name' => 'Project 2'])->getId();

        $payload = [$project1Id, $project2Id];

        $this->client->request(Request::METHOD_DELETE, '/api/projects/batch/delete?ids='.implode(',', $payload));

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $this->assertCount(0, $this->em->getRepository(Project::class)->findAll());
    }
}
