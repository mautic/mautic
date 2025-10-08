<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Model;

use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class AuditLogModelTest extends MauticMysqlTestCase
{
    private $container;
    private AuditLogModel $model;
    private $repo;

    protected function setUp(): void
    {
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
}
