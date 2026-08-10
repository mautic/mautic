<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\BatchCompanyContactAssignmentModel;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

final class BatchCompanyContactAssignmentModelTest extends TestCase
{
    /** @var CompanyModel&MockObject */
    private MockObject $companyModel;

    /** @var LeadModel&MockObject */
    private MockObject $leadModel;

    /** @var CorePermissions&MockObject */
    private MockObject $security;

    /** @var LeadRepository&MockObject */
    private MockObject $leadRepository;

    private BatchCompanyContactAssignmentModel $model;

    protected function setUp(): void
    {
        $this->companyModel    = $this->createMock(CompanyModel::class);
        $this->leadModel         = $this->createMock(LeadModel::class);
        $this->security          = $this->createMock(CorePermissions::class);
        $this->leadRepository    = $this->createMock(LeadRepository::class);

        $this->model = new BatchCompanyContactAssignmentModel(
            $this->companyModel,
            $this->leadModel,
            $this->security,
            $this->leadRepository,
        );
    }

    public function testProcessReturns404ForMissingContact(): void
    {
        $this->leadModel->method('getEntities')->willReturn([]);
        $this->companyModel->method('getEntities')->willReturn([]);

        $payload = $this->model->process([
            ['contactId' => 99, 'companyId' => 1],
        ]);

        $this->assertSame(1, $payload['summary']['total']);
        $this->assertSame(0, $payload['summary']['succeeded']);
        $this->assertSame(1, $payload['summary']['failed']);
        $this->assertSame(Response::HTTP_NOT_FOUND, $payload['results'][0]['status']);
        $this->assertSame('Contact not found', $payload['results'][0]['message']);
    }

    public function testProcessReturns404ForMissingCompany(): void
    {
        $contact = $this->createContact(1, 10);

        $this->leadModel->method('getEntities')->willReturn([$contact]);
        $this->companyModel->method('getEntities')->willReturn([]);

        $payload = $this->model->process([
            ['contactId' => 1, 'companyId' => 999],
        ]);

        $this->assertSame(Response::HTTP_NOT_FOUND, $payload['results'][0]['status']);
        $this->assertSame('Company not found', $payload['results'][0]['message']);
        $this->assertSame(1, $payload['summary']['failed']);
    }

    public function testProcessReturns403WhenContactEditDenied(): void
    {
        $contact = $this->createContact(1, 10);
        $company = $this->createCompany(7);

        $this->leadModel->method('getEntities')->willReturn([$contact]);
        $this->companyModel->method('getEntities')->willReturn([$company]);
        $this->security->method('hasEntityAccess')->willReturn(false);

        $this->companyModel->expects($this->never())->method('addLeadToCompany');

        $payload = $this->model->process([
            ['contactId' => 1, 'companyId' => 7],
        ]);

        $this->assertSame(Response::HTTP_FORBIDDEN, $payload['results'][0]['status']);
        $this->assertSame(1, $payload['summary']['failed']);
    }

    public function testProcessAssignsAndReturns200ForValidPair(): void
    {
        $contact = $this->createContact(1, 10);
        $company = $this->createCompany(7);

        $this->leadModel->method('getEntities')->willReturn([$contact]);
        $this->companyModel->method('getEntities')->willReturn([$company]);
        $this->security->method('hasEntityAccess')->willReturn(true);
        $this->companyModel->expects($this->once())
            ->method('addLeadToCompany')
            ->with([7], $contact)
            ->willReturn([7]);
        $this->expectLeadRepositorySave($contact);

        $payload = $this->model->process([
            ['contactId' => 1, 'companyId' => 7],
        ]);

        $this->assertSame(Response::HTTP_OK, $payload['results'][0]['status']);
        $this->assertSame(1, $payload['summary']['total']);
        $this->assertSame(1, $payload['summary']['succeeded']);
    }

    public function testProcessDuplicatePairsReturn200TwiceButModelCalledOnce(): void
    {
        $contact = $this->createContact(1, 10);
        $company = $this->createCompany(7);

        $this->leadModel->method('getEntities')->willReturn([$contact]);
        $this->companyModel->method('getEntities')->willReturn([$company]);
        $this->security->method('hasEntityAccess')->willReturn(true);
        $this->companyModel->expects($this->once())
            ->method('addLeadToCompany')
            ->with([7], $contact)
            ->willReturn([7]);
        $this->expectLeadRepositorySave($contact);

        $payload = $this->model->process([
            ['contactId' => 1, 'companyId' => 7],
            ['contactId' => 1, 'companyId' => 7],
        ]);

        $this->assertSame(2, $payload['summary']['total']);
        $this->assertCount(2, $payload['results']);
        $this->assertSame(Response::HTTP_OK, $payload['results'][0]['status']);
        $this->assertSame(Response::HTTP_OK, $payload['results'][1]['status']);
        $this->assertSame(2, $payload['summary']['succeeded']);
        $this->assertSame(0, $payload['summary']['failed']);
    }

    public function testProcessGroupsMultipleCompaniesPerContact(): void
    {
        $contact   = $this->createContact(5, 10);
        $company7  = $this->createCompany(7);
        $company11 = $this->createCompany(11);

        $this->leadModel->method('getEntities')->willReturn([$contact]);
        $this->companyModel->method('getEntities')->willReturn([$company7, $company11]);
        $this->security->method('hasEntityAccess')->willReturn(true);
        $this->companyModel->expects($this->once())
            ->method('addLeadToCompany')
            ->with([7, 11], $contact)
            ->willReturn([7, 11]);
        $this->expectLeadRepositorySave($contact);

        $payload = $this->model->process([
            ['contactId' => 5, 'companyId' => 7],
            ['contactId' => 5, 'companyId' => 11],
        ]);

        $this->assertSame(2, $payload['summary']['total']);
        $this->assertSame(2, $payload['summary']['succeeded']);
    }

    public function testLogContactCompanyAssignmentsForAddedCompanies(): void
    {
        $contact = $this->createContact(1, 10);
        $company = $this->createCompany(7, 'Acme Inc');
        $this->expectLeadRepositorySave($contact);

        $this->model->logContactCompanyAssignments($contact, [7], [7 => $company]);

        $logs = $contact->getCompanyChangeLog();
        $this->assertCount(1, $logs);
        $this->assertSame('api', $logs[0]->getType());
        $this->assertSame('API assignment', $logs[0]->getEventName());
        $this->assertNotSame('API batch assignment', $logs[0]->getEventName());
        $this->assertSame('Lead added to the company, Acme Inc', $logs[0]->getActionName());
        $this->assertSame(7, $logs[0]->getCompany());
    }

    public function testLogContactCompanyAssignmentsDoesNotLogWhenNoCompaniesWereAdded(): void
    {
        $contact = $this->createContact(1, 10);
        $company = $this->createCompany(7, 'Acme Inc');

        $this->leadRepository->expects($this->never())->method('saveEntity');

        $this->model->logContactCompanyAssignments($contact, [], [7 => $company]);

        $this->assertCount(0, $contact->getCompanyChangeLog());
    }

    public function testLogContactCompanyAssignmentsThrowsWhenSaveFails(): void
    {
        $contact = $this->createContact(1, 10);
        $company = $this->createCompany(7, 'Acme Inc');

        $this->leadRepository->expects($this->once())
            ->method('saveEntity')
            ->with($contact)
            ->willThrowException(new \RuntimeException('Logging failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Logging failed');

        $this->model->logContactCompanyAssignments($contact, [7], [7 => $company]);
    }

    public function testProcessLogsBatchAssignmentsForAddedCompanies(): void
    {
        $contact = $this->createContact(1, 10);
        $company = $this->createCompany(7, 'Acme Inc');

        $this->leadModel->method('getEntities')->willReturn([$contact]);
        $this->companyModel->method('getEntities')->willReturn([$company]);
        $this->security->method('hasEntityAccess')->willReturn(true);
        $this->companyModel->method('addLeadToCompany')->willReturn([7]);
        $this->expectLeadRepositorySave($contact);

        $this->model->process([
            ['contactId' => 1, 'companyId' => 7],
        ]);

        $logs = $contact->getCompanyChangeLog();
        $this->assertCount(1, $logs);
        $this->assertSame('api', $logs[0]->getType());
        $this->assertSame('API batch assignment', $logs[0]->getEventName());
        $this->assertSame('Lead added to the company, Acme Inc', $logs[0]->getActionName());
        $this->assertSame(7, $logs[0]->getCompany());
    }

    public function testProcessDoesNotLogWhenNoCompaniesWereAdded(): void
    {
        $contact = $this->createContact(1, 10);
        $company = $this->createCompany(7);

        $this->leadModel->method('getEntities')->willReturn([$contact]);
        $this->companyModel->method('getEntities')->willReturn([$company]);
        $this->security->method('hasEntityAccess')->willReturn(true);
        $this->companyModel->method('addLeadToCompany')->willReturn([]);
        $this->leadRepository->expects($this->never())->method('saveEntity');

        $this->model->process([
            ['contactId' => 1, 'companyId' => 7],
        ]);

        $this->assertCount(0, $contact->getCompanyChangeLog());
    }

    public function testProcessReturns200WhenLoggingFails(): void
    {
        $contact = $this->createContact(1, 10);
        $company = $this->createCompany(7);

        $this->leadModel->method('getEntities')->willReturn([$contact]);
        $this->companyModel->method('getEntities')->willReturn([$company]);
        $this->security->method('hasEntityAccess')->willReturn(true);
        $this->companyModel->method('addLeadToCompany')->willReturn([7]);

        $this->leadRepository->expects($this->once())
            ->method('saveEntity')
            ->with($contact)
            ->willThrowException(new \RuntimeException('Logging failed'));

        $payload = $this->model->process([
            ['contactId' => 1, 'companyId' => 7],
        ]);

        $this->assertSame(Response::HTTP_OK, $payload['results'][0]['status']);
        $this->assertSame(1, $payload['summary']['succeeded']);
        $this->assertSame(0, $payload['summary']['failed']);
    }

    private function expectLeadRepositorySave(Lead $contact): void
    {
        $this->leadRepository->expects($this->once())->method('saveEntity')->with($contact);
    }

    private function createContact(int $id, int $ownerId): Lead
    {
        $owner = $this->createMock(User::class);
        $owner->method('getId')->willReturn($ownerId);

        $contact = new Lead();
        $contact->setId($id);
        $contact->setOwner($owner);

        return $contact;
    }

    private function createCompany(int $id, string $name = 'Test Company'): Company
    {
        $company = $this->createMock(Company::class);
        $company->method('getId')->willReturn($id);
        $company->method('getName')->willReturn($name);

        return $company;
    }
}
