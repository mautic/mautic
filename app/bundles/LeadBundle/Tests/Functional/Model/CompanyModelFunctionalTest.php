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
}
