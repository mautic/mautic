<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\SyncDataExchange\Internal\ObjectHelper;

use Doctrine\DBAL\Connection;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Company as CompanyObject;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Field\FieldsWithUniqueIdentifier;
use Mautic\LeadBundle\Model\CompanyModel;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CompanyObjectHelperTest extends TestCase
{
    /**
     * @var CompanyModel&MockObject
     */
    private MockObject $model;

    /**
     * @var CompanyRepository&MockObject
     */
    private MockObject $repository;

    /**
     * @var Connection&MockObject
     */
    private MockObject $connection;

    /**
     * @var FieldsWithUniqueIdentifier&MockObject
     */
    private MockObject $fieldsWithUniqueIdentifier;

    protected function setUp(): void
    {
        $this->model                      = $this->createMock(CompanyModel::class);
        $this->repository                 = $this->createMock(CompanyRepository::class);
        $this->connection                 = $this->createMock(Connection::class);
        $this->fieldsWithUniqueIdentifier = $this->createMock(FieldsWithUniqueIdentifier::class);

        $this->fieldsWithUniqueIdentifier->method('getFieldsWithUniqueIdentifier')
            ->with(['object' => CompanyObject::NAME])
            ->willReturn(
                [
                    'companyemail' => [],
                ]
            );
    }

    public function testCreateWithDuplicateUniqueIdentifiers(): void
    {
        $idMap = [
            'email1@email.com' => 127,
            'email2@email.com' => 128,
        ];

        $this->model->expects($this->exactly(3))
            ->method('saveEntity')
            ->with(
                $this->callback(function (Company $company) use ($idMap): bool {
                    // Set ID
                    $reflection = new \ReflectionClass($company);
                    $property   = $reflection->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($company, $idMap[$company->getEmail()]);

                    return true;
                })
            );

        $this->repository->expects($this->exactly(2))
            ->method('detachEntity');

        // Test that two objects with the same unique identifier are merged into one
        $object1 = $this->getObject(1, ['companyemail' => 'email1@email.com']);
        $object2 = $this->getObject(2, ['companyemail' => 'email2@email.com']);
        $object3 = $this->getObject(3, ['companyemail' => 'email1@email.com']);

        $objects = [$object1, $object2, $object3];

        $objectMappings = $this->getObjectHelper()->create($objects);

        foreach ($objectMappings as $key => $objectMapping) {
            $this->assertEquals('Test', $objectMapping->getIntegration());
            $this->assertEquals(CompanyObject::NAME, $objectMapping->getInternalObjectName());
            $this->assertEquals('MappedObject', $objectMapping->getIntegrationObjectName());
            $this->assertEquals($objects[$key]->getMappedObjectId(), $objectMapping->getIntegrationObjectId());

            // Test that mapped ID matches internal ID
            switch ($objects[$key]->getMappedObjectId()) {
                case 1:
                case 3:
                    Assert::assertSame(127, $objectMapping->getInternalObjectId());
                    break;
                case 2:
                    Assert::assertSame(128, $objectMapping->getInternalObjectId());
                    break;
            }
        }
    }

    public function testCreateWithOneWithoutUniqueIdentifier(): void
    {
        $idMap = [
            'email1@email.com' => 127,
            'email2@email.com' => 128,
            ''                 => 129,
        ];

        $this->model->expects($this->exactly(4))
            ->method('saveEntity')
            ->with(
                $this->callback(function (Company $company) use ($idMap): bool {
                    // Set ID
                    $reflection = new \ReflectionClass($company);
                    $property   = $reflection->getProperty('id');
                    $property->setAccessible(true);
                    $property->setValue($company, $idMap[$company->getEmail()]);

                    return true;
                })
            );

        $this->repository->expects($this->exactly(3))
            ->method('detachEntity');

        // Test that two objects with the same unique identifier are merged into one
        $object1 = $this->getObject(1, ['companyemail' => 'email1@email.com']);
        $object2 = $this->getObject(2, ['companyemail' => 'email2@email.com']);
        $object3 = $this->getObject(3, ['companyemail' => 'email1@email.com']);
        $object4 = $this->getObject(4, ['companyname' => 'Some Biz']);

        $objects = [$object1, $object2, $object3, $object4];

        $objectMappings = $this->getObjectHelper()->create($objects);

        foreach ($objectMappings as $key => $objectMapping) {
            $this->assertEquals('Test', $objectMapping->getIntegration());
            $this->assertEquals(CompanyObject::NAME, $objectMapping->getInternalObjectName());
            $this->assertEquals('MappedObject', $objectMapping->getIntegrationObjectName());
            $this->assertEquals($objects[$key]->getMappedObjectId(), $objectMapping->getIntegrationObjectId());

            // Test that mapped ID matches internal ID
            switch ($objects[$key]->getMappedObjectId()) {
                case 1:
                case 3:
                    Assert::assertSame(127, $objectMapping->getInternalObjectId());
                    break;
                case 2:
                    Assert::assertSame(128, $objectMapping->getInternalObjectId());
                    break;
                case 4:
                    Assert::assertSame(129, $objectMapping->getInternalObjectId());
                    break;
            }
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

    public function testFindObjectById(): void
    {
        $company = new Company();
        $this->repository->expects(self::once())
            ->method('getEntity')
            ->with(1)
            ->willReturn($company);

        self::assertSame($company, $this->getObjectHelper()->findObjectById(1));
    }

    public function testFindObjectByIdReturnsNull(): void
    {
        $this->repository->expects(self::once())
            ->method('getEntity')
            ->with(1);

        self::assertNull($this->getObjectHelper()->findObjectById(1));
    }

    public function testSetFieldValues(): void
    {
        $company = new Company();
        $this->model->expects(self::once())
            ->method('setFieldValues')
            ->with($company, []);
        $this->getObjectHelper()->setFieldValues($company);
    }

    public function testUpdateEmpty(): void
    {
        $this->model->expects($this->never())
            ->method('getEntities');

        $objectMappings = $this->getObjectHelper()->update([], []);

        Assert::assertSame([], $objectMappings);
    }

    private function getObjectHelper(): CompanyObjectHelper
    {
        return new CompanyObjectHelper($this->model, $this->repository, $this->connection, $this->fieldsWithUniqueIdentifier);
    }

    /**
     * @param array<string,string> $fieldValues
     */
    private function getObject(int $mappedId, array $fieldValues): ObjectChangeDAO
    {
        $object = new ObjectChangeDAO(
            'Test',
            CompanyObject::NAME,
            null,
            'MappedObject',
            $mappedId,
            new \DateTime()
        );

        foreach ($fieldValues as $name => $value) {
            $object->addField(
                new FieldDAO($name, new NormalizedValueDAO('string', $value))
            );
        }

        return $object;
    }
}
