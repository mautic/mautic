<?php

declare(strict_types=1);

namespace Mautic\PageBundle\Tests\Controller;

use Mautic\PageBundle\Entity\Page;
use Mautic\ProjectBundle\Tests\Functional\AbstratctProjectSearchTestCase;

final class PageProjectSearchFunctionalTest extends AbstratctProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $pageAlpha = $this->createPage('Page Alpha');
        $pageBeta  = $this->createPage('Page Beta');
        $this->createPage('Page Gamma');
        $this->createPage('Page Delta');

        $pageAlpha->addProject($projectOne);
        $pageAlpha->addProject($projectTwo);
        $pageBeta->addProject($projectTwo);
        $pageBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/pages', '/s/pages']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Page Alpha', 'Page Beta'],
            'unexpectedEntities'  => ['Page Gamma', 'Page Delta'],
        ];

        yield 'search by one project AND page name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Page Beta'],
            'unexpectedEntities'  => ['Page Alpha', 'Page Gamma', 'Page Delta'],
        ];

        yield 'search by one project OR page name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Page Alpha', 'Page Beta', 'Page Gamma'],
            'unexpectedEntities'  => ['Page Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Page Gamma', 'Page Delta'],
            'unexpectedEntities'  => ['Page Alpha', 'Page Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Page Beta'],
            'unexpectedEntities'  => ['Page Alpha', 'Page Gamma', 'Page Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Page Gamma', 'Page Delta'],
            'unexpectedEntities'  => ['Page Alpha', 'Page Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Page Alpha', 'Page Beta'],
            'unexpectedEntities'  => ['Page Gamma', 'Page Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Page Alpha', 'Page Gamma', 'Page Delta'],
            'unexpectedEntities'  => ['Page Beta'],
        ];
    }

    private function createPage(string $name): Page
    {
        $page = new Page();
        $page->setTitle($name);
        $page->setAlias($name);
        $this->em->persist($page);

        return $page;
    }
}
