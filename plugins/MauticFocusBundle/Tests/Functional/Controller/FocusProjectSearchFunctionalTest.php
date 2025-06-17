<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFocusBundle\Tests\Functional\Controller;

use Mautic\ProjectBundle\Tests\Functional\AbstratctProjectSearchTestCase;
use MauticPlugin\MauticFocusBundle\Entity\Focus;

final class FocusProjectSearchFunctionalTest extends AbstratctProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $focusAlpha = $this->createFocus('Focus Alpha');
        $focusBeta  = $this->createFocus('Focus Beta');
        $this->createFocus('Focus Gamma');
        $this->createFocus('Focus Delta');

        $focusAlpha->addProject($projectOne);
        $focusAlpha->addProject($projectTwo);
        $focusBeta->addProject($projectTwo);
        $focusBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/focus', '/s/focus']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Focus Alpha', 'Focus Beta'],
            'unexpectedEntities'  => ['Focus Gamma', 'Focus Delta'],
        ];

        yield 'search by one project AND focus name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Focus Beta'],
            'unexpectedEntities'  => ['Focus Alpha', 'Focus Gamma', 'Focus Delta'],
        ];

        yield 'search by one project OR focus name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Focus Alpha', 'Focus Beta', 'Focus Gamma'],
            'unexpectedEntities'  => ['Focus Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Focus Gamma', 'Focus Delta'],
            'unexpectedEntities'  => ['Focus Alpha', 'Focus Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Focus Beta'],
            'unexpectedEntities'  => ['Focus Alpha', 'Focus Gamma', 'Focus Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Focus Gamma', 'Focus Delta'],
            'unexpectedEntities'  => ['Focus Alpha', 'Focus Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Focus Alpha', 'Focus Beta'],
            'unexpectedEntities'  => ['Focus Gamma', 'Focus Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Focus Alpha', 'Focus Gamma', 'Focus Delta'],
            'unexpectedEntities'  => ['Focus Beta'],
        ];
    }

    private function createFocus(string $name): Focus
    {
        $focus = new Focus();
        $focus->setName($name);
        $focus->setType('notice');
        $focus->setStyle('bar');
        $this->em->persist($focus);

        return $focus;
    }
}
