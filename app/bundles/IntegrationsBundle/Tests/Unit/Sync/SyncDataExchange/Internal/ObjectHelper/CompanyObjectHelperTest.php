<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\ObjectHelper;

use Doctrine\DBAL\Connection;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class CompanyObjectHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CompanyModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $model;

    /**
     * @var CompanyRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var Connection|\PHPUnit_Framework_MockObject_MockObject
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

    /**
     * @return CompanyObjectHelper
     */
    private function getObjectHelper()
    {
        return new CompanyObjectHelper($this->model, $this->repository, $this->connection);
    }
}
