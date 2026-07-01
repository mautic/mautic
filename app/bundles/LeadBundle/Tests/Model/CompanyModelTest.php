<?php

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Test\ReflectionHelper;
use Mautic\LeadBundle\Deduplicate\CompanyDeduper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Model\CompanyModel;
use PHPUnit\Framework\MockObject\MockObject;

#[\PHPUnit\Framework\Attributes\CoversClass(\Mautic\CoreBundle\Helper\AbstractFormFieldHelper::class)]
final class CompanyModelTest extends \PHPUnit\Framework\TestCase
{
    #[\PHPUnit\Framework\Attributes\TestDox('Ensure that an array value is flattened before saving')]
    public function testArrayValueIsFlattenedBeforeSave(): void
    {
        /** @var CompanyModel&MockObject $companyModel */
        $companyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $company = new Company();
        $company->setFields(
            [
                'core' => [
                    'multiselect' => [
                        'type'  => 'multiselect',
                        'alias' => 'multiselect',
                        'value' => 'abc|123',
                    ],
                ],
            ]
        );

        $companyModel->setFieldValues($company, ['multiselect' => ['abc', 'def']]);

        $updatedFields = $company->getUpdatedFields();

        $this->assertEquals(
            [
                'multiselect' => 'abc|def',
            ],
            $updatedFields
        );
    }

    public function testImportCompanySkipIfExistsTrue(): void
    {
        $companyModel = $this->getCompanyModelForImport();

        $duplicatedCompany = $this->createMock(Company::class);
        $duplicatedCompany->method('getProfileFields')->willReturn(['companyfield'=> 'xxx']);
        $companyDeduper = $this->getCompanyDeduperForImport($duplicatedCompany);

        ReflectionHelper::setValue($companyModel, 'companyDeduper', $companyDeduper);
        $duplicatedCompany->expects($this->exactly(0))->method('addUpdatedField');
        $companyModel->importCompany([], [], null, false, true);
    }

    public function testImportCompanySkipIfExistsFalse(): void
    {
        $companyModel = $this->getCompanyModelForImport();

        $duplicatedCompany = $this->createMock(Company::class);
        $duplicatedCompany->method('getProfileFields')->willReturn(['companyfield'=> 'xxx']);
        $companyDeduper = $this->getCompanyDeduperForImport($duplicatedCompany);

        ReflectionHelper::setValue($companyModel, 'companyDeduper', $companyDeduper);
        $duplicatedCompany->expects($this->once())->method('addUpdatedField');
        $companyModel->importCompany([], [], null, false, false);
    }

    public function testImportHtmlFieldsForCompany(): void
    {
        $companyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchCompanyFields', 'getFieldData'])
            ->getMock();

        $companyModel->method('fetchCompanyFields')->willReturn(
            [
                [
                    'alias'        => 'companyfield',
                    'defaultValue' => '',
                    'type'         => 'text',
                ],
                [
                    'alias'        => 'custom_html_field',
                    'defaultValue' => '',
                    'type'         => 'html',
                ],
            ]
        );

        $data = ['companyfield' => 'test', 'custom_html_field' => '<p>html content</p>'];
        $companyModel->method('getFieldData')
            ->willReturn($data);
        $this->setSecurity($companyModel);

        $companyModel->method('getFieldData')->willReturn($data);

        $duplicatedCompany = $this->createMock(Company::class);
        $duplicatedCompany->method('getProfileFields')->willReturn($data);

        $companyDeduper = $this->getCompanyDeduperForImport($duplicatedCompany);
        ReflectionHelper::setValue($companyModel, 'companyDeduper', $companyDeduper);

        $duplicatedCompany->expects($this->exactly(2))->method('addUpdatedField');
        $companyModel->importCompany([], [], null, false, false);
    }

    /**
     * @return CompanyModel&MockObject
     */
    private function getCompanyModelForImport(): CompanyModel
    {
        $companyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchCompanyFields', 'getFieldData'])
            ->getMock();

        $companyModel->method('fetchCompanyFields')->willReturn(
            [
                [
                    'alias'        => 'companyfield',
                    'defaultValue' => '',
                    'type'         => 'text',
                ],
            ]
        );
        $companyModel->method('getFieldData')->willReturn(['companyfield' => 'xxx']);
        $this->setSecurity($companyModel);

        return $companyModel;
    }

    /**
     * @return CompanyDeduper&MockObject
     */
    private function getCompanyDeduperForImport(Company $duplicatedCompany): CompanyDeduper
    {
        $companyDeduper = $this->createMock(CompanyDeduper::class);

        $companyDeduper->method('checkForDuplicateCompanies')->willReturn([$duplicatedCompany]);

        return $companyDeduper;
    }

    public function testExtractCompanyDataFromImport(): void
    {
        /** @var CompanyModel&MockObject $companyModel */
        $companyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchCompanyFields'])
            ->getMock();

        $companyModel->method('fetchCompanyFields')
            ->willReturn([
                ['alias' => 'companyname'],
                ['alias' => 'companyemail'],
                ['alias' => 'companyindustry'],
            ]);

        $fields = [
            'email'           => 'i_contact_email',
            'companyemail'    => 'i_company_email',
            'company'         => 'i_company_name',
            'companyindustry' => 'i_company_industry',
        ];
        $data= [
            'i_contact_email'    => 'PennyKMoore@dayrep.com',
            'i_company_email'    => 'turbochicken@dayrep.com',
            'i_company_name'     => 'Turbo chicken',
            'i_company_industry' => 'Biotechnology',
        ];

        [$companyFields, $companyData] = $companyModel->extractCompanyDataFromImport($fields, $data);

        $expectedCompanyFields = [
            'companyemail'    => 'i_company_email',
            'companyindustry' => 'i_company_industry',
            'companyname'     => 'i_company_name',
        ];
        $expectedCompanyData = [
            'i_company_email'    => 'turbochicken@dayrep.com',
            'i_company_industry' => 'Biotechnology',
            'i_company_name'     => 'Turbo chicken',
        ];

        $this->assertSame($expectedCompanyFields, $companyFields);
        $this->assertSame($expectedCompanyData, $companyData);
    }

    private function setSecurity(CompanyModel $companyModel): void
    {
        $security = $this->createMock(CorePermissions::class);
        $security->method('hasEntityAccess')
            ->willReturn(true);
        $security->method('isGranted')
            ->willReturn(true);

        $reflection = new \ReflectionClass($companyModel);
        $property   = $reflection->getProperty('security');
        $property->setValue($companyModel, $security);
    }
}
