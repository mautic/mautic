<?php
// php

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
        $this->model      = $this->container->get('mautic.core.model.auditlog');
        $this->repo       = $this->model->getRepository();
    }

    // Helpers to reduce duplication
    private function addAuditLog(string $bundle, string $object, int $objectId, string $action = 'create'): void
    {
        $this->model->writeToLog([
            'bundle'    => $bundle,
            'object'    => $object,
            'objectId'  => $objectId,
            'action'    => $action,
            'ipAddress' => '127.0.0.1',
        ]);
    }

    private function assertLogsCount(string $bundle, string $object, int $objectId, int $expected, string $message = ''): void
    {
        $logs = $this->repo->findBy([
            'objectId' => $objectId,
            'bundle'   => $bundle,
            'object'   => $object,
        ]);
        $this->assertCount($expected, $logs, $message);
    }

    private function assertLogsEmpty(string $bundle, string $object, int $objectId, string $message = ''): void
    {
        $logs = $this->repo->findBy([
            'objectId' => $objectId,
            'bundle'   => $bundle,
            'object'   => $object,
        ]);
        $this->assertEmpty($logs, $message);
    }

    private function cleanupLogsByCriteria(array $criteria): void
    {
        $logs = $this->repo->findBy($criteria);
        if (!empty($logs)) {
            $this->repo->deleteEntities($logs);
        }
    }

    private function removeAndFlushEntities(...$entities): void
    {
        foreach ($entities as $e) {
            $this->em->remove($e);
        }
        $this->em->flush();
    }

    public function testWriteToLogCreatesEntry(): void
    {
        $objectId = 1;
        $this->addAuditLog('test', 'object', $objectId, 'create');

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
        $this->addAuditLog('test', 'object', $objectId, 'update');

        $result = $this->model->getLogForObject('object', $objectId, null, 10, 'test');

        $this->assertIsArray($result, 'getLogForObject should return an array or collection.');
        $this->assertNotEmpty($result, 'getLogForObject should return the previously written audit log.');

        // cleanup
        $this->cleanupLogsByCriteria([
            'objectId' => $objectId,
            'bundle'   => 'test',
            'object'   => 'object',
        ]);
    }

    public function testDeleteAuditLogByLeadsDeletesLeadLogs(): void
    {
        $lead = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $this->em->flush();

        $this->addAuditLog('lead', 'lead', $lead->getId(), 'create');
        $this->addAuditLog('lead', 'lead', $lead->getId(), 'update');

        $this->assertLogsCount('lead', 'lead', $lead->getId(), 2, 'Two audit logs should exist before deletion.');

        $this->model->deleteAuditLogByLeads([$lead->getId()], false);

        $this->assertLogsEmpty('lead', 'lead', $lead->getId(), 'Audit logs should be deleted.');

        // Cleanup
        $this->removeAndFlushEntities($lead);
    }

    public function testDeleteAuditLogByLeadsWithMultipleLeads(): void
    {
        $lead1 = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $lead2 = $this->createLead('Jane', 'Smith', 'jane.smith@example.com');
        $this->em->flush();

        $this->addAuditLog('lead', 'lead', $lead1->getId(), 'create');
        $this->addAuditLog('lead', 'lead', $lead2->getId(), 'create');

        $this->assertLogsCount('lead', 'lead', $lead1->getId(), 1);
        $this->assertLogsCount('lead', 'lead', $lead2->getId(), 1);

        $this->model->deleteAuditLogByLeads([$lead1->getId(), $lead2->getId()], false);

        $this->assertLogsEmpty('lead', 'lead', $lead1->getId(), 'Lead 1 audit logs should be deleted.');
        $this->assertLogsEmpty('lead', 'lead', $lead2->getId(), 'Lead 2 audit logs should be deleted.');

        $this->removeAndFlushEntities($lead1, $lead2);
    }

    public function testDeleteAuditLogByLeadsWithoutDeletingCompanyLogs(): void
    {
        $lead    = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $company = $this->createCompany('Test Company', 'company@example.com');
        $this->createPrimaryCompanyForLead($lead, $company);
        $this->em->flush();

        $this->addAuditLog('lead', 'lead', $lead->getId(), 'create');
        $this->addAuditLog('lead', 'company', $company->getId(), 'create');

        $this->model->deleteAuditLogByLeads([$lead->getId()], false);

        $this->assertLogsEmpty('lead', 'lead', $lead->getId(), 'Lead audit logs should be deleted.');
        $this->assertLogsCount('lead', 'company', $company->getId(), 1, 'Company audit logs should NOT be deleted.');

        // Cleanup
        $this->cleanupLogsByCriteria([
            'objectId' => $company->getId(),
            'bundle'   => 'lead',
            'object'   => 'company',
        ]);
        $this->removeAndFlushEntities($lead, $company);
    }

    public function testDeleteAuditLogByLeadsWithDeletingCompanyLogs(): void
    {
        $lead    = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $company = $this->createCompany('Test Company', 'company@example.com');
        $this->createPrimaryCompanyForLead($lead, $company);
        $this->em->flush();

        $this->addAuditLog('lead', 'lead', $lead->getId(), 'create');
        $this->addAuditLog('lead', 'company', $company->getId(), 'create');

        $this->model->deleteAuditLogByLeads([$lead->getId()], true);

        $this->assertLogsEmpty('lead', 'lead', $lead->getId(), 'Lead audit logs should be deleted.');
        $this->assertLogsEmpty('lead', 'company', $company->getId(), 'Company audit logs should be deleted.');

        $this->removeAndFlushEntities($lead, $company);
    }

    public function testDeleteAuditLogByLeadsWithMultipleCompanies(): void
    {
        $lead     = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $company1 = $this->createCompany('Test Company 1', 'company1@example.com');
        $company2 = $this->createCompany('Test Company 2', 'company2@example.com');
        $this->createPrimaryCompanyForLead($lead, $company1, true);
        $this->createPrimaryCompanyForLead($lead, $company2, false);
        $this->em->flush();

        $this->addAuditLog('lead', 'lead', $lead->getId(), 'create');
        $this->addAuditLog('lead', 'company', $company1->getId(), 'create');
        $this->addAuditLog('lead', 'company', $company2->getId(), 'create');

        $this->model->deleteAuditLogByLeads([$lead->getId()], true);

        $this->assertLogsEmpty('lead', 'lead', $lead->getId(), 'Lead audit logs should be deleted.');
        $this->assertLogsEmpty('lead', 'company', $company1->getId(), 'Company 1 audit logs should be deleted.');
        $this->assertLogsEmpty('lead', 'company', $company2->getId(), 'Company 2 audit logs should be deleted.');

        $this->removeAndFlushEntities($lead, $company1, $company2);
    }

    public function testDeleteAuditLogByLeadsWithEmptyArray(): void
    {
        // Should not throw exception with empty array
        $this->model->deleteAuditLogByLeads([], false);
        $this->model->deleteAuditLogByLeads([], true);
    }

    public function testDeleteAuditLogByLeadsWithNonExistentLeadId(): void
    {
        $nonExistentId = 999999;
        $this->model->deleteAuditLogByLeads([$nonExistentId], false);
        $this->model->deleteAuditLogByLeads([$nonExistentId], true);
    }

    public function testDeleteAuditLogByLeadsOnlyDeletesLeadBundleLogs(): void
    {
        $lead = $this->createLead('John', 'Doe', 'john.doe@example.com');
        $this->em->flush();

        $this->addAuditLog('lead', 'lead', $lead->getId(), 'create');
        $this->addAuditLog('email', 'email_stat', $lead->getId(), 'sent');

        $this->model->deleteAuditLogByLeads([$lead->getId()], false);

        $this->assertLogsEmpty('lead', 'lead', $lead->getId(), 'Lead bundle audit logs should be deleted.');

        $emailBundleLogs = $this->repo->findBy([
            'objectId' => $lead->getId(),
            'bundle'   => 'email',
            'object'   => 'email_stat',
        ]);
        $this->assertCount(1, $emailBundleLogs, 'Other bundle audit logs should NOT be deleted.');

        // Cleanup
        $this->repo->deleteEntities($emailBundleLogs);
        $this->removeAndFlushEntities($lead);
    }
}
