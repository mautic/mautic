<?php

declare(strict_types=1);

namespace Mautic\PointBundle\Tests\Functional\Controller;

use Mautic\PointBundle\Entity\Trigger;
use Mautic\ProjectBundle\Tests\Functional\AbstractProjectSearchTestCase;

final class TriggerProjectSearchFunctionalTest extends AbstractProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $triggerAlpha = $this->createTrigger('Trigger Alpha');
        $triggerBeta  = $this->createTrigger('Trigger Beta');
        $this->createTrigger('Trigger Gamma');
        $this->createTrigger('Trigger Delta');

        $triggerAlpha->addProject($projectOne);
        $triggerAlpha->addProject($projectTwo);
        $triggerBeta->addProject($projectTwo);
        $triggerBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/points/triggers', '/s/points/triggers']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Trigger Alpha', 'Trigger Beta'],
            'unexpectedEntities'  => ['Trigger Gamma', 'Trigger Delta'],
        ];

        yield 'search by one project AND trigger name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Trigger Beta'],
            'unexpectedEntities'  => ['Trigger Alpha', 'Trigger Gamma', 'Trigger Delta'],
        ];

        yield 'search by one project OR trigger name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Trigger Alpha', 'Trigger Beta', 'Trigger Gamma'],
            'unexpectedEntities'  => ['Trigger Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Trigger Gamma', 'Trigger Delta'],
            'unexpectedEntities'  => ['Trigger Alpha', 'Trigger Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Trigger Beta'],
            'unexpectedEntities'  => ['Trigger Alpha', 'Trigger Gamma', 'Trigger Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Trigger Gamma', 'Trigger Delta'],
            'unexpectedEntities'  => ['Trigger Alpha', 'Trigger Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Trigger Alpha', 'Trigger Beta'],
            'unexpectedEntities'  => ['Trigger Gamma', 'Trigger Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Trigger Alpha', 'Trigger Gamma', 'Trigger Delta'],
            'unexpectedEntities'  => ['Trigger Beta'],
        ];
    }

    private function createTrigger(string $name): Trigger
    {
        $trigger = new Trigger();
        $trigger->setName($name);
        $this->em->persist($trigger);

        return $trigger;
    }
}
