<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Tests;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Entity\LeadRepository;
use MauticPlugin\MauticCrmBundle\Tests\Stubs\StubIntegration;
use Symfony\Component\HttpFoundation\Session\Session;

class CrmAbstractIntegrationTest extends \PHPUnit_Framework_TestCase
{
    public function testFieldMatchingPriority()
    {
        $config = [
            'update_mautic' => [
                'email'      => '1',
                'first_name' => '0',
                'last_name'  => '0',
                'address_1'  => '1',
                'address_2'  => '1',
            ],
        ];

        /** @var \PHPUnit_Framework_MockObject_MockBuilder $mockBuilder */
        $mockBuilder = $this->getMockBuilder(StubIntegration::class);
        $mockBuilder->disableOriginalConstructor();

        /** @var StubIntegration $integration */
        $integration = $mockBuilder->getMock();

        $methodMautic = new \ReflectionMethod(StubIntegration::class, 'getPriorityFieldsForMautic');
        $methodMautic->setAccessible(true);

        $methodIntegration = new \ReflectionMethod(StubIntegration::class, 'getPriorityFieldsForIntegration');
        $methodIntegration->setAccessible(true);

        $fieldsForMautic = $methodMautic->invokeArgs($integration, [$config]);

        $this->assertSame(
            ['email', 'address_1', 'address_2'],
            $fieldsForMautic,
            'Fields to update in Mautic should return fields marked as 1 in the integration priority config.'
        );

        $fieldsForIntegration = $methodIntegration->invokeArgs($integration, [$config]);

        $this->assertSame(
            ['first_name', 'last_name'],
            $fieldsForIntegration,
            'Fields to update in the integration should return fields marked as 0 in the integration priority config.'
        );
    }

    public function testCompanyDataIsMappedForNewCompanies()
    {
        $integration = $this->getMockBuilder(StubIntegration::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getMauticCompany', 'setCompanyModel', 'setFieldModel', 'hydrateCompanyName'])
            ->getMock();

        $data = [
            'custom_company_name' => 'Some Business',
            'some_custom_field'   => 'some value',
        ];

        $integration->expects($this->once())
            ->method('populateMauticLeadData')
            ->willReturn($data);

        $fieldModel = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->getMock();
        $emailValidator = $this->getMockBuilder(EmailValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $companyModel = $this->getMockBuilder(CompanyModel::class)
            ->setMethodsExcept(['setFieldValues'])
            ->setConstructorArgs([$fieldModel, $session, $emailValidator])
            ->getMock();
        $companyModel->expects($this->once())
            ->method('organizeFieldsByGroup')
            ->willReturn([
                'core' => [
                    'companyname' => [
                        'alias' => 'companyname',
                        'type'  => 'text',
                    ],
                    'custom_company_name' => [
                        'alias' => 'custom_company_name',
                        'type'  => 'text',
                    ],
                    'some_custom_field' => [
                        'alias' => 'some_custom_field',
                        'type'  => 'text',
                    ],
                ],
            ]);
        $integration->setCompanyModel($companyModel);

        $integration->setFieldModel($fieldModel);

        $company = $integration->getMauticCompany($data);

        $this->assertEquals('Some Business', $company->getName());
        $this->assertEquals('Some Business', $company->getFieldValue('custom_company_name'));
        $this->assertEquals('some value', $company->getFieldValue('some_custom_field'));
    }

    public function testFalseValueIsUpdatedCorrectly()
    {
        $integration = $this->getMockBuilder(StubIntegration::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['getMauticLead', 'populateMauticLeadData'])
            ->getMock();

        $integration->expects($this->any())
            ->method('mergeConfigToFeatureSettings')
            ->willReturn([
                'leadFields' => [
                        'mautic__Email__c__Lead' => 'email',
                        'mautic__Is_this_text__c__Lead' => 'is_this_text',
                    ],
                ]
            );

        $reflection = new \ReflectionClass($integration);

        $translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $translatorProperty = $reflection->getProperty('translator');
        $translatorProperty->setAccessible(true);
        $translatorProperty->setValue($integration, $translator);

        $fieldModel = $this->getMockBuilder(FieldModel::class)
            ->disableOriginalConstructor()
            ->getMock();

        $fieldModelProperty = $reflection->getProperty('fieldModel');
        $fieldModelProperty->setAccessible(true);
        $fieldModelProperty->setValue($integration, $fieldModel);

        $fieldModel->expects($this->any())
            ->method('getFieldListWithProperties')
            ->willReturn([
                'email' => [
                    'type' => 'text',
                ],
                'is_this_text' => [
                    'type' => 'text',
                ],
            ]);

        $fieldModel->expects($this->any())
            ->method('getUniqueIdentifierFields')
            ->willReturn([
                'email' => 'Email',
            ]);


        $leadModel = $this->getMockBuilder(LeadModel::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['setFieldValues',])
            ->getMock();

        $leadModelProperty = $reflection->getProperty('leadModel');
        $leadModelProperty->setAccessible(true);
        $leadModelProperty->setValue($integration, $leadModel);


        $leadRepository = $this->getMockBuilder(LeadRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $leadRepository->expects($this->any())
            ->method('getLeadsByUniqueFields')
            ->willReturn([]);

        $entityManager =$this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($leadRepository);

        $entityManagerProperty = $reflection->getProperty('em');
        $entityManagerProperty->setAccessible(true);
        $entityManagerProperty->setValue($integration, $entityManager);

        $data = [
            'mautic__Email__c__Lead' => 'test@test.com',
            'mautic__Is_this_text__c__Lead' => false,
        ];

        $leadData = $integration->getMauticLead($data, false);

        $a = 5;
    }
}
