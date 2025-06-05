<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Controller;

use Mautic\FormBundle\Entity\Form;
use Mautic\ProjectBundle\Tests\Functional\AbstratctProjectSearchTestCase;

final class FormProjectSearchFunctionalTest extends AbstratctProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $formAlpha = $this->createForm('Form Alpha');
        $formBeta  = $this->createForm('Form Beta');
        $this->createForm('Form Gamma');
        $this->createForm('Form Delta');

        $formAlpha->addProject($projectOne);
        $formAlpha->addProject($projectTwo);
        $formBeta->addProject($projectTwo);
        $formBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/forms', '/s/forms']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Form Alpha', 'Form Beta'],
            'unexpectedEntities'  => ['Form Gamma', 'Form Delta'],
        ];

        yield 'search by one project AND form name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Form Beta'],
            'unexpectedEntities'  => ['Form Alpha', 'Form Gamma', 'Form Delta'],
        ];

        yield 'search by one project OR form name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Form Alpha', 'Form Beta', 'Form Gamma'],
            'unexpectedEntities'  => ['Form Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Form Gamma', 'Form Delta'],
            'unexpectedEntities'  => ['Form Alpha', 'Form Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Form Beta'],
            'unexpectedEntities'  => ['Form Alpha', 'Form Gamma', 'Form Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Form Gamma', 'Form Delta'],
            'unexpectedEntities'  => ['Form Alpha', 'Form Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Form Alpha', 'Form Beta'],
            'unexpectedEntities'  => ['Form Gamma', 'Form Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Form Alpha', 'Form Gamma', 'Form Delta'],
            'unexpectedEntities'  => ['Form Beta'],
        ];
    }

    private function createForm(string $name): Form
    {
        $form = new Form();
        $form->setName($name);
        $form->setAlias($name);
        $this->em->persist($form);

        return $form;
    }
}
