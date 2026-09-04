<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyChangeLog;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Tests\TestEntityCreationTrait;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class CompanyApiControllerFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait {
        CreateTestEntitiesTrait::createCompany as createNamedCompany;
        CreateTestEntitiesTrait::createLead as createNamedLead;
    }
    use TestEntityCreationTrait {
        TestEntityCreationTrait::createCompany insteadof CreateTestEntitiesTrait;
    }

    private const SALES_USER = 'sales';

    private CompanyModel $companyModel;

    private LeadModel $leadModel;

    protected function setUp(): void
    {
        // Disable API just for specific test.
        $this->configParams['api_enabled']                               = 'testDisabledApi' !== $this->name();
        $this->configParams['company_unique_identifiers_operator']       = 'AND';
        $this->configParams['update_company_mapping_data_in_background'] = !str_ends_with($this->name(), 'InHttpRequest');

        parent::setUp();

        $this->companyModel = self::getContainer()->get(CompanyModel::class);
        $this->leadModel    = self::getContainer()->get(LeadModel::class);
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

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

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

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

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

        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

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

    /**
     * Test creating a company via API Platform v2 endpoint.
     *
     * @param array<string, mixed> $companyData
     */
    #[DataProvider('companyCreateDataProvider')]
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
        $this->assertResponseStatusCodeSame($expectedStatusCode);

        if (Response::HTTP_CREATED === $expectedStatusCode) {
            $responseData = json_decode($response->getContent(), true);

            $this->assertIsArray($responseData);
            $this->assertArrayHasKey('id', $responseData);
            $this->assertArrayHasKey('score', $responseData);

            // Verify the company was actually created in the database
            $companyRepository = $this->em->getRepository(Company::class);
            $company           = $companyRepository->find($responseData['id']);

            $this->assertInstanceOf(Company::class, $company);
            $this->assertSame($companyData['name'] ?? null, $company->getName());
            $this->assertSame($companyData['score'] ?? 0, $company->getScore());
            $this->assertSame($companyData['city'] ?? null, $company->getCity());
            $this->assertSame($companyData['state'] ?? null, $company->getState());
            $this->assertSame($companyData['country'] ?? null, $company->getCountry());
            $this->assertSame($companyData['industry'] ?? null, $company->getIndustry());
        }
    }

    /**
     * @return \Iterator<string, array{companyData: array<string, mixed>, expectedStatusCode: int}>
     */
    public static function companyCreateDataProvider(): \Iterator
    {
        yield 'valid company with all fields' => [
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
        ];
    }

    public function testCreateNewCompany(): void
    {
        $payload = [
            'companyname'     => 'Company A',
            'companyemail'    => 'test@company.com',
            'companycity'     => 'City',
            'companyaddress1' => 'Address one',
            'companyaddress2' => 'Address two',
            'companyphone'    => '123456789',
            'companywebsite'  => 'https://company.com',
        ];
        $this->client->request('POST', '/api/companies/new', $payload);

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        foreach ($payload as $alias => $value) {
            $this->assertEquals($value, $response['company']['fields']['all'][$alias]);
        }
    }

    public function testCreateCompaniesInBatch(): void
    {
        $payload = [
            [
                'companyname'     => 'Company A',
                'companyemail'    => 'test@company-a.com',
                'companycity'     => 'City A',
                'companyaddress1' => 'Address A one',
                'companyaddress2' => 'Address A two',
                'companyphone'    => '123456789',
                'companywebsite'  => 'https://company.a.com',
            ],
            [
                'companyname'     => 'Company B',
                'companyemail'    => 'test@company-b.com',
                'companycity'     => 'City B',
                'companyaddress1' => 'Address B one',
                'companyaddress2' => 'Address B two',
                'companyphone'    => '123456789',
                'companywebsite'  => 'https://company.b.com',
            ],
            [
                'companyname'     => 'Company B',
                'companyemail'    => 'test@company-b.com',
                'companycity'     => 'City B',
                'companyaddress1' => 'Address B one',
                'companyaddress2' => 'Address B two',
                'companyphone'    => '123456789',
                'companywebsite'  => 'https://company.b.com',
            ],
        ];
        $this->client->request('POST', '/api/companies/batch/new', $payload);

        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        // Assert status codes
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][0]);
        $this->assertEquals(Response::HTTP_CREATED, $response['statusCodes'][1]);
        // The third item of payload is duplicate of the second, So expect the 200 only
        $this->assertEquals(Response::HTTP_OK, $response['statusCodes'][2]);

        foreach ($response['companies'] as $index => $company) {
            foreach ($payload[$index] as $alias => $value) {
                $this->assertEquals($value, $company['fields']['all'][$alias]);
            }
        }
    }

    public function testDeleteCompanyInBackground(): void
    {
        $company = $this->createCompany();
        $this->client->request('DELETE', sprintf('/api/companies/%d/delete', $company->getId()));

        $this->client->getResponse();
        $this->assertResponseIsSuccessful();

        $company = $this->getCompanyRepository()->find($company->getId());
        $this->assertInstanceOf(Company::class, $company);
        $this->asserttrue($company->isDeleted());
    }

    public function testDeleteCompanyInHttpRequest(): void
    {
        $company   = $this->createCompany();
        $companyId = $company->getId();
        $this->client->request('DELETE', sprintf('/api/companies/%d/delete', $company->getId()));

        $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $company = $this->getCompanyRepository()->find($companyId);
        $this->assertNotInstanceOf(Company::class, $company);
    }

    public function testDeleteCompaniesInBatchInHttpRequest(): void
    {
        $company1   = $this->createCompany();
        $company2   = $this->createCompany();

        $companyId1 = $company1->getId();
        $companyId2 = $company2->getId();

        $payload = [
            $companyId1,
            $companyId2,
        ];

        $this->client->request('DELETE', sprintf('/api/companies/batch/delete?ids=%s', implode(',', $payload)));

        $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $company1 = $this->getCompanyRepository()->find($companyId1);
        $this->assertNotInstanceOf(Company::class, $company1);
        $company2 = $this->getCompanyRepository()->find($companyId2);
        $this->assertNotInstanceOf(Company::class, $company2);
    }

    public function testDeleteCompaniesInBatch(): void
    {
        $company1   = $this->createCompany();
        $company2   = $this->createCompany();

        $payload = [
            $company1->getId(),
            $company2->getId(),
        ];

        $this->client->request('DELETE', sprintf('/api/companies/batch/delete?ids=%s', implode(',', $payload)));

        $this->client->getResponse();
        $this->assertResponseIsSuccessful();
        $company1 = $this->getCompanyRepository()->find($company1->getId());
        $this->assertInstanceOf(Company::class, $company1);
        $this->asserttrue($company1->isDeleted());
        $company2 = $this->getCompanyRepository()->find($company2->getId());
        $this->assertInstanceOf(Company::class, $company2);
        $this->asserttrue($company2->isDeleted());
    }

    public function testEditCompaniesInBatchInHttpRequest(): void
    {
        $company1   = $this->createCompany();
        $company2   = $this->createCompany();
        $company1Id = $company1->getId();
        $company2Id = $company2->getId();

        $contact1   = $this->createContact();
        $contact2   = $this->createContact();
        $contact1Id = $contact1->getId();
        $contact2Id = $contact2->getId();

        $this->attachContactToCompany($contact1, $company1, true);
        $this->attachContactToCompany($contact2, $company2, true);
        $this->attachContactToCompany($contact2, $company1);

        $company1UpdatedName = 'Company 1 Updated';
        $company2UpdatedName = 'Company 2 Updated';
        $payload             = [
            [
                'id'          => $company1->getId(),
                'companyname' => $company1UpdatedName,
            ],
            [
                'id'          => $company2->getId(),
                'companyname' => $company2UpdatedName,
            ],
        ];

        $this->client->request('PATCH', '/api/companies/batch/edit', $payload);
        $this->client->getResponse();
        $this->em->clear();

        $this->assertResponseIsSuccessful();
        $this->assertSame($company1UpdatedName, $this->getCompanyRepository()->find($company1Id)->getName());
        $this->assertSame($company2UpdatedName, $this->getCompanyRepository()->find($company2Id)->getName());

        $contactRepo = $this->getContactRepository();
        $this->assertSame($company1UpdatedName, $contactRepo->getEntity($contact1Id)->getCompany());
        $this->assertSame($company2UpdatedName, $contactRepo->getEntity($contact2Id)->getCompany());
    }

    public function testEditCompanyInHttpRequest(): void
    {
        $company1   = $this->createCompany();
        $company2   = $this->createCompany();
        $company1Id = $company1->getId();

        $contact1   = $this->createContact();
        $contact2   = $this->createContact();
        $contact1Id = $contact1->getId();

        $this->attachContactToCompany($contact1, $company1, true);
        $this->attachContactToCompany($contact2, $company2, true);
        $this->attachContactToCompany($contact2, $company1);

        $company1UpdatedName = 'Company 1 Updated';
        $payload             = [
            'id'          => $company1->getId(),
            'companyname' => $company1UpdatedName,
        ];

        $this->client->request('PATCH', sprintf('/api/companies/%d/edit', $company1Id), $payload);
        $this->client->getResponse();
        $this->em->clear();

        $this->assertResponseIsSuccessful();
        $this->assertSame($company1UpdatedName, $this->getCompanyRepository()->find($company1Id)->getName());

        $contactRepo = $this->getContactRepository();
        $this->assertSame($company1UpdatedName, $contactRepo->getEntity($contact1Id)->getCompany());
    }

    public function testBatchAddContactsSuccess(): void
    {
        $company = $this->createNamedCompany('Batch Co A', 'batch-co-a@example.com');
        $contact = $this->createNamedLead('Batch', 'Success', 'batch-success@example.com');
        $this->em->flush();

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        self::assertResponseIsSuccessful();
        $this->assertSame(1, $response['summary']['total']);
        $this->assertSame(1, $response['summary']['succeeded']);
        $this->assertSame(0, $response['summary']['failed']);
        $this->assertSame(Response::HTTP_OK, $response['results'][0]['status']);
        $this->assertSame('Contact added to company', $response['results'][0]['message']);
        $this->assertTrue($this->hasContactCompany($contact->getId(), $company->getId()));

        $logs = $this->getCompanyChangeLogsForContact($contact->getId());
        $this->assertCount(1, $logs);
        $this->assertSame('api', $logs[0]->getType());
        $this->assertSame('API batch assignment', $logs[0]->getEventName());
        $this->assertSame('Lead added to the company, Batch Co A', $logs[0]->getActionName());
        $this->assertSame($company->getId(), $logs[0]->getCompany());
    }

    public function testBatchAddContactsPartialFailure(): void
    {
        $company = $this->createNamedCompany('Batch Co B', 'batch-co-b@example.com');
        $contact = $this->createNamedLead('Batch', 'Partial', 'batch-partial@example.com');
        $this->em->flush();

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
                ['contactId' => $contact->getId(), 'companyId' => 999999],
                ['contactId' => 999998, 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        self::assertResponseIsSuccessful();
        $this->assertSame(3, $response['summary']['total']);
        $this->assertSame(1, $response['summary']['succeeded']);
        $this->assertSame(2, $response['summary']['failed']);
        $this->assertSame(Response::HTTP_OK, $response['results'][0]['status']);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response['results'][1]['status']);
        $this->assertSame('Company not found', $response['results'][1]['message']);
        $this->assertSame(Response::HTTP_NOT_FOUND, $response['results'][2]['status']);
        $this->assertSame('Contact not found', $response['results'][2]['message']);
        $this->assertCount(1, $this->getCompanyChangeLogsForContact($contact->getId()));
    }

    public function testBatchAddContactsEmptyBody(): void
    {
        $this->requestBatchAddContacts([]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testBatchAddContactsDuplicatePairs(): void
    {
        $company = $this->createNamedCompany('Batch Co C', 'batch-co-c@example.com');
        $contact = $this->createNamedLead('Batch', 'Dup', 'batch-dup@example.com');
        $this->em->flush();

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        self::assertResponseIsSuccessful();
        $this->assertSame(2, $response['summary']['total']);
        $this->assertSame(2, $response['summary']['succeeded']);
        $this->assertSame(0, $response['summary']['failed']);
        $this->assertCount(2, $response['results']);
        $this->assertSame(Response::HTTP_OK, $response['results'][0]['status']);
        $this->assertSame(Response::HTTP_OK, $response['results'][1]['status']);
        $this->assertTrue($this->hasContactCompany($contact->getId(), $company->getId()));
    }

    public function testBatchAddContactsAlreadyAssigned(): void
    {
        $company = $this->createNamedCompany('Batch Co D', 'batch-co-d@example.com');
        $contact = $this->createNamedLead('Batch', 'Existing', 'batch-existing@example.com');
        $this->em->flush();
        $this->companyModel->addLeadToCompany($company, $contact);

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        self::assertResponseIsSuccessful();
        $this->assertSame(Response::HTTP_OK, $response['results'][0]['status']);
        $this->assertSame(1, $response['summary']['succeeded']);
        $this->assertTrue($this->hasContactCompany($contact->getId(), $company->getId()));
        $this->assertCount(0, $this->getCompanyChangeLogsForContact($contact->getId()));
    }

    public function testSingleAddContactDoesNotCreateBatchChangeLog(): void
    {
        $company = $this->createNamedCompany('Single Co', 'single-co@example.com');
        $contact = $this->createNamedLead('Single', 'Add', 'single-add@example.com');
        $this->em->flush();

        $this->client->request(
            Request::METHOD_POST,
            sprintf('/api/companies/%d/contact/%d/add', $company->getId(), $contact->getId())
        );

        self::assertResponseIsSuccessful();
        $this->assertTrue($this->hasContactCompany($contact->getId(), $company->getId()));

        $logs = $this->getCompanyChangeLogsForContact($contact->getId());
        $this->assertCount(1, $logs);
        $this->assertSame('api', $logs[0]->getType());
        $this->assertSame('API assignment', $logs[0]->getEventName());
        $this->assertNotSame('API batch assignment', $logs[0]->getEventName());
        $this->assertSame('Lead added to the company, Single Co', $logs[0]->getActionName());
        $this->assertSame($company->getId(), $logs[0]->getCompany());
    }

    public function testSingleAddContactAlreadyAssigned(): void
    {
        $company = $this->createNamedCompany('Single Co Existing', 'single-co-existing@example.com');
        $contact = $this->createNamedLead('Single', 'Existing', 'single-existing@example.com');
        $this->em->flush();
        $this->companyModel->addLeadToCompany($company, $contact);

        $this->client->request(
            Request::METHOD_POST,
            sprintf('/api/companies/%d/contact/%d/add', $company->getId(), $contact->getId())
        );

        self::assertResponseIsSuccessful();
        $this->assertTrue($this->hasContactCompany($contact->getId(), $company->getId()));
        $this->assertCount(0, $this->getCompanyChangeLogsForContact($contact->getId()));
    }

    public function testBatchAddContactsNoPermissionPerItem(): void
    {
        $adminContact = $this->createNamedLead('Batch', 'Admin', 'batch-admin-contact@example.com');
        $company      = $this->createNamedCompany('Batch Co E', 'batch-co-e@example.com');
        $this->em->flush();

        $salesUser = $this->em->getRepository(User::class)->findOneBy(['username' => self::SALES_USER]);
        $this->assertInstanceOf(User::class, $salesUser);
        $this->setLeadPermissions($salesUser, ['editown']);

        $this->client->setServerParameter('PHP_AUTH_USER', self::SALES_USER);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => $adminContact->getId(), 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        self::assertResponseIsSuccessful();
        $this->assertSame(Response::HTTP_FORBIDDEN, $response['results'][0]['status']);
        $this->assertSame('Access denied', $response['results'][0]['message']);
        $this->assertFalse($this->hasContactCompany($adminContact->getId(), $company->getId()));
    }

    public function testBatchAddContactsGlobalForbiddenWithoutEditPermission(): void
    {
        $salesUser = $this->em->getRepository(User::class)->findOneBy(['username' => self::SALES_USER]);
        $this->assertInstanceOf(User::class, $salesUser);
        $this->setLeadPermissions($salesUser, ['viewown', 'viewother']);

        $this->client->setServerParameter('PHP_AUTH_USER', self::SALES_USER);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => 1, 'companyId' => 1],
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testBatchAddContactsExceedsBatchLimit(): void
    {
        $assignments = [];
        for ($i = 0; $i < 201; ++$i) {
            $assignments[] = ['contactId' => 1, 'companyId' => 1];
        }

        $this->requestBatchAddContacts([
            'assignments' => $assignments,
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requestBatchAddContacts(array $payload): void
    {
        $this->client->request(
            Request::METHOD_POST,
            '/api/companies/batch/addcontacts',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeResponse(): array
    {
        $content = $this->client->getResponse()->getContent();
        $this->assertNotFalse($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<CompanyChangeLog>
     */
    private function getCompanyChangeLogsForContact(int $contactId): array
    {
        $this->em->clear();
        $contact = $this->leadModel->getEntity($contactId);
        $this->assertInstanceOf(Lead::class, $contact);

        return $this->em->getRepository(CompanyChangeLog::class)->findBy(
            ['lead' => $contact],
            ['dateAdded' => 'ASC']
        );
    }

    private function hasContactCompany(int $contactId, int $companyId): bool
    {
        $contact = $this->leadModel->getEntity($contactId);
        $company = $this->companyModel->getEntity($companyId);
        $this->assertInstanceOf(Lead::class, $contact);
        $this->assertInstanceOf(Company::class, $company);

        return null !== $this->em->getRepository(CompanyLead::class)->findOneBy([
            'lead'    => $contact,
            'company' => $company,
        ]);
    }

    /**
     * @param list<string> $permissions
     */
    private function setLeadPermissions(User $user, array $permissions): void
    {
        $role = $user->getRole();
        $this->assertInstanceOf(\Mautic\UserBundle\Entity\Role::class, $role);

        $this->em->createQueryBuilder()
            ->delete(Permission::class, 'p')
            ->where('p.bundle = :bundle')
            ->andWhere('p.role = :role_id')
            ->setParameters(['bundle' => 'lead', 'role_id' => $role->getId()])
            ->getQuery()
            ->execute();

        $role->setIsAdmin(false);
        $roleModel = self::getContainer()->get(RoleModel::class);
        $roleModel->setRolePermissions($role, ['lead:leads' => $permissions]);
        $this->em->persist($role);
        $this->em->flush();
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function markCompanyEmailAsUnique(): void
    {
        $fieldRepository   = $this->em->getRepository(LeadField::class);
        $companyEmailField = $fieldRepository->findOneBy(['alias' => 'companyemail']);
        $this->assertInstanceOf(LeadField::class, $companyEmailField);
        $companyEmailField->setIsUniqueIdentifer(true);
        $this->em->persist($companyEmailField);
        $this->em->flush();
    }

    private function getCompanyRepository(): CompanyRepository
    {
        /** @var CompanyRepository */
        return $this->em->getRepository(Company::class);
    }

    private function getContactRepository(): LeadRepository
    {
        /** @var LeadRepository */
        return $this->em->getRepository(Lead::class);
    }
}
