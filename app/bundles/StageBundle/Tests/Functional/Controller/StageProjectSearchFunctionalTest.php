<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Controller;

use Mautic\ProjectBundle\Tests\Functional\AbstractProjectSearchTestCase;
use Mautic\StageBundle\Entity\Stage;

final class StageProjectSearchFunctionalTest extends AbstractProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $stageAlpha = $this->createStage('Stage Alpha');
        $stageBeta  = $this->createStage('Stage Beta');
        $this->createStage('Stage Gamma');
        $this->createStage('Stage Delta');

        $stageAlpha->addProject($projectOne);
        $stageAlpha->addProject($projectTwo);
        $stageBeta->addProject($projectTwo);
        $stageBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/stages', '/s/stages']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Stage Alpha', 'Stage Beta'],
            'unexpectedEntities'  => ['Stage Gamma', 'Stage Delta'],
        ];

        yield 'search by one project AND stage name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Stage Beta'],
            'unexpectedEntities'  => ['Stage Alpha', 'Stage Gamma', 'Stage Delta'],
        ];

        yield 'search by one project OR stage name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Stage Alpha', 'Stage Beta', 'Stage Gamma'],
            'unexpectedEntities'  => ['Stage Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Stage Gamma', 'Stage Delta'],
            'unexpectedEntities'  => ['Stage Alpha', 'Stage Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Stage Beta'],
            'unexpectedEntities'  => ['Stage Alpha', 'Stage Gamma', 'Stage Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Stage Gamma', 'Stage Delta'],
            'unexpectedEntities'  => ['Stage Alpha', 'Stage Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Stage Alpha', 'Stage Beta'],
            'unexpectedEntities'  => ['Stage Gamma', 'Stage Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Stage Alpha', 'Stage Gamma', 'Stage Delta'],
            'unexpectedEntities'  => ['Stage Beta'],
        ];
    }

    private function createStage(string $name): Stage
    {
        $stage = new Stage();
        $stage->setName($name);
        $this->em->persist($stage);

        return $stage;
    }
}
