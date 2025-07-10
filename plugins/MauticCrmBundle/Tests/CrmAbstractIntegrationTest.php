<?php

namespace MauticPlugin\MauticCrmBundle\Tests;

use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\Deduplicate\CompanyDeduper;
use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticCrmBundle\Tests\Fixtures\Model\CompanyModelStub;
use MauticPlugin\MauticCrmBundle\Tests\Stubs\StubIntegration;
use PHPUnit\Framework\MockObject\MockBuilder;

class CrmAbstractIntegrationTest extends AbstractIntegrationTestCase
{
    public function testFieldMatchingPriority(): void
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

        /** @var MockBuilder $mockBuilder */
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

    public function testCompanyDataIsMappedForNewCompanies(): void
    {
        $data = [
            'custom_company_name' => 'Some Business',
            'some_custom_field'   => 'some value',
        ];

        $emailValidator = $this->getMockBuilder(EmailValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $companyDeduper = $this->createMock(CompanyDeduper::class);

        $companyModel = $this->getMockBuilder(CompanyModelStub::class)
            ->onlyMethods(['fetchCompanyFields', 'organizeFieldsByGroup', 'saveEntity'])
            ->disableOriginalConstructor()
            ->getMock();
        $companyModel->setFieldModel($this->fieldModel);
        $companyModel->setEmailValidator($emailValidator);
        $companyModel->setCompanyDeduper($companyDeduper);

        $companyModel->expects($this->any())
            ->method('fetchCompanyFields')
            ->willReturn([]);
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

        $integration = $this->getMockBuilder(StubIntegration::class)
            ->setConstructorArgs([
                $this->dispatcher,
                $this->cache,
                $this->em,
                $this->request,
                $this->router,
                $this->translator,
                $this->logger,
                $this->encryptionHelper,
                $this->leadModel,
                $companyModel,
                $this->pathsHelper,
                $this->notificationModel,
                $this->fieldModel,
                $this->integrationEntityModel,
                $this->doNotContact,
                $this->fieldsWithUniqueIdentifier,
            ])
            ->onlyMethods(['populateMauticLeadData', 'mergeConfigToFeatureSettings'])
            ->getMock();

        $integration->expects($this->once())
            ->method('populateMauticLeadData')
            ->willReturn($data);

        $company = $integration->getMauticCompany($data);

        $this->assertEquals('Some Business', $company->getName());
        $this->assertEquals('Some Business', $company->getFieldValue('custom_company_name'));
        $this->assertEquals('some value', $company->getFieldValue('some_custom_field'));
    }

    public function testLimitString(): void
    {
        $integration = $this->getMockBuilder(StubIntegration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $methodLimitString = new \ReflectionMethod(StubIntegration::class, 'limitString');
        $methodLimitString->setAccessible(true);

        $string = 'SomeRandomString';

        $result = $methodLimitString->invokeArgs($integration, [str_repeat($string, 100), 'text']);
        $this->assertSame(strlen($result), 255);

        $result = $methodLimitString->invokeArgs($integration, [$string, 'text']);
        $this->assertSame(strlen($result), strlen($string));
        $this->assertSame($result, $string);

        $result = $methodLimitString->invokeArgs($integration, [true, 'text']);
        $this->assertSame($result, true);

        $result = $methodLimitString->invokeArgs($integration, [false, 'text']);
        $this->assertSame($result, false);

        $result = $methodLimitString->invokeArgs($integration, [[1, 2, 3]]);
        $this->assertSame($result, [1, 2, 3]);
    }
}
