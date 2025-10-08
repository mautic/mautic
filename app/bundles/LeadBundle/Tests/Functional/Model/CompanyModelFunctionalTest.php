<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;

final class CompanyModelFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    public function testAddLeadToCompanyWithLeadAsArray(): void
    {
        // Create a lead
        $lead = $this->createLead('User', 'One', 'user@company_a.com');
        // Create a company
        $company = $this->createCompany('Company A', 'contact@company_a.com');
        $this->em->flush();

        /** @var CompanyLeadRepository $companyLeadRepo */
        $companyLeadRepo = $this->em->getRepository(CompanyLead::class);

        $this->assertEquals(0, $companyLeadRepo->count([]));

        /** @var CompanyModel $companyModel */
        $companyModel = self::getContainer()->get('mautic.lead.model.company');
        $companyModel->addLeadToCompany($company, $lead->convertToArray());

        $this->assertEquals(1, $companyLeadRepo->count([]));
    }

    public function testGetCompaniesByLeadsReturnsCompanies(): void
    {
        // Create leads and companies
        $lead1    = $this->createLead('Lead', 'One', 'lead1@company_x.com');
        $companyX = $this->createCompany('Company X', 'contact@company_x.com');

        $lead2    = $this->createLead('Lead', 'Two', 'lead2@company_y.com');
        $companyY = $this->createCompany('Company Y', 'contact@company_y.com');

        $this->em->flush();

        /** @var CompanyModel $companyModel */
        $companyModel = self::getContainer()->get('mautic.lead.model.company');

        // Attach leads to companies
        $companyModel->addLeadToCompany($companyX, $lead1->convertToArray());
        $companyModel->addLeadToCompany($companyY, $lead2->convertToArray());

        $this->em->flush();

        $result = $companyModel->getCompaniesByLeads([$lead1->getId(), $lead2->getId()]);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        $resultIds = [];
        foreach ($result as $company) {
            $this->assertInstanceOf(Company::class, $company);
            $resultIds[] = $company->getId();
        }

        sort($resultIds);
        $expected = [$companyX->getId(), $companyY->getId()];
        sort($expected);

        $this->assertEquals($expected, $resultIds);
    }
}
