<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\Permission;
use Mautic\UserBundle\Entity\User;
use Mautic\UserBundle\Model\RoleModel;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CompanyApiControllerFunctionalTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private const SALES_USER = 'sales';

    private CompanyModel $companyModel;

    private LeadModel $leadModel;

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

        $this->companyModel = static::getContainer()->get(CompanyModel::class);
        $this->leadModel    = static::getContainer()->get(LeadModel::class);
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
                'companyname' => 'BatchUpdate',
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
            'companyname' => 'API',
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

    public function testBatchAddContactsSuccess(): void
    {
        $company = $this->createCompany('Batch Co A', 'batch-co-a@example.com');
        $contact = $this->createLead('Batch', 'Success', 'batch-success@example.com');
        $this->em->flush();

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        Assert::assertSame(1, $response['summary']['total']);
        Assert::assertSame(1, $response['summary']['succeeded']);
        Assert::assertSame(0, $response['summary']['failed']);
        Assert::assertSame(Response::HTTP_OK, $response['results'][0]['status']);
        Assert::assertSame('Contact added to company', $response['results'][0]['message']);
        Assert::assertTrue($this->hasContactCompany($contact->getId(), $company->getId()));
    }

    public function testBatchAddContactsPartialFailure(): void
    {
        $company = $this->createCompany('Batch Co B', 'batch-co-b@example.com');
        $contact = $this->createLead('Batch', 'Partial', 'batch-partial@example.com');
        $this->em->flush();

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
                ['contactId' => $contact->getId(), 'companyId' => 999999],
                ['contactId' => 999998, 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        Assert::assertSame(3, $response['summary']['total']);
        Assert::assertSame(1, $response['summary']['succeeded']);
        Assert::assertSame(2, $response['summary']['failed']);
        Assert::assertSame(Response::HTTP_OK, $response['results'][0]['status']);
        Assert::assertSame(Response::HTTP_NOT_FOUND, $response['results'][1]['status']);
        Assert::assertSame('Company not found', $response['results'][1]['message']);
        Assert::assertSame(Response::HTTP_NOT_FOUND, $response['results'][2]['status']);
        Assert::assertSame('Contact not found', $response['results'][2]['message']);
    }

    public function testBatchAddContactsEmptyBody(): void
    {
        $this->requestBatchAddContacts([]);

        Assert::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testBatchAddContactsDuplicatePairs(): void
    {
        $company = $this->createCompany('Batch Co C', 'batch-co-c@example.com');
        $contact = $this->createLead('Batch', 'Dup', 'batch-dup@example.com');
        $this->em->flush();

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        Assert::assertSame(2, $response['summary']['total']);
        Assert::assertSame(2, $response['summary']['succeeded']);
        Assert::assertSame(0, $response['summary']['failed']);
        Assert::assertCount(2, $response['results']);
        Assert::assertSame(Response::HTTP_OK, $response['results'][0]['status']);
        Assert::assertSame(Response::HTTP_OK, $response['results'][1]['status']);
        Assert::assertTrue($this->hasContactCompany($contact->getId(), $company->getId()));
    }

    public function testBatchAddContactsAlreadyAssigned(): void
    {
        $company = $this->createCompany('Batch Co D', 'batch-co-d@example.com');
        $contact = $this->createLead('Batch', 'Existing', 'batch-existing@example.com');
        $this->em->flush();
        $this->companyModel->addLeadToCompany($company, $contact);

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        Assert::assertSame(Response::HTTP_OK, $response['results'][0]['status']);
        Assert::assertSame(1, $response['summary']['succeeded']);
        Assert::assertTrue($this->hasContactCompany($contact->getId(), $company->getId()));
    }

    public function testBatchAddContactsNoPermissionPerItem(): void
    {
        $adminContact = $this->createLead('Batch', 'Admin', 'batch-admin-contact@example.com');
        $company      = $this->createCompany('Batch Co E', 'batch-co-e@example.com');
        $this->em->flush();

        $salesUser = $this->em->getRepository(User::class)->findOneBy(['username' => self::SALES_USER]);
        Assert::assertInstanceOf(User::class, $salesUser);
        $this->setLeadPermissions($salesUser, ['editown']);

        $this->client->setServerParameter('PHP_AUTH_USER', self::SALES_USER);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => $adminContact->getId(), 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        Assert::assertSame(Response::HTTP_FORBIDDEN, $response['results'][0]['status']);
        Assert::assertSame('Access denied', $response['results'][0]['message']);
        Assert::assertFalse($this->hasContactCompany($adminContact->getId(), $company->getId()));
    }

    public function testBatchAddContactsGlobalForbiddenWithoutEditPermission(): void
    {
        $salesUser = $this->em->getRepository(User::class)->findOneBy(['username' => self::SALES_USER]);
        Assert::assertInstanceOf(User::class, $salesUser);
        $this->setLeadPermissions($salesUser, ['viewown', 'viewother']);

        $this->client->setServerParameter('PHP_AUTH_USER', self::SALES_USER);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->requestBatchAddContacts([
            'assignments' => [
                ['contactId' => 1, 'companyId' => 1],
            ],
        ]);

        Assert::assertSame(Response::HTTP_FORBIDDEN, $this->client->getResponse()->getStatusCode());
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

        Assert::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
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
        Assert::assertNotFalse($content);

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    private function hasContactCompany(int $contactId, int $companyId): bool
    {
        $contact = $this->leadModel->getEntity($contactId);
        $company = $this->companyModel->getEntity($companyId);
        \assert(null !== $contact && null !== $company);

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
        Assert::assertNotNull($role);

        $this->em->createQueryBuilder()
            ->delete(Permission::class, 'p')
            ->where('p.bundle = :bundle')
            ->andWhere('p.role = :role_id')
            ->setParameters(['bundle' => 'lead', 'role_id' => $role->getId()])
            ->getQuery()
            ->execute();

        $role->setIsAdmin(false);
        $roleModel = static::getContainer()->get('mautic.user.model.role');
        \assert($roleModel instanceof RoleModel);
        $roleModel->setRolePermissions($role, ['lead:leads' => $permissions]);
        $this->em->persist($role);
        $this->em->flush();
    }
}
