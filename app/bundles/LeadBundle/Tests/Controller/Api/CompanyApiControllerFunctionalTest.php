<?php

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\Tag;
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
        $this->configParams['api_enabled']                         = 'testDisabledApi' !== $this->name();
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

    public function testAddAndRemoveCompanyTagsViaApi(): void
    {
        $companyId = $this->createCompanyViaApi('API tagged company');

        $this->client->request('POST', sprintf('/api/companies/%d/tags/add', $companyId), [
            'tags' => ['Enterprise', 'VIP'],
        ]);
        $addTagsResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $addTagsResponse->getStatusCode(), $addTagsResponse->getContent());

        /** @var Company|null $company */
        $company = $this->em->getRepository(Company::class)->find($companyId);
        $this->assertInstanceOf(Company::class, $company);
        $companyTagNames = array_map(
            static fn (Tag $tag): string => $tag->getTag(),
            $company->getTags()->toArray()
        );
        sort($companyTagNames);
        $this->assertSame(['Enterprise', 'VIP'], $companyTagNames);

        $enterpriseTag = current(array_filter(
            $company->getTags()->toArray(),
            static fn (Tag $tag): bool => 'Enterprise' === $tag->getTag()
        ));
        $this->assertInstanceOf(Tag::class, $enterpriseTag);
        \assert($enterpriseTag instanceof Tag);

        $this->client->request('POST', sprintf('/api/companies/%d/tag/%d/remove', $companyId, $enterpriseTag->getId()));
        $removeTagResponse = $this->client->getResponse();
        $this->assertSame(Response::HTTP_OK, $removeTagResponse->getStatusCode(), $removeTagResponse->getContent());

        $this->em->clear();

        /** @var Company|null $updatedCompany */
        $updatedCompany = $this->em->getRepository(Company::class)->find($companyId);
        $this->assertInstanceOf(Company::class, $updatedCompany);
        $updatedTagNames = array_map(
            static fn (Tag $tag): string => $tag->getTag(),
            $updatedCompany->getTags()->toArray()
        );

        $this->assertNotContains('Enterprise', $updatedTagNames);
        $this->assertContains('VIP', $updatedTagNames);
    }

    public function testAddCompanyTagsViaApiRejectsEmptyPayload(): void
    {
        $companyId = $this->createCompanyViaApi('API empty tags company');

        $this->client->request('POST', sprintf('/api/companies/%d/tags/add', $companyId), []);
        $response = $this->client->getResponse();

        $this->assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode(), $response->getContent());
    }

    /**
     * Test creating a company via API Platform v2 endpoint.
     *
     * @param array<string, mixed> $companyData
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('companyCreateDataProvider')]
    public function testCreateCompanyViaApiPlatform(array $companyData, int $expectedStatusCode): void
    {
        $this->client->request(
            'POST',
            '/api/v2/companies',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/ld+json',
                'HTTP_ACCEPT'  => 'application/ld+json',
            ],
            json_encode($companyData)
        );

        $response = $this->client->getResponse();
        $this->assertSame($expectedStatusCode, $response->getStatusCode(), $response->getContent());

        if (Response::HTTP_CREATED === $expectedStatusCode) {
            $responseData = json_decode($response->getContent(), true);

            $this->assertIsArray($responseData);
            $this->assertArrayHasKey('id', $responseData);
            $this->assertArrayHasKey('score', $responseData);

            // Verify the company was actually created in the database
            $companyRepository = $this->em->getRepository(\Mautic\LeadBundle\Entity\Company::class);
            $company           = $companyRepository->find($responseData['id']);

            $this->assertInstanceOf(\Mautic\LeadBundle\Entity\Company::class, $company);
            $this->assertSame($companyData['name'] ?? null, $company->getName());
            $this->assertSame($companyData['score'] ?? 0, $company->getScore());
            $this->assertSame($companyData['city'] ?? null, $company->getCity());
            $this->assertSame($companyData['state'] ?? null, $company->getState());
            $this->assertSame($companyData['country'] ?? null, $company->getCountry());
            $this->assertSame($companyData['industry'] ?? null, $company->getIndustry());
        }
    }

    /**
     * @return array<string, array{companyData: array<string, mixed>, expectedStatusCode: int}>
     */
    public static function companyCreateDataProvider(): array
    {
        return [
            'valid company with all fields' => [
                'companyData' => [
                    'score'       => 0,
                    'socialCache' => [],
                    'city'        => 'Boston',
                    'state'       => 'Massachusetts',
                    'country'     => 'United States',
                    'name'        => 'Mautic',
                    'industry'    => 'Software',
                ],
                'expectedStatusCode' => Response::HTTP_CREATED,
            ],
        ];
    }

    private function createCompanyViaApi(string $name): int
    {
        $this->client->request('POST', '/api/companies/new', ['companyname' => $name]);
        $response = $this->client->getResponse();
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode(), $response->getContent());

        $payload = json_decode($response->getContent(), true);
        $this->assertIsArray($payload);
        $this->assertArrayHasKey('company', $payload);
        $this->assertArrayHasKey('id', $payload['company']);

        return (int) $payload['company']['id'];
    }
}
