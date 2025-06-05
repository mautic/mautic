<?php

declare(strict_types=1);

namespace Mautic\ProjectBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\ProjectBundle\Entity\Project;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;

/**
 * This class should simplify writing functional tests for project search functionality on various entities.
 */
abstract class AbstratctProjectSearchTestCase extends MauticMysqlTestCase
{
    /**
     * @param string[] $expectedEntities
     * @param string[] $unexpectedEntities
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
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

            $content = $crawler->count() ? $crawler->filter('body')->text() : $this->client->getResponse()->getContent();

            foreach ($expectedEntities as $expectedEntity) {
                Assert::assertStringContainsString($expectedEntity, $content);
            }

            foreach ($unexpectedEntities as $unexpectedEntity) {
                Assert::assertStringNotContainsString($unexpectedEntity, $content);
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
}
