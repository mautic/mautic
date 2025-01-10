<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\DeleteContactSecondaryCompaniesCommand;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;

final class DeleteContactSecondaryCompaniesCommandTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testDeleteContactSecondaryCompanies(): void
    {
        $contact          = $this->getContactWithCompanies();
        /** @var CompanyLeadRepository $companyLeadRepo */
        $companyLeadRepo  = $this->em->getRepository(CompanyLead::class);

        $contactCompanies = $companyLeadRepo->getCompaniesByLeadId($contact->getId());
        self::assertEquals(2, count($contactCompanies));

        $this->testSymfonyCommand(DeleteContactSecondaryCompaniesCommand::NAME);

        $contactCompanies = $companyLeadRepo->getCompaniesByLeadId($contact->getId());
        self::assertEquals(2, count($contactCompanies));

        $this->setUpSymfony(['contact_allow_multiple_companies' => 0]);
        $this->testSymfonyCommand(DeleteContactSecondaryCompaniesCommand::NAME);

        $contactCompanies = $companyLeadRepo->getCompaniesByLeadId($contact->getId());
        self::assertEquals(1, count($contactCompanies));
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function getContactWithCompanies(): Lead
    {
        $company = new Company();
        $company->setName('Doe Corp');

        $this->em->persist($company);

        $company2 = new Company();
        $company2->setName('Doe Corp 2');

        $this->em->persist($company2);

        $contact = new Lead();
        $contact->setEmail('test@test.com');

        $this->em->persist($contact);
        $this->em->flush();

        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get('mautic.lead.model.lead');
        $this->assertTrue($leadModel->addToCompany($contact, $company));
        $this->assertTrue($leadModel->addToCompany($contact, $company2));

        return $contact;
    }
}
