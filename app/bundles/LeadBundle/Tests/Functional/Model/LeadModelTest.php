<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;

class LeadModelTest extends MauticMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testImportLeadWithCompanyDataAndSkipIfExists(): void
    {
        // 1. Create a company first that should be detected as duplicate
        $companyName  = 'Test Import Company '.uniqid();
        $companyEmail = 'company'.uniqid().'@test.com';
        $company      = $this->createCompany($companyName, $companyEmail);

        // 2. Prepare contact data with company association
        $contactEmail     = 'contact'.uniqid().'@test.com';
        $contactFirstName = 'Test';
        $contactLastName  = 'Contact'.uniqid();

        // 3. First import - create contact and link to existing company
        $contactFields = [
            'email'     => 'email',
            'firstname' => 'firstname',
            'lastname'  => 'lastname',
        ];

        $companyFields = [
            'companyname'  => 'companyname',
            'companyemail' => 'companyemail',
        ];

        $contactData = [
            'email'        => $contactEmail,
            'firstname'    => $contactFirstName,
            'lastname'     => $contactLastName,
            'companyname'  => $companyName,
            'companyemail' => $companyEmail,
        ];

        // Import with skipIfExists = false
        $this->importContactWithCompany($contactFields, $companyFields, $contactData, false);

        // Verify the contact was created and linked to the existing company
        $contact = $this->getContactByEmail($contactEmail);
        Assert::assertNotNull($contact);
        Assert::assertEquals($contactFirstName, $contact->getFirstname());
        Assert::assertEquals($contactLastName, $contact->getLastname());

        // Verify company association
        $companies = $this->getCompaniesForContact($contact);
        Assert::assertCount(1, $companies);
        Assert::assertEquals($companyName, $companies[0]['companyname']);
        Assert::assertEquals($companyEmail, $companies[0]['companyemail']);

        // 4. Modify contact data and try to import again with skipIfExists = true
        $modifiedContactData = [
            'email'        => $contactEmail, // Same email to match existing contact
            'firstname'    => 'Modified',
            'lastname'     => 'Name',
            'companyname'  => $companyName,
            'companyemail' => $companyEmail,
        ];

        // Import with skipIfExists = true
        $this->importContactWithCompany($contactFields, $companyFields, $modifiedContactData, true);

        // Verify contact data was NOT updated
        $contactAfterSecondImport = $this->getContactByEmail($contactEmail);
        Assert::assertEquals($contactFirstName, $contactAfterSecondImport->getFirstname());
        Assert::assertEquals($contactLastName, $contactAfterSecondImport->getLastname());

        // 5. Import again with skipIfExists = false
        $this->importContactWithCompany($contactFields, $companyFields, $modifiedContactData, false);

        // Verify contact data WAS updated
        $contactAfterThirdImport = $this->getContactByEmail($contactEmail);
        Assert::assertEquals('Modified', $contactAfterThirdImport->getFirstname());
        Assert::assertEquals('Name', $contactAfterThirdImport->getLastname());
    }

    /**
     * @param array<string, string> $contactFields
     * @param array<string, string> $companyFields
     * @param array<string, string> $data
     */
    private function importContactWithCompany(array $contactFields, array $companyFields, array $data, bool $skipIfExists): void
    {
        /** @var LeadModel $leadModel */
        $leadModel = self::getContainer()->get('mautic.lead.model.lead');

        $mergedFields = array_merge($contactFields, $companyFields);

        $leadModel->import($mergedFields, $data, null, null, null, true, null, null, $skipIfExists);
    }

    private function createCompany(string $name, string $email): Company
    {
        /** @var CompanyModel $companyModel */
        $companyModel = self::getContainer()->get('mautic.lead.model.company');

        $company = new Company();
        $company->setName($name);

        // Set company fields
        $companyModel->setFieldValues($company, [
            'companyname'  => $name,
            'companyemail' => $email,
        ]);

        $companyModel->saveEntity($company);

        return $company;
    }

    private function getContactByEmail(string $email): ?Lead
    {
        return $this->em->getRepository(Lead::class)->findOneBy(['email' => $email]);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function getCompaniesForContact(Lead $contact): array
    {
        /** @var CompanyModel $companyModel */
        $companyModel = self::getContainer()->get('mautic.lead.model.company');

        return $companyModel->getCompanyLeadRepository()->getCompaniesByLeadId($contact->getId());
    }
}
