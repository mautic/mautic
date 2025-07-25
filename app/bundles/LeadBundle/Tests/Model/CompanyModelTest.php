<?php

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\Deduplicate\CompanyDeduper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Entity\UserRepository;
use Symfony\Component\HttpFoundation\Session\Session;

class CompanyModelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FieldModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $leadFieldModel;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Session
     */
    private \PHPUnit\Framework\MockObject\MockObject $session;

    /**
     * @var EmailValidator|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $emailValidator;

    /**
     * @var CompanyDeduper|\PHPUnit\Framework\MockObject\MockObject
     */
    private \PHPUnit\Framework\MockObject\MockObject $companyDeduper;

    public function setUp(): void
    {
        $this->leadFieldModel = $this->createMock(FieldModel::class);
        $this->session        = $this->createMock(Session::class);
        $this->emailValidator = $this->createMock(EmailValidator::class);
        $this->companyDeduper = $this->createMock(CompanyDeduper::class);
    }

    /**
     * @testdox Ensure that an array value is flattened before saving
     *
     * @covers  \Mautic\CoreBundle\Helper\AbstractFormFieldHelper::parseList
     */
    public function testArrayValueIsFlattenedBeforeSave(): void
    {
        /** @var CompanyModel $companyModel */
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

        $this->setProperty($companyModel, CompanyModel::class, 'companyDeduper', $companyDeduper);
        $duplicatedCompany->expects($this->exactly(0))->method('addUpdatedField');
        $companyModel->importCompany([], [], null, false, true);
    }

    public function testImportCompanySkipIfExistsFalse(): void
    {
        $companyModel = $this->getCompanyModelForImport();

        $duplicatedCompany = $this->createMock(Company::class);
        $duplicatedCompany->method('getProfileFields')->willReturn(['companyfield'=> 'xxx']);
        $companyDeduper = $this->getCompanyDeduperForImport($duplicatedCompany);

        $this->setProperty($companyModel, CompanyModel::class, 'companyDeduper', $companyDeduper);
        $duplicatedCompany->expects($this->exactly(1))->method('addUpdatedField');
        $companyModel->importCompany([], [], null, false, false);
    }

    private function getCompanyModelForImport()
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

    private function getCompanyDeduperForImport(Company $duplicatedCompany)
    {
        $companyDeduper = $this->createMock(CompanyDeduper::class);

        $companyDeduper->method('checkForDuplicateCompanies')->willReturn([$duplicatedCompany]);

        return $companyDeduper;
    }

    /**
     * Set protected property to an object.
     *
     * @param object $object
     * @param string $class
     * @param string $property
     * @param mixed  $value
     */
    private function setProperty($object, $class, $property, $value): void
    {
        $reflectedProp = new \ReflectionProperty($class, $property);
        $reflectedProp->setAccessible(true);
        $reflectedProp->setValue($object, $value);
    }

    public function testExtractCompanyDataFromImport(): void
    {
        /** @var CompanyModel $companyModel */
        $companyModel = $this->getMockBuilder(CompanyModel::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['fetchCompanyFields'])
            ->getMock();

        $companyModel->method('fetchCompanyFields')
            ->will($this->returnValue([
                ['alias' => 'companyname'],
                ['alias' => 'companyemail'],
                ['alias' => 'companyindustry'],
            ]));

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

    public function testImportCompanyWithUserReferenceFields(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        // Create repository mocks
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->method('findByIdentifier')->willReturn($user);

        $companyLeadRepo = $this->createMock(\Mautic\LeadBundle\Entity\CompanyLeadRepository::class);

        $companyRepo = $this->createMock(\Mautic\LeadBundle\Entity\CompanyRepository::class);
        $companyRepo->method('setDispatcher')->willReturnSelf();

        // Setup entity manager with repositories
        $entityManager = $this->createMock(\Doctrine\ORM\EntityManager::class);
        $entityManager->method('getRepository')
            ->will($this->returnValueMap([
                [User::class, $userRepo],
                [\Mautic\LeadBundle\Entity\CompanyLead::class, $companyLeadRepo],
                [Company::class, $companyRepo],
            ]));
        $entityManager->method('getReference')->willReturn($user);

        $userHelper = $this->createMock(UserHelper::class);
        $userHelper->method('getUser')->willReturn($user);

        $dispatcher = $this->createMock(\Symfony\Component\EventDispatcher\EventDispatcherInterface::class);

        // Create the CompanyModel with constructor injection
        $companyModel = new class($entityManager, $this->leadFieldModel, $this->companyDeduper, $userHelper, $dispatcher) extends CompanyModel {
            private $entity;

            // Override constructor to accept only what we need
            public function __construct($em, $leadFieldModel, $companyDeduper, $userHelper, $dispatcher)
            {
                $this->em             = $em;
                $this->leadFieldModel = $leadFieldModel;
                $this->companyDeduper = $companyDeduper;
                $this->userHelper     = $userHelper;
                $this->dispatcher     = $dispatcher;
                $this->entity         = new Company();
            }

            // Override getRepository to return our mock directly
            public function getRepository(): \Mautic\LeadBundle\Entity\CompanyRepository
            {
                return $this->em->getRepository(Company::class);
            }

            // Override fetchCompanyFields with test data
            public function fetchCompanyFields()
            {
                return [
                    [
                        'alias'        => 'companyfield',
                        'defaultValue' => '',
                        'type'         => 'text',
                    ],
                ];
            }

            // Override getFieldData with test data
            public function getFieldData($fields, $data): array
            {
                return ['companyfield' => 'xxx'];
            }

            // Add getter for the entity to verify in test
            public function getEntity($id = null): ?Company
            {
                return $this->entity;
            }
        };

        // Test with createdByUser
        $company = $companyModel->importCompany(
            ['createdByUser' => 'created_field', 'modifiedByUser' => 'modified_field'],
            ['created_field' => 'admin', 'modified_field' => 'admin'],
            1
        );

        $this->assertSame($user->getId(), $company->getCreatedBy());
        $this->assertSame($user->getId(), $company->getModifiedBy());
        $this->assertSame($user, $company->getOwner());
    }
}
