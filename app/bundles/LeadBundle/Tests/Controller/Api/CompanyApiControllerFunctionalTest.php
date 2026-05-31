<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller\Api;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyLead;
use Mautic\LeadBundle\Entity\Lead;
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
    private const SALES_USER = 'sales';

    private CompanyModel $companyModel;

    private LeadModel $leadModel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyModel = static::getContainer()->get('mautic.lead.model.company');
        $this->leadModel    = static::getContainer()->get('mautic.lead.model.lead');
    }

    public function testBatchAddContactsSuccess(): void
    {
        $company = $this->createCompany('Batch Co A');
        $contact = $this->createContact('batch-success@example.com');

        $this->client->request(Request::METHOD_POST, '/api/companies/batch/addcontacts', [
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
        Assert::assertTrue($this->isContactInCompany($contact->getId(), $company->getId()));
    }

    public function testBatchAddContactsPartialFailure(): void
    {
        $company = $this->createCompany('Batch Co B');
        $contact = $this->createContact('batch-partial@example.com');

        $this->client->request(Request::METHOD_POST, '/api/companies/batch/addcontacts', [
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
        $this->client->request(Request::METHOD_POST, '/api/companies/batch/addcontacts', []);

        Assert::assertSame(Response::HTTP_BAD_REQUEST, $this->client->getResponse()->getStatusCode());
    }

    public function testBatchAddContactsDuplicatePairs(): void
    {
        $company = $this->createCompany('Batch Co C');
        $contact = $this->createContact('batch-dup@example.com');

        $this->client->request(Request::METHOD_POST, '/api/companies/batch/addcontacts', [
            'assignments' => [
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        // total reflects original input count (including duplicates), succeeded+failed == total
        Assert::assertSame(2, $response['summary']['total']);
        Assert::assertSame(2, $response['summary']['succeeded']);
        Assert::assertSame(0, $response['summary']['failed']);
        Assert::assertCount(2, $response['results']);
        Assert::assertSame(Response::HTTP_OK, $response['results'][0]['status']);
        Assert::assertSame(Response::HTTP_OK, $response['results'][1]['status']);
        // only one DB row despite two identical input pairs
        Assert::assertSame(1, $this->countCompanyLeadRows($contact->getId(), $company->getId()));
    }

    public function testBatchAddContactsAlreadyAssigned(): void
    {
        $company = $this->createCompany('Batch Co D');
        $contact = $this->createContact('batch-existing@example.com');
        $this->companyModel->addLeadToCompany($company, $contact);

        $this->client->request(Request::METHOD_POST, '/api/companies/batch/addcontacts', [
            'assignments' => [
                ['contactId' => $contact->getId(), 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        Assert::assertSame(Response::HTTP_OK, $response['results'][0]['status']);
        Assert::assertSame(1, $response['summary']['succeeded']);
        // idempotent — still exactly one row in DB
        Assert::assertSame(1, $this->countCompanyLeadRows($contact->getId(), $company->getId()));
    }

    public function testBatchAddContactsNoPermissionPerItem(): void
    {
        $adminContact = $this->createContact('batch-admin-contact@example.com');
        $company      = $this->createCompany('Batch Co E');

        $salesUser = $this->em->getRepository(User::class)->findOneBy(['username' => self::SALES_USER]);
        Assert::assertInstanceOf(User::class, $salesUser);
        $this->setLeadPermissions($salesUser, ['editown']);

        $this->client->setServerParameter('PHP_AUTH_USER', self::SALES_USER);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request(Request::METHOD_POST, '/api/companies/batch/addcontacts', [
            'assignments' => [
                ['contactId' => $adminContact->getId(), 'companyId' => $company->getId()],
            ],
        ]);

        $response = $this->decodeResponse();
        Assert::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        Assert::assertSame(Response::HTTP_FORBIDDEN, $response['results'][0]['status']);
        Assert::assertSame('Access denied', $response['results'][0]['message']);
        Assert::assertFalse($this->isContactInCompany($adminContact->getId(), $company->getId()));
    }

    public function testBatchAddContactsGlobalForbiddenWithoutEditPermission(): void
    {
        $salesUser = $this->em->getRepository(User::class)->findOneBy(['username' => self::SALES_USER]);
        Assert::assertInstanceOf(User::class, $salesUser);
        $this->setLeadPermissions($salesUser, ['viewown', 'viewother']);

        $this->client->setServerParameter('PHP_AUTH_USER', self::SALES_USER);
        $this->client->setServerParameter('PHP_AUTH_PW', 'Maut1cR0cks!');

        $this->client->request(Request::METHOD_POST, '/api/companies/batch/addcontacts', [
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

        $this->client->request(Request::METHOD_POST, '/api/companies/batch/addcontacts', [
            'assignments' => $assignments,
        ]);

        Assert::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $this->client->getResponse()->getStatusCode());
    }

    private function createCompany(string $name): Company
    {
        $company = new Company();
        $company->setIsPublished(true);
        $company->setName($name);
        $this->companyModel->saveEntity($company);

        return $company;
    }

    private function createContact(string $email): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $this->leadModel->saveEntity($contact);

        return $contact;
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

    private function isContactInCompany(int $contactId, int $companyId): bool
    {
        return $this->countCompanyLeadRows($contactId, $companyId) > 0;
    }

    private function countCompanyLeadRows(int $contactId, int $companyId): int
    {
        $contact = $this->leadModel->getEntity($contactId);
        $company = $this->companyModel->getEntity($companyId);

        if (null === $contact || null === $company) {
            return 0;
        }

        return null === $this->em->getRepository(CompanyLead::class)->findOneBy([
            'lead'    => $contact,
            'company' => $company,
        ]) ? 0 : 1;
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
