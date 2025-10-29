<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Model;

use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\CoreBundle\Tests\Functional\CreateTestEntitiesTrait;
use Symfony\Component\DependencyInjection\Container;

class AuditLogModelTest extends MauticMysqlTestCase
{
    use CreateTestEntitiesTrait;

    private Container $container;
    private AuditLogModel $model;
    private AuditLogRepository $repo;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->container = static::getContainer();
        // Service id used in project convention
        $this->model = $this->container->get('mautic.core.model.auditlog');
        $this->repo  = $this->model->getRepository();
    }

    public function testWriteToLogCreatesEntry(): void
    {
        $objectId = 1;
        $args     = [
            'bundle'    => 'test',
            'object'    => 'object',
            'objectId'  => $objectId,
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ];

        $this->model->writeToLog($args);

        $logs = $this->repo->findBy([
            'objectId' => $objectId,
            'bundle'   => 'test',
            'object'   => 'object',
        ]);

        $this->assertNotEmpty($logs, 'Audit log entry should be created by writeToLog.');

        // cleanup
        $this->repo->deleteEntities($logs);
    }

    public function testGetLogForObjectReturnsEntries(): void
    {
        $objectId = 2;
        $args     = [
            'bundle'    => 'test',
            'object'    => 'object',
            'objectId'  => $objectId,
            'action'    => 'update',
            'ipAddress' => '127.0.0.1',
        ];

        $this->model->writeToLog($args);

        $result = $this->model->getLogForObject('object', $objectId, null, 10, 'test');

        $this->assertIsArray($result, 'getLogForObject should return an array or collection.');
        $this->assertNotEmpty($result, 'getLogForObject should return the previously written audit log.');

        // cleanup: repository may return AuditLog entities; remove them
        $logs = $this->repo->findBy([
            'objectId' => $objectId,
            'bundle'   => 'test',
            'object'   => 'object',
        ]);
        $this->repo->deleteEntities($logs);
    }

    public function testDeleteAuditLogByLeadsDeletesLeadLogs(): void
    {
        // Create a test lead
        $lead = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $this->em->flush();

        // Create audit logs for the lead
        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'lead',
            'objectId'  => $lead->getId(),
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ]);

        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'lead',
            'objectId'  => $lead->getId(),
            'action'    => 'update',
            'ipAddress' => '127.0.0.1',
        ]);

        // Verify logs were created
        $logsBefore = $this->repo->findBy([
            'objectId' => $lead->getId(),
            'bundle'   => 'lead',
            'object'   => 'lead',
        ]);
        $this->assertCount(2, $logsBefore, 'Two audit logs should exist before deletion.');

        // Delete audit logs
        $this->model->deleteAuditLogByLeads([$lead->getId()], false);

        // Verify logs were deleted
        $logsAfter = $this->repo->findBy([
            'objectId' => $lead->getId(),
            'bundle'   => 'lead',
            'object'   => 'lead',
        ]);
        $this->assertEmpty($logsAfter, 'Audit logs should be deleted.');

        // Cleanup
        $this->em->remove($lead);
        $this->em->flush();
    }

    public function testDeleteAuditLogByLeadsWithMultipleLeads(): void
    {
        // Create multiple test leads
        $lead1 = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $lead2 = $this->createLead('Jane', 'Smith', 'jane.smith@example.com');
        $this->em->flush();

        // Create audit logs for both leads
        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'lead',
            'objectId'  => $lead1->getId(),
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ]);

        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'lead',
            'objectId'  => $lead2->getId(),
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ]);

        // Verify logs were created
        $logs1Before = $this->repo->findBy([
            'objectId' => $lead1->getId(),
            'bundle'   => 'lead',
            'object'   => 'lead',
        ]);
        $logs2Before = $this->repo->findBy([
            'objectId' => $lead2->getId(),
            'bundle'   => 'lead',
            'object'   => 'lead',
        ]);
        $this->assertCount(1, $logs1Before);
        $this->assertCount(1, $logs2Before);

        // Delete audit logs for both leads
        $this->model->deleteAuditLogByLeads([$lead1->getId(), $lead2->getId()], false);

        // Verify all logs were deleted
        $logs1After = $this->repo->findBy([
            'objectId' => $lead1->getId(),
            'bundle'   => 'lead',
            'object'   => 'lead',
        ]);
        $logs2After = $this->repo->findBy([
            'objectId' => $lead2->getId(),
            'bundle'   => 'lead',
            'object'   => 'lead',
        ]);
        $this->assertEmpty($logs1After, 'Lead 1 audit logs should be deleted.');
        $this->assertEmpty($logs2After, 'Lead 2 audit logs should be deleted.');

        // Cleanup
        $this->em->remove($lead1);
        $this->em->remove($lead2);
        $this->em->flush();
    }

    public function testDeleteAuditLogByLeadsWithoutDeletingCompanyLogs(): void
    {
        // Create test lead and company
        $lead    = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $company = $this->createCompany('Test Company', 'company@example.com');
        $this->createPrimaryCompanyForLead($lead, $company);
        $this->em->flush();

        // Create audit logs for lead and company
        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'lead',
            'objectId'  => $lead->getId(),
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ]);

        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'company',
            'objectId'  => $company->getId(),
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ]);

        // Delete audit logs for lead only (deleteCompaniesByLeads = false)
        $this->model->deleteAuditLogByLeads([$lead->getId()], false);

        // Verify lead logs were deleted
        $leadLogsAfter = $this->repo->findBy([
            'objectId' => $lead->getId(),
            'bundle'   => 'lead',
            'object'   => 'lead',
        ]);
        $this->assertEmpty($leadLogsAfter, 'Lead audit logs should be deleted.');

        // Verify company logs were NOT deleted
        $companyLogsAfter = $this->repo->findBy([
            'objectId' => $company->getId(),
            'bundle'   => 'lead',
            'object'   => 'company',
        ]);
        $this->assertCount(1, $companyLogsAfter, 'Company audit logs should NOT be deleted.');

        // Cleanup
        $this->repo->deleteEntities($companyLogsAfter);
        $this->em->remove($lead);
        $this->em->remove($company);
        $this->em->flush();
    }

    public function testDeleteAuditLogByLeadsWithDeletingCompanyLogs(): void
    {
        // Create test lead and company
        $lead    = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $company = $this->createCompany('Test Company', 'company@example.com');
        $this->createPrimaryCompanyForLead($lead, $company);
        $this->em->flush();

        // Create audit logs for lead and company
        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'lead',
            'objectId'  => $lead->getId(),
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ]);

        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'company',
            'objectId'  => $company->getId(),
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ]);

        // Delete audit logs for lead AND company (deleteCompaniesByLeads = true)
        $this->model->deleteAuditLogByLeads([$lead->getId()], true);

        // Verify lead logs were deleted
        $leadLogsAfter = $this->repo->findBy([
            'objectId' => $lead->getId(),
            'bundle'   => 'lead',
            'object'   => 'lead',
        ]);
        $this->assertEmpty($leadLogsAfter, 'Lead audit logs should be deleted.');

        // Verify company logs were also deleted
        $companyLogsAfter = $this->repo->findBy([
            'objectId' => $company->getId(),
            'bundle'   => 'lead',
            'object'   => 'company',
        ]);
        $this->assertEmpty($companyLogsAfter, 'Company audit logs should be deleted.');

        // Cleanup
        $this->em->remove($lead);
        $this->em->remove($company);
        $this->em->flush();
    }

    public function testDeleteAuditLogByLeadsWithMultipleCompanies(): void
    {
        // Create test lead with multiple companies
        $lead     = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $company1 = $this->createCompany('Test Company 1', 'company1@example.com');
        $company2 = $this->createCompany('Test Company 2', 'company2@example.com');
        $this->createPrimaryCompanyForLead($lead, $company1, true);
        $this->createPrimaryCompanyForLead($lead, $company2, false);
        $this->em->flush();

        // Create audit logs
        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'lead',
            'objectId'  => $lead->getId(),
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ]);

        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'company',
            'objectId'  => $company1->getId(),
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ]);

        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'company',
            'objectId'  => $company2->getId(),
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ]);

        // Delete audit logs including companies
        $this->model->deleteAuditLogByLeads([$lead->getId()], true);

        // Verify all logs were deleted
        $leadLogsAfter = $this->repo->findBy([
            'objectId' => $lead->getId(),
            'bundle'   => 'lead',
            'object'   => 'lead',
        ]);
        $company1LogsAfter = $this->repo->findBy([
            'objectId' => $company1->getId(),
            'bundle'   => 'lead',
            'object'   => 'company',
        ]);
        $company2LogsAfter = $this->repo->findBy([
            'objectId' => $company2->getId(),
            'bundle'   => 'lead',
            'object'   => 'company',
        ]);

        $this->assertEmpty($leadLogsAfter, 'Lead audit logs should be deleted.');
        $this->assertEmpty($company1LogsAfter, 'Company 1 audit logs should be deleted.');
        $this->assertEmpty($company2LogsAfter, 'Company 2 audit logs should be deleted.');

        // Cleanup
        $this->em->remove($lead);
        $this->em->remove($company1);
        $this->em->remove($company2);
        $this->em->flush();
    }

    public function testDeleteAuditLogByLeadsWithEmptyArray(): void
    {
        // Should not throw exception with empty array
        $this->model->deleteAuditLogByLeads([], false);
        $this->model->deleteAuditLogByLeads([], true);

        // No assertions needed - just verifying no exceptions are thrown
        $this->assertTrue(true);
    }

    public function testDeleteAuditLogByLeadsWithNonExistentLeadId(): void
    {
        // Should handle non-existent lead IDs gracefully
        $nonExistentId = 999999;

        // This should not throw an exception
        $this->model->deleteAuditLogByLeads([$nonExistentId], false);
        $this->model->deleteAuditLogByLeads([$nonExistentId], true);

        // No assertions needed - just verifying no exceptions are thrown
        $this->assertTrue(true);
    }

    public function testDeleteAuditLogByLeadsOnlyDeletesLeadBundleLogs(): void
    {
        // Create a test lead
        $lead = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $this->em->flush();

        // Create audit logs for lead in 'lead' bundle
        $this->model->writeToLog([
            'bundle'    => 'lead',
            'object'    => 'lead',
            'objectId'  => $lead->getId(),
            'action'    => 'create',
            'ipAddress' => '127.0.0.1',
        ]);

        // Create audit logs for lead in different bundle (should NOT be deleted)
        $this->model->writeToLog([
            'bundle'    => 'email',
            'object'    => 'email_stat',
            'objectId'  => $lead->getId(),
            'action'    => 'sent',
            'ipAddress' => '127.0.0.1',
        ]);

        // Delete audit logs
        $this->model->deleteAuditLogByLeads([$lead->getId()], false);

        // Verify lead bundle logs were deleted
        $leadBundleLogs = $this->repo->findBy([
            'objectId' => $lead->getId(),
            'bundle'   => 'lead',
            'object'   => 'lead',
        ]);
        $this->assertEmpty($leadBundleLogs, 'Lead bundle audit logs should be deleted.');

        // Verify other bundle logs were NOT deleted
        $emailBundleLogs = $this->repo->findBy([
            'objectId' => $lead->getId(),
            'bundle'   => 'email',
            'object'   => 'email_stat',
        ]);
        $this->assertCount(1, $emailBundleLogs, 'Other bundle audit logs should NOT be deleted.');

        // Cleanup
        $this->repo->deleteEntities($emailBundleLogs);
        $this->em->remove($lead);
        $this->em->flush();
    }
}
