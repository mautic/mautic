<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class should simplify writing functional tests for project search functionality on various entities.
 */
abstract class AbstractProjectSearchTestCase extends MauticMysqlTestCase
{
    /**
     * @param string[] $expectedEntities
     * @param string[] $unexpectedEntities
     */
    #[DataProvider('searchDataProvider')]
    abstract public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void;

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    abstract public static function searchDataProvider(): \Generator;

    /**
     * Test and assert API as well as UI.
     *
     * @param string[] $expectedEntities
     * @param string[] $unexpectedEntities
     * @param string[] $routes
     */
    protected function searchAndAssert(string $searchTerm, array $expectedEntities, array $unexpectedEntities, array $routes): void
    {
        foreach ($routes as $route) {
            $crawler = $this->client->request(Request::METHOD_GET, $route.'?search='.urlencode($searchTerm));
            $this->assertResponseIsSuccessful();
            $isApiRequest = str_starts_with($route, '/api/');

            $content = $isApiRequest ? $this->client->getResponse()->getContent() : $crawler->filter('body')->text();

            foreach ($expectedEntities as $expectedEntity) {
                $this->assertStringContainsString($expectedEntity, (string) $content);
            }

            foreach ($unexpectedEntities as $unexpectedEntity) {
                $this->assertStringNotContainsString($unexpectedEntity, (string) $content);
            }

            if ($isApiRequest) {
                $this->assertJson($content, 'API response should be of type JSON.');
                $this->assertProjectDataInApiResponse(json_decode($content, true));
            }
        }
    }

    protected function createProject(string $name): Project
    {
        $project = new Project();
        $project->setName($name);
        $this->em->persist($project);

        return $project;
    }

    /**
     * @param mixed[] $data
     */
    private function assertProjectDataInApiResponse(array $data): void
    {
        $projectData = $this->getProjectData($data);

        if (null === $projectData) {
            return;
        }

        $this->assertEqualsCanonicalizing(['id', 'name'], array_keys(reset($projectData)), 'Project data should contain only "id" and "name".');
    }

    /**
     * @param mixed[] $data
     *
     * @return mixed[]|null
     */
    private function getProjectData(array $data): ?array
    {
        foreach ($data as $key => $item) {
            if (!is_array($item)) {
                continue;
            }

            if ('projects' === $key && $item) {
                return $item;
            }

            $projectData = $this->getProjectData($item);

            if (null !== $projectData) {
                return $projectData;
            }
        }

        return null;
    }
}
