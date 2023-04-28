<?php

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Response;

class CompanyApiControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function markCompanyEmailAsUnique(): void
    {
        $fieldRepository   = $this->em->getRepository(LeadField::class);
        $companyEmailField = $fieldRepository->findOneBy(['alias' => 'companyemail']);
        \assert($companyEmailField instanceof LeadField);
        $companyEmailField->setIsUniqueIdentifer(true);
        $this->em->persist($companyEmailField);
        $this->em->flush();
    }

    protected function setUp(): void
    {
        // Disable API just for specific test.
        $this->configParams['api_enabled']                         = 'testDisabledApi' !== $this->getName();
        $this->configParams['company_unique_identifiers_operator'] = 'AND';

        parent::setUp();
    }

    public function testBatchNewEndpoint(): void
    {
        $this->markCompanyEmailAsUnique();

        $payload = [
            [
                'companyname' => 'BatchUpdate',
            ],
            [
                'companyname' => 'BatchUpdate2',
            ],
            [
                'companyname' => 'BatchUpdate3',
            ],
        ];

        // create 3 new companies
        $this->client->request('POST', '/api/companies/batch/new', $payload);
        $clientResponse = $this->client->getResponse();

        Assert::assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);

        // Assert status codes
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][0]);
        $companyId1 = $response['companies'][0]['id'];
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][1]);
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][2]);

        // Assert email
        $this->assertEquals($payload[0]['companyname'], $response['companies'][0]['fields']['all']['companyname']);
        $this->assertEquals($payload[1]['companyname'], $response['companies'][1]['fields']['all']['companyname']);
        $this->assertEquals($payload[2]['companyname'], $response['companies'][2]['fields']['all']['companyname']);

        $payload = [
            [
                'companyname'        => 'BatchUpdate',
            ],
        ];

        // use unique field to not create new company
        $this->client->request('POST', '/api/companies/batch/new', $payload);
        $clientResponse = $this->client->getResponse();

        Assert::assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][0]);
        $this->assertEquals($companyId1, $response['companies'][0]['id']);

        // Assert email
        $this->assertEquals('BatchUpdate', $response['companies'][0]['fields']['all']['companyname']);

        $payload = [
            [
                'companyname'  => 'BatchUpdate',
                'companyemail' => 'BatchUpdate@update.com',
            ],
        ];

        // use both unique fields and create new, because use AND operator between unique fields
        $this->client->request('POST', '/api/companies/batch/new', $payload);
        $clientResponse = $this->client->getResponse();

        Assert::assertSame(Response::HTTP_CREATED, $clientResponse->getStatusCode(), $clientResponse->getContent());

        $response = json_decode($clientResponse->getContent(), true);

        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][0]);
        $this->assertNotEquals($companyId1, $response['companies'][0]['id']);
    }

    public function testSingleNewEndpoint(): void
    {
        $this->markCompanyEmailAsUnique();

        $payload = [
            'companyname'            => 'API',
        ];

        $this->client->request('POST', '/api/companies/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $companyId      = $response['company']['id'];

        $this->assertEquals($payload['companyname'], $response['company']['fields']['all']['companyname']);

        // Lets try to create the same company
        $this->client->request('POST', '/api/companies/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertEquals($companyId, $response['company']['id']);

        $payload = [
            'companyname'  => 'API',
            'companyemail' => 'api@api.com',
        ];

        // Lets try to create the new company because use unique fields with AND operator
        $this->client->request('POST', '/api/companies/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        $this->assertNotEquals($companyId, $response['company']['id']);
    }
}
