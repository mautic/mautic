<?php

declare(strict_types=1);

namespace Mautic\DynamicContentBundle\Tests\Controller;

use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\ProjectBundle\Tests\Functional\AbstractProjectSearchTestCase;

final class DynamicContentProjectSearchFunctionalTest extends AbstractProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $dynamicContentAlpha = $this->createDynamicContent('DynamicContent Alpha');
        $dynamicContentBeta  = $this->createDynamicContent('DynamicContent Beta');
        $this->createDynamicContent('DynamicContent Gamma');
        $this->createDynamicContent('DynamicContent Delta');

        $dynamicContentAlpha->addProject($projectOne);
        $dynamicContentAlpha->addProject($projectTwo);
        $dynamicContentBeta->addProject($projectTwo);
        $dynamicContentBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/dynamiccontents', '/s/dwc']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['DynamicContent Alpha', 'DynamicContent Beta'],
            'unexpectedEntities'  => ['DynamicContent Gamma', 'DynamicContent Delta'],
        ];

        yield 'search by one project AND dynamicContent name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['DynamicContent Beta'],
            'unexpectedEntities'  => ['DynamicContent Alpha', 'DynamicContent Gamma', 'DynamicContent Delta'],
        ];

        yield 'search by one project OR dynamicContent name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['DynamicContent Alpha', 'DynamicContent Beta', 'DynamicContent Gamma'],
            'unexpectedEntities'  => ['DynamicContent Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['DynamicContent Gamma', 'DynamicContent Delta'],
            'unexpectedEntities'  => ['DynamicContent Alpha', 'DynamicContent Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['DynamicContent Beta'],
            'unexpectedEntities'  => ['DynamicContent Alpha', 'DynamicContent Gamma', 'DynamicContent Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['DynamicContent Gamma', 'DynamicContent Delta'],
            'unexpectedEntities'  => ['DynamicContent Alpha', 'DynamicContent Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['DynamicContent Alpha', 'DynamicContent Beta'],
            'unexpectedEntities'  => ['DynamicContent Gamma', 'DynamicContent Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['DynamicContent Alpha', 'DynamicContent Gamma', 'DynamicContent Delta'],
            'unexpectedEntities'  => ['DynamicContent Beta'],
        ];
    }

    private function createDynamicContent(string $name): DynamicContent
    {
        $dynamicContent = new DynamicContent();
        $dynamicContent->setName($name);
        $this->em->persist($dynamicContent);

        return $dynamicContent;
    }
}
