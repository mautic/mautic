<?php

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Export;

use Mautic\LeadBundle\Entity\Company;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;

class CompanyTest extends PipedriveTest
{
    private $features = [
        'objects'       => [
            'company',
        ],
        'companyFields' => [
            'name'    => 'companyname',
            'address' => 'companyaddress1',
        ],
    ];

    public function testCreateCompanyWhenFeatureIsDisabled()
    {
        $this->installPipedriveIntegration(
            true,
            [],
            [
                'url'   => 'Api/Post',
                'token' => 'token',
            ]
        );

        $this->client->request(
            'POST',
            '/s/companies/new?mauticUserLastActive=1&mauticLastNotificationId=',
            [
                'company' => [
                    'companyname'     => 'Test Name',
                    'companyaddress1' => 'Test Address',
                    '_token'          => $this->getCsrfToken('company'),
                ],
            ]
        );

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $company             = $this->em->getRepository(Company::class)->findOneById(1);

        $requests = $GLOBALS['requests'];

        $this->assertSame(count($requests), 0);
        $this->assertSame(count($integrationEntities), 0);
        $this->assertEquals($company->getName(), 'Test Name');
        $this->assertEquals($company->getAddress1(), 'Test Address');
    }

    public function testCreateCompany()
    {
        $testName     = 'Test Name';
        $testAddress1 = 'Test Adddress 123, Wrocław, Poland';

        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => 'Api/Post',
                'token' => 'token',
            ]
        );

        $this->client->request(
            'POST',
            '/s/companies/new?mauticUserLastActive=1&mauticLastNotificationId=',
            [
                'company' => [
                    'companyname'     => $testName,
                    'companyaddress1' => $testAddress1,
                    '_token'          => $this->getCsrfToken('company'),
                ],
            ]
        );

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $company             = $this->em->getRepository(Company::class)->findOneById(1);
        $integrationEntity   = $integrationEntities[0];

        $requests = $GLOBALS['requests'];
        $request  = $requests['POST/Api/Post/organizations'][0];

        $this->assertSame(count($requests), 1);
        $this->assertSame(count($integrationEntities), 1);
        $this->assertEquals($company->getName(), $testName);
        $this->assertEquals($company->getAddress1(), $testAddress1);
        $this->assertEquals($request['form_params']['name'], $testName);
        $this->assertEquals($request['form_params']['address'], $testAddress1);
        $this->assertEquals($integrationEntity->getInternalEntityId(), $company->getId());
        $this->assertNotNull($integrationEntity->getIntegrationEntityId());
        $this->assertEquals($integrationEntity->getIntegrationEntity(), PipedriveIntegration::ORGANIZATION_ENTITY_TYPE);
        $this->assertEquals($integrationEntity->getInternalEntity(), PipedriveIntegration::COMPANY_ENTITY_TYPE);
        $this->assertEquals($integrationEntity->getIntegration(), PipedriveIntegration::INTEGRATION_NAME);
    }

    public function testUpdateCompanyWhenFeatureIsDisabled()
    {
        $testName     = 'New Test Name';
        $testAddress1 = 'New Test Adddress 123, Wrocław, Poland';

        $this->installPipedriveIntegration(
            true,
            [],
            [
                'url'   => 'Api/Put',
                'token' => 'token',
            ]
        );

        $company = $this->createCompany();

        $this->client->request(
            'POST',
            's/companies/edit/'.$company->getId().'?mauticUserLastActive=1&mauticLastNotificationId=',
            [
                'company' => [
                    'companyname'     => $testName,
                    'companyaddress1' => $testAddress1,
                    '_token'          => $this->getCsrfToken('company'),
                ],
            ]
        );

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $companies           = $this->em->getRepository(Company::class)->findAll();
        $company             = $companies[0];

        $requests = $GLOBALS['requests'];

        $this->assertSame(count($requests), 0);
        $this->assertSame(count($integrationEntities), 0);
        $this->assertSame(count($companies), 1);
        $this->assertSame($company->getName(), $testName);
        $this->assertSame($company->getAddress1(), $testAddress1);
    }

    public function testUpdateCompany()
    {
        $integrationId = 66;
        $testName      = 'New Test Name';
        $testAddress1  = 'New Test Adddress 123, Wrocław, Poland';

        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => 'Api/Put',
                'token' => 'token',
            ]
        );

        $company = $this->createCompany();
        $this->createCompanyIntegrationEntity($integrationId, $company->getId());

        $this->client->request(
            'POST',
            's/companies/edit/'.$company->getId().'?mauticUserLastActive=1&mauticLastNotificationId=',
            [
                'company' => [
                    'companyname'     => $testName,
                    'companyaddress1' => $testAddress1,
                    '_token'          => $this->getCsrfToken('company'),
                ],
            ]
        );

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $companies           = $this->em->getRepository(Company::class)->findAll();
        $company             = $companies[0];

        $requests = $GLOBALS['requests'];

        $this->assertSame(count($requests), 1);
        $this->assertSame(count($integrationEntities), 1);
        $this->assertSame(count($companies), 1);
        $this->assertSame($company->getName(), $testName);
        $this->assertSame($company->getAddress1(), $testAddress1);
    }

    public function testDeleteCompanyWhenFeatureIsDisabled()
    {
        $this->installPipedriveIntegration(
            true,
            [],
            [
                'url'   => 'Api/Delete',
                'token' => 'token',
            ]
        );

        $company = $this->createCompany();
        $this->createCompanyIntegrationEntity(567, $company->getId());

        $this->client->request(
            'POST',
            's/companies/delete/'.$company->getId().'?mauticUserLastActive=1&mauticLastNotificationId=',
            []
        );

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $companies           = $this->em->getRepository(Company::class)->findAll();

        $requests = $GLOBALS['requests'];

        $this->assertSame(count($requests), 0);
        $this->assertSame(count($integrationEntities), 0);
        $this->assertSame(count($companies), 0);
    }

    public function testDeleteCompany()
    {
        $integrationId = 66;

        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => 'Api/Delete',
                'token' => 'token',
            ]
        );

        $company = $this->createCompany();
        $this->createCompanyIntegrationEntity($integrationId, $company->getId());

        $this->client->request(
            'POST',
            's/companies/delete/'.$company->getId().'?mauticUserLastActive=1&mauticLastNotificationId=',
            []
        );

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $companies           = $this->em->getRepository(Company::class)->findAll();

        $requests = $GLOBALS['requests'];
        $request  = $requests['DELETE/Api/Delete/organizations/'.$integrationId][0];

        $this->assertSame(count($requests), 1);
        $this->assertSame(count($integrationEntities), 0);
        $this->assertSame(count($companies), 0);
        $this->assertEmpty($request['form_params']);
    }
}
