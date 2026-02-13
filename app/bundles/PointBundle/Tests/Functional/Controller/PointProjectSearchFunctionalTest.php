<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional\Controller;

use Mautic\PointBundle\Entity\Point;
use Mautic\ProjectBundle\Tests\Functional\AbstractProjectSearchTestCase;

final class PointProjectSearchFunctionalTest extends AbstractProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $pointAlpha = $this->createPoint('Point Alpha');
        $pointBeta  = $this->createPoint('Point Beta');
        $this->createPoint('Point Gamma');
        $this->createPoint('Point Delta');

        $pointAlpha->addProject($projectOne);
        $pointAlpha->addProject($projectTwo);
        $pointBeta->addProject($projectTwo);
        $pointBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/points', '/s/points']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Point Alpha', 'Point Beta'],
            'unexpectedEntities'  => ['Point Gamma', 'Point Delta'],
        ];

        yield 'search by one project AND point name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Point Beta'],
            'unexpectedEntities'  => ['Point Alpha', 'Point Gamma', 'Point Delta'],
        ];

        yield 'search by one project OR point name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Point Alpha', 'Point Beta', 'Point Gamma'],
            'unexpectedEntities'  => ['Point Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Point Gamma', 'Point Delta'],
            'unexpectedEntities'  => ['Point Alpha', 'Point Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Point Beta'],
            'unexpectedEntities'  => ['Point Alpha', 'Point Gamma', 'Point Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Point Gamma', 'Point Delta'],
            'unexpectedEntities'  => ['Point Alpha', 'Point Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Point Alpha', 'Point Beta'],
            'unexpectedEntities'  => ['Point Gamma', 'Point Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Point Alpha', 'Point Gamma', 'Point Delta'],
            'unexpectedEntities'  => ['Point Beta'],
        ];
    }

    private function createPoint(string $name): Point
    {
        $point = new Point();
        $point->setName($name);
        $point->setType('url.hit');
        $this->em->persist($point);

        return $point;
    }
}
