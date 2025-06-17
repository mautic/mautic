<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Tests\Controller;

use Mautic\EmailBundle\Entity\Email;
use Mautic\ProjectBundle\Tests\Functional\AbstratctProjectSearchTestCase;

final class EmailProjectSearchFunctionalTest extends AbstratctProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $emailAlpha = $this->createEmail('Email Alpha');
        $emailBeta  = $this->createEmail('Email Beta');
        $this->createEmail('Email Gamma');
        $this->createEmail('Email Delta');

        $emailAlpha->addProject($projectOne);
        $emailAlpha->addProject($projectTwo);
        $emailBeta->addProject($projectTwo);
        $emailBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/emails', '/s/emails']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Email Alpha', 'Email Beta'],
            'unexpectedEntities'  => ['Email Gamma', 'Email Delta'],
        ];

        yield 'search by one project AND email name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Email Beta'],
            'unexpectedEntities'  => ['Email Alpha', 'Email Gamma', 'Email Delta'],
        ];

        yield 'search by one project OR email name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Email Alpha', 'Email Beta', 'Email Gamma'],
            'unexpectedEntities'  => ['Email Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Email Gamma', 'Email Delta'],
            'unexpectedEntities'  => ['Email Alpha', 'Email Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Email Beta'],
            'unexpectedEntities'  => ['Email Alpha', 'Email Gamma', 'Email Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Email Gamma', 'Email Delta'],
            'unexpectedEntities'  => ['Email Alpha', 'Email Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Email Alpha', 'Email Beta'],
            'unexpectedEntities'  => ['Email Gamma', 'Email Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Email Alpha', 'Email Gamma', 'Email Delta'],
            'unexpectedEntities'  => ['Email Beta'],
        ];
    }

    private function createEmail(string $name): Email
    {
        $email = new Email();
        $email->setName($name);
        $this->em->persist($email);

        return $email;
    }
}
