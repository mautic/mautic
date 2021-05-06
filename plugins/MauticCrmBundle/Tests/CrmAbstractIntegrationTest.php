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

use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\PluginBundle\Tests\Integration\AbstractIntegrationTestCase;
use MauticPlugin\MauticCrmBundle\Tests\Stubs\StubIntegration;

class CrmAbstractIntegrationTest extends AbstractIntegrationTestCase
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
        $data = [
            'custom_company_name' => 'Some Business',
            'some_custom_field'   => 'some value',
        ];

        $emailValidator = $this->getMockBuilder(EmailValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $companyModel = $this->getMockBuilder(CompanyModel::class)
            ->setMethodsExcept(['setFieldValues'])
            ->setConstructorArgs([$this->fieldModel, $this->session, $emailValidator])
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

        $integration = $this->getMockBuilder(StubIntegration::class)
            ->setConstructorArgs([
                $this->dispatcher,
                $this->cache,
                $this->em,
                $this->session,
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
            ])
            ->setMethodsExcept(['getMauticCompany', 'setCompanyModel', 'setFieldModel', 'hydrateCompanyName'])
            ->getMock();

        $integration->expects($this->once())
            ->method('populateMauticLeadData')
            ->willReturn($data);

        $company = $integration->getMauticCompany($data);

        $this->assertEquals('Some Business', $company->getName());
        $this->assertEquals('Some Business', $company->getFieldValue('custom_company_name'));
        $this->assertEquals('some value', $company->getFieldValue('some_custom_field'));
    }

    public function testLimitString()
    {
        $integration = $this->getMockBuilder(StubIntegration::class)
            ->disableOriginalConstructor()
            ->setMethodsExcept(['limitString'])
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
