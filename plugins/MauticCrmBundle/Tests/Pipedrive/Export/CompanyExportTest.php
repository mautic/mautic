<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Pipedrive\Export;

use GuzzleHttp\Psr7\Response;
use Mautic\LeadBundle\Entity\Company;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;
use MauticPlugin\MauticCrmBundle\Tests\Pipedrive\PipedriveTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CompanyExportTest extends PipedriveTest
{
    private $features = [
        'objects' => [
            'company',
        ],
        'companyFields' => [
            'name'    => 'companyname',
            'address' => 'companyaddress1',
        ],
    ];

    protected function beforeBeginTransaction(): void
    {
        $this->resetAutoincrement([
            'companies',
        ]);
    }

    public function testCreateCompanyWhenFeatureIsDisabled(): void
    {
        $this->installPipedriveIntegration(
            true,
            [],
            [
                'url'   => 'Api/Post',
                'token' => 'token',
            ]
        );

        $crawler     = $this->client->request(Request::METHOD_GET, '/s/companies/new');
        $formCrawler = $crawler->filter('form[name=company]');
        $this->assertSame(1, $formCrawler->count());

        $form = $formCrawler->form();
        $form->setValues([
            'company[companyname]'     => 'Test Name',
            'company[companyaddress1]' => 'Test Address',
        ]);
        $this->client->submit($form);

        /** @var IntegrationEntity[] $integrationEntities */
        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();

        $company = $this->em->getRepository(Company::class)->findOneById(1);
        \assert($company instanceof Company);

        $this->assertNotNull($company, 'Company failed to be created');
        $this->assertSame(null, $this->mockHandler->getLastRequest(), 'Last request was not submitting the company form');
        $this->assertSame(count($integrationEntities), 0);
        $this->assertEquals($company->getName(), 'Test Name');
        $this->assertEquals($company->getAddress1(), 'Test Address');
    }

    public function testCreateCompanyWithFeatureEnabled(): void
    {
        $testName     = 'Test Name';
        $testAddress1 = 'Test Adddress 123, Wrocław, Poland';
        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Post/organizations')));

        $this->installPipedriveIntegration(
            true,
            $this->features,
            [
                'url'   => 'Api/Post',
                'token' => 'token',
            ]
        );

        $crawler     = $this->client->request(Request::METHOD_GET, '/s/companies/new');
        $formCrawler = $crawler->filter('form[name=company]');
        $this->assertSame(1, $formCrawler->count());

        $form = $formCrawler->form();
        $form->setValues([
            'company[companyname]'     => $testName,
            'company[companyaddress1]' => $testAddress1,
        ]);
        $this->client->submit($form);

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $company             = $this->em->getRepository(Company::class)->findOneById(1);
        $integrationEntity   = $integrationEntities[0];

        $this->assertStringEndsWith('Api/Post/organizations', $this->mockHandler->getLastRequest()->getUri()->getPath());
        $this->assertEquals('name=Test+Name&address=Test+Adddress+123%2C+Wroc%C5%82aw%2C+Poland', $this->mockHandler->getLastRequest()->getBody()->__toString());
        $this->assertSame(count($integrationEntities), 1);
        $this->assertEquals($company->getName(), $testName);
        $this->assertEquals($company->getAddress1(), $testAddress1);
        $this->assertEquals($integrationEntity->getInternalEntityId(), $company->getId());
        $this->assertNotNull($integrationEntity->getIntegrationEntityId());
        $this->assertEquals($integrationEntity->getIntegrationEntity(), PipedriveIntegration::ORGANIZATION_ENTITY_TYPE);
        $this->assertEquals($integrationEntity->getInternalEntity(), PipedriveIntegration::COMPANY_ENTITY_TYPE);
        $this->assertEquals($integrationEntity->getIntegration(), PipedriveIntegration::INTEGRATION_NAME);
    }

    public function testUpdateCompanyWhenFeatureIsDisabled(): void
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

        $crawler     = $this->client->request(Request::METHOD_GET, '/s/companies/edit/'.$company->getId());
        $formCrawler = $crawler->filter('form[name=company]');
        $this->assertSame(1, $formCrawler->count());

        $form = $formCrawler->form();
        $form->setValues([
            'company[companyname]'     => $testName,
            'company[companyaddress1]' => $testAddress1,
        ]);
        $this->client->submit($form);

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $companies           = $this->em->getRepository(Company::class)->findAll();
        $company             = $companies[0];

        $this->assertSame(null, $this->mockHandler->getLastRequest(), 'Last request was not submitting the company form');
        $this->assertSame(count($integrationEntities), 0);
        $this->assertSame(count($companies), 1);
        $this->assertSame($company->getName(), $testName);
        $this->assertSame($company->getAddress1(), $testAddress1);
    }

    public function testUpdateCompanyWithFeatureEnabled(): void
    {
        $integrationId = 66;
        $testName      = 'New Test Name';
        $testAddress1  = 'New Test Adddress 123, Wrocław, Poland';

        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Put/organizations')));

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

        $crawler     = $this->client->request(Request::METHOD_GET, '/s/companies/edit/'.$company->getId());
        $formCrawler = $crawler->filter('form[name=company]');
        $this->assertSame(1, $formCrawler->count());

        $form = $formCrawler->form();
        $form->setValues([
            'company[companyname]'     => $testName,
            'company[companyaddress1]' => $testAddress1,
        ]);
        $this->client->submit($form);

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $companies           = $this->em->getRepository(Company::class)->findAll();
        $company             = $companies[0];

        $this->assertStringEndsWith('Api/Put/organizations/'.$integrationId, $this->mockHandler->getLastRequest()->getUri()->getPath());
        $this->assertEquals('name=New+Test+Name&address=New+Test+Adddress+123%2C+Wroc%C5%82aw%2C+Poland', $this->mockHandler->getLastRequest()->getBody()->__toString());
        $this->assertSame(count($integrationEntities), 1);
        $this->assertSame(count($companies), 1);
        $this->assertSame($company->getName(), $testName);
        $this->assertSame($company->getAddress1(), $testAddress1);
    }

    public function testDeleteCompanyWhenFeatureIsDisabled(): void
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
            Request::METHOD_POST,
            's/companies/delete/'.$company->getId().'?mauticUserLastActive=1&mauticLastNotificationId=',
            []
        );

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $companies           = $this->em->getRepository(Company::class)->findAll();

        $this->assertSame(null, $this->mockHandler->getLastRequest(), 'Last request was not submitting the company form');
        $this->assertSame(count($integrationEntities), 0);
        $this->assertSame(count($companies), 0);
    }

    public function testDeleteCompany(): void
    {
        $integrationId = 66;

        $this->mockHandler->append(new Response(SymfonyResponse::HTTP_OK, [], self::getData('Api/Delete/organizations')));

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
            Request::METHOD_POST,
            's/companies/delete/'.$company->getId().'?mauticUserLastActive=1&mauticLastNotificationId=',
            []
        );

        $integrationEntities = $this->em->getRepository(IntegrationEntity::class)->findAll();
        $companies           = $this->em->getRepository(Company::class)->findAll();

        $this->assertStringEndsWith('Api/Delete/organizations/'.$integrationId, $this->mockHandler->getLastRequest()->getUri()->getPath());
        $this->assertSame(count($integrationEntities), 0);
        $this->assertSame(count($companies), 0);
    }
}
