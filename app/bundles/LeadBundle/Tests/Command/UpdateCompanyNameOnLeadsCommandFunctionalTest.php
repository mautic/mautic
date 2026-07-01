<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Command\UpdateCompanyNameOnLeadsCommand;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Tests\TestEntityCreationTrait;
use PHPUnit\Framework\Assert;

final class UpdateCompanyNameOnLeadsCommandFunctionalTest extends MauticMysqlTestCase
{
    use TestEntityCreationTrait;

    protected $useCleanupRollback = false;

    public function testUpdateCompanies(): void
    {
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

        $this->updateCompanyName($company1);

        $this->testSymfonyCommand(UpdateCompanyNameOnLeadsCommand::COMMAND_NAME, ['--company-id' => $company1->getId()]);

        $this->em->clear();

        Assert::assertSame($companyRepository->getEntity($company1->getId())->getName(), $contactRepository->getEntity($contact1->getId())->getCompany(), 'Company name is updated on leads.');
        Assert::assertSame($companyRepository->getEntity($company1->getId())->getName(), $contactRepository->getEntity($contact2->getId())->getCompany(), 'Company name is updated on leads.');
        Assert::assertSame($companyRepository->getEntity($company2->getId())->getName(), $contactRepository->getEntity($contact3->getId())->getCompany(), 'Company name is not updated and remains same as it\'s primary company');

        $this->updateCompanyName($companyRepository->getEntity($company2->getId()));

        $this->testSymfonyCommand(UpdateCompanyNameOnLeadsCommand::COMMAND_NAME);
        $this->em->clear();

        Assert::assertSame($companyRepository->getEntity($company2->getId())->getName(), $contactRepository->getEntity($contact3->getId())->getCompany(), 'Company name is updated on leads.');
        Assert::assertSame($companyRepository->getEntity($company2->getId())->getName(), $contactRepository->getEntity($contact4->getId())->getCompany(), 'Company name is updated on leads.');
        Assert::assertSame($companyRepository->getEntity($company1->getId())->getName(), $contactRepository->getEntity($contact1->getId())->getCompany(), 'Company name is not updated and remains same as it\'s primary company');
    }
}
