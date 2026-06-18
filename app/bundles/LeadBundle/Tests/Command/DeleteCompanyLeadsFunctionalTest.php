<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\DeleteCompanyLeads;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Tests\TestEntityCreationTrait;
use PHPUnit\Framework\Assert;

final class DeleteCompanyLeadsFunctionalTest extends MauticMysqlTestCase
{
    use TestEntityCreationTrait;

    protected $useCleanupRollback = false;

    public function testDeleteCompanies(): void
    {
        /**
         * @var CompanyLeadRepository
         */
        $companyLeadRepository = $this->em->getRepository(CompanyLead::class);

        /**
         * @var CompanyRepository
         */
        $companyRepository = $this->em->getRepository(Company::class);

        /**
         * @var LeadRepository
         */
        $contactRepository = $this->em->getRepository(Lead::class);

        $contact1 = $this->createContact();
        $contact2 = $this->createContact();
        $contact3 = $this->createContact();
        $contact4 = $this->createContact();

        $company1 = $this->createCompany();
        $company2 = $this->createCompany();
        $company3 = $this->createCompany();

        $this->attachContactToCompany($contact1, $company1, true);
        $this->attachContactToCompany($contact1, $company2);
        $this->attachContactToCompany($contact2, $company1, true);

        $this->attachContactToCompany($contact3, $company1);
        $this->attachContactToCompany($contact3, $company2, true);

        $this->attachContactToCompany($contact4, $company2, true);

        $this->attachContactToCompany($contact3, $company3);

        $this->softDeleteCompany($company1);

        $this->testSymfonyCommand(DeleteCompanyLeads::COMMAND_NAME, ['--company-id' => $company1->getId()]);

        Assert::assertSame(4, $companyLeadRepository->count([]), 'Company lead mapping is deleted for deleted company.');
        Assert::assertNull($companyRepository->getEntity($company1->getId()), 'Company is deleted from companies permanently.');
        Assert::assertNull($contactRepository->getEntity($contact2->getId())->getCompany(), 'Company is set to null when no other company is attached.');
        Assert::assertSame($company2->getName(), $contactRepository->getEntity($contact1->getId())->getCompany(), 'Another company is made primary for the contact.');

        $this->softDeleteCompany($company2);
        $this->testSymfonyCommand(DeleteCompanyLeads::COMMAND_NAME);

        Assert::assertSame(1, $companyRepository->count([]), '1 company is not deleted');
        Assert::assertSame(1, $companyLeadRepository->count([]), 'Company lead mapping is deleted for deleted company.');
        Assert::assertNull($companyRepository->getEntity($company2->getId()), 'Company is deleted from companies permanently.');
        Assert::assertNull($contactRepository->getEntity($contact4->getId())->getCompany(), 'Company is set to null when no other company is attached.');
        Assert::assertSame($company3->getName(), $contactRepository->getEntity($contact3->getId())->getCompany(), 'Another company is made primary for the contact.');
    }
}
