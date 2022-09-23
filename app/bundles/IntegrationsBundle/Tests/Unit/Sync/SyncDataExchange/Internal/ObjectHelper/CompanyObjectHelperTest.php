<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\ObjectHelper;

use Doctrine\DBAL\Connection;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class CompanyObjectHelperTest extends TestCase
{
    /**
     * @var CompanyModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $model;

    /**
     * @var CompanyRepository|\PHPUnit\Framework\MockObject\MockObject
     */
    private $repository;

    /**
     * @var Connection|\PHPUnit\Framework\MockObject\MockObject
     */
    private $connection;

    protected function setUp(): void
    {
        $this->model      = $this->createMock(CompanyModel::class);
        $this->repository = $this->createMock(CompanyRepository::class);
        $this->connection = $this->createMock(Connection::class);
    }

    public function testCreate(): void
    {
        $this->model->expects($this->exactly(2))
            ->method('saveEntity');
        $this->repository->expects($this->exactly(2))
            ->method('detachEntity');

        $objects = [
            new ObjectChangeDAO('Test', MauticSyncDataExchange::OBJECT_COMPANY, null, 'MappedObject', 1, new \DateTime()),
            new ObjectChangeDAO('Test', MauticSyncDataExchange::OBJECT_COMPANY, null, 'MappedObject', 2, new \DateTime()),
        ];

        $objectMappings = $this->getObjectHelper()->create($objects);

        foreach ($objectMappings as $key => $objectMapping) {
            $this->assertEquals('Test', $objectMapping->getIntegration());
            $this->assertEquals(MauticSyncDataExchange::OBJECT_COMPANY, $objectMapping->getInternalObjectName());
            $this->assertEquals('MappedObject', $objectMapping->getIntegrationObjectName());
            $this->assertEquals($objects[$key]->getMappedObjectId(), $objectMapping->getIntegrationObjectId());
        }
    }

    public function testUpdate(): void
    {
        $this->model->expects($this->exactly(2))
            ->method('saveEntity');
        $this->repository->expects($this->exactly(2))
            ->method('detachEntity');

        $objects = [
            0 => new ObjectChangeDAO('Test', MauticSyncDataExchange::OBJECT_COMPANY, 0, 'MappedObject', 0, new \DateTime()),
            1 => new ObjectChangeDAO('Test', MauticSyncDataExchange::OBJECT_COMPANY, 1, 'MappedObject', 1, new \DateTime()),
        ];

        $company1 = $this->createMock(Company::class);
        $company1->method('getId')
            ->willReturn(0);
        $company2 = $this->createMock(Company::class);
        $company2->method('getId')
            ->willReturn(1);
        $this->model->expects($this->once())
            ->method('getEntities')
            ->willReturn(
                [
                    $company1,
                    $company2,
                ]
            );
        $objectMappings = $this->getObjectHelper()->update([3, 4], $objects);

        foreach ($objectMappings as $objectMapping) {
            $this->assertEquals('Test', $objectMapping->getIntegration());
            $this->assertEquals('MappedObject', $objectMapping->getIntegrationObjectName());
            $this->assertTrue(isset($objects[$objectMapping->getIntegrationObjectId()]));
            $this->assertEquals($objects[$objectMapping->getIntegrationObjectId()]->getMappedObjectId(), $objectMapping->getIntegrationObjectId());
        }
    }

    public function testUpdateEmpty(): void
    {
        $this->model->expects($this->never())
            ->method('getEntities');

        $objectMappings = $this->getObjectHelper()->update([], []);

        Assert::assertSame([], $objectMappings);
    }

    /**
     * @return CompanyObjectHelper
     */
    private function getObjectHelper()
    {
        return new CompanyObjectHelper($this->model, $this->repository, $this->connection);
    }
}
