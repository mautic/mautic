<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\LeadBundle\Entity\Company;
use Mautic\ProjectBundle\Tests\Functional\AbstratctProjectSearchTestCase;

final class CompanyProjectSearchFunctionalTest extends AbstratctProjectSearchTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('searchDataProvider')]
    public function testProjectSearch(string $searchTerm, array $expectedEntities, array $unexpectedEntities): void
    {
        $projectOne   = $this->createProject('Project One');
        $projectTwo   = $this->createProject('Project Two');
        $projectThree = $this->createProject('Project Three');

        $companyAlpha = $this->createCompany('Company Alpha');
        $companyBeta  = $this->createCompany('Company Beta');
        $this->createCompany('Company Gamma');
        $this->createCompany('Company Delta');

        $companyAlpha->addProject($projectOne);
        $companyAlpha->addProject($projectTwo);
        $companyBeta->addProject($projectTwo);
        $companyBeta->addProject($projectThree);

        $this->em->flush();
        $this->em->clear();

        $this->searchAndAssert($searchTerm, $expectedEntities, $unexpectedEntities, ['/api/companies', '/s/companies']);
    }

    /**
     * @return \Generator<string, array{searchTerm: string, expectedEntities: array<string>, unexpectedEntities: array<string>}>
     */
    public static function searchDataProvider(): \Generator
    {
        yield 'search by one project' => [
            'searchTerm'          => 'project:"Project Two"',
            'expectedEntities'    => ['Company Alpha', 'Company Beta'],
            'unexpectedEntities'  => ['Company Gamma', 'Company Delta'],
        ];

        yield 'search by one project AND company name' => [
            'searchTerm'          => 'project:"Project Two" AND Beta',
            'expectedEntities'    => ['Company Beta'],
            'unexpectedEntities'  => ['Company Alpha', 'Company Gamma', 'Company Delta'],
        ];

        yield 'search by one project OR company name' => [
            'searchTerm'          => 'project:"Project Two" OR Gamma',
            'expectedEntities'    => ['Company Alpha', 'Company Beta', 'Company Gamma'],
            'unexpectedEntities'  => ['Company Delta'],
        ];

        yield 'search by NOT one project' => [
            'searchTerm'          => '!project:"Project Two"',
            'expectedEntities'    => ['Company Gamma', 'Company Delta'],
            'unexpectedEntities'  => ['Company Alpha', 'Company Beta'],
        ];

        yield 'search by two projects with AND' => [
            'searchTerm'          => 'project:"Project Two" AND project:"Project Three"',
            'expectedEntities'    => ['Company Beta'],
            'unexpectedEntities'  => ['Company Alpha', 'Company Gamma', 'Company Delta'],
        ];

        yield 'search by two projects with NOT AND' => [
            'searchTerm'          => '!project:"Project Two" AND !project:"Project Three"',
            'expectedEntities'    => ['Company Gamma', 'Company Delta'],
            'unexpectedEntities'  => ['Company Alpha', 'Company Beta'],
        ];

        yield 'search by two projects with OR' => [
            'searchTerm'          => 'project:"Project Two" OR project:"Project Three"',
            'expectedEntities'    => ['Company Alpha', 'Company Beta'],
            'unexpectedEntities'  => ['Company Gamma', 'Company Delta'],
        ];

        yield 'search by two projects with NOT OR' => [
            'searchTerm'          => '!project:"Project Two" OR !project:"Project Three"',
            'expectedEntities'    => ['Company Alpha', 'Company Gamma', 'Company Delta'],
            'unexpectedEntities'  => ['Company Beta'],
        ];
    }

    private function createCompany(string $name): Company
    {
        $company = new Company();
        $company->setName($name);
        $this->em->persist($company);

        return $company;
    }
}
