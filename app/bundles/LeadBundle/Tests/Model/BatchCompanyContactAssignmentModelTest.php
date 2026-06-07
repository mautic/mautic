<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Model;

use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\BatchCompanyContactAssignmentModel;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class BatchCompanyContactAssignmentModelTest extends TestCase
{
    /** @var CompanyModel&MockObject */
    private CompanyModel $companyModel;

    /** @var LeadModel&MockObject */
    private LeadModel $leadModel;

    /** @var CorePermissions&MockObject */
    private CorePermissions $security;

    private BatchCompanyContactAssignmentModel $model;

    protected function setUp(): void
    {
        $this->companyModel = $this->createMock(CompanyModel::class);
        $this->leadModel    = $this->createMock(LeadModel::class);
        $this->security     = $this->createMock(CorePermissions::class);

        $this->model = new BatchCompanyContactAssignmentModel(
            $this->companyModel,
            $this->leadModel,
            $this->security
        );
    }

    public function testProcessReturns404ForMissingContact(): void
    {
        $this->leadModel->method('getEntities')->willReturn([]);
        $this->companyModel->method('getEntities')->willReturn([]);

        $payload = $this->model->process([
            ['contactId' => 99, 'companyId' => 1],
        ]);

        Assert::assertSame(1, $payload['summary']['total']);
        Assert::assertSame(0, $payload['summary']['succeeded']);
        Assert::assertSame(1, $payload['summary']['failed']);
        Assert::assertSame(Response::HTTP_NOT_FOUND, $payload['results'][0]['status']);
        Assert::assertSame('Contact not found', $payload['results'][0]['message']);
    }

    public function testProcessReturns404ForMissingCompany(): void
    {
        $contact = $this->createContact(1, 10);

        $this->leadModel->method('getEntities')->willReturn([$contact]);
        $this->companyModel->method('getEntities')->willReturn([]);

        $payload = $this->model->process([
            ['contactId' => 1, 'companyId' => 999],
        ]);

        Assert::assertSame(Response::HTTP_NOT_FOUND, $payload['results'][0]['status']);
        Assert::assertSame('Company not found', $payload['results'][0]['message']);
        Assert::assertSame(1, $payload['summary']['failed']);
    }

    public function testProcessReturns403WhenContactEditDenied(): void
    {
        $contact = $this->createContact(1, 10);
        $company = $this->createCompany(7);

        $this->leadModel->method('getEntities')->willReturn([$contact]);
        $this->companyModel->method('getEntities')->willReturn([$company]);
        $this->security->method('hasEntityAccess')->willReturn(false);

        // Expectation must be set before process() to correctly catch unexpected calls.
        $this->companyModel->expects($this->never())->method('addLeadToCompany');

        $payload = $this->model->process([
            ['contactId' => 1, 'companyId' => 7],
        ]);

        Assert::assertSame(Response::HTTP_FORBIDDEN, $payload['results'][0]['status']);
        Assert::assertSame(1, $payload['summary']['failed']);
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
            ->with([7], $contact);

        $payload = $this->model->process([
            ['contactId' => 1, 'companyId' => 7],
        ]);

        Assert::assertSame(Response::HTTP_OK, $payload['results'][0]['status']);
        Assert::assertSame(1, $payload['summary']['total']);
        Assert::assertSame(1, $payload['summary']['succeeded']);
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
            ->with([7], $contact);

        $payload = $this->model->process([
            ['contactId' => 1, 'companyId' => 7],
            ['contactId' => 1, 'companyId' => 7],
        ]);

        // total reflects the original input count (including duplicates)
        Assert::assertSame(2, $payload['summary']['total']);
        Assert::assertCount(2, $payload['results']);
        Assert::assertSame(Response::HTTP_OK, $payload['results'][0]['status']);
        Assert::assertSame(Response::HTTP_OK, $payload['results'][1]['status']);
        Assert::assertSame(2, $payload['summary']['succeeded']);
        Assert::assertSame(0, $payload['summary']['failed']);
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
            ->with(
                $this->callback(static function (array $companyIds): bool {
                    sort($companyIds);

                    return [7, 11] === $companyIds;
                }),
                $contact
            );

        $payload = $this->model->process([
            ['contactId' => 5, 'companyId' => 7],
            ['contactId' => 5, 'companyId' => 11],
        ]);

        Assert::assertSame(2, $payload['summary']['total']);
        Assert::assertSame(2, $payload['summary']['succeeded']);
    }

    private function createContact(int $id, int $ownerId): Lead
    {
        $owner = $this->createMock(\Mautic\UserBundle\Entity\User::class);
        $owner->method('getId')->willReturn($ownerId);

        $contact = new Lead();
        $contact->setId($id);
        $contact->setOwner($owner);

        return $contact;
    }

    private function createCompany(int $id): Company
    {
        $company = $this->createMock(Company::class);
        $company->method('getId')->willReturn($id);

        return $company;
    }
}
