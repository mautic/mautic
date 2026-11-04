<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\RemappedObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderResultsDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use PHPUnit\Framework\TestCase;

final class OrderResultsDAOTest extends TestCase
{
    public function testObjectsOrganizedByObjectName(): void
    {
        $newObjectMapping1 = new ObjectMapping();
        $newObjectMapping1->setIntegrationObjectName('foo');
        $newObjectMapping1->setIntegrationObjectId('abc');
        $newObjectMapping2 = new ObjectMapping();
        $newObjectMapping2->setIntegrationObjectName('bar');
        $newObjectMapping2->setIntegrationObjectId('efg');
        $newObjectMappings = [$newObjectMapping1, $newObjectMapping2];

        $updatedObjectMapping1 = new ObjectMapping();
        $updatedObjectMapping1->setIntegrationObjectName('foo');
        $updatedObjectMapping1->setIntegrationObjectId('hij');
        $updatedObjectMapping2 = new ObjectMapping();
        $updatedObjectMapping2->setIntegrationObjectName('bar');
        $updatedObjectMapping1->setIntegrationObjectId('klm');
        $updatedObjectMappings = [$updatedObjectMapping1, $updatedObjectMapping2];

        $remappedObjects = [
            new RemappedObjectDAO('foobar', 'oldfoo', 'oldfoo1', 'foo', 'foo1'),
            new RemappedObjectDAO('foobar', 'oldbar', 'oldbar1', 'bar', 'bar1'),
        ];

        $deletedObjects = [
            new ObjectChangeDAO('foobar', 'foo', 'foo1', 'contact', 1),
            new ObjectChangeDAO('foobar', 'bar', 'bar1', 'company', 1),
        ];

        $orderResults = new OrderResultsDAO($newObjectMappings, $updatedObjectMappings, $remappedObjects, $deletedObjects);

        $fooNewObjectMappings = $orderResults->getNewObjectMappings('foo');
        $this->assertCount(1, $fooNewObjectMappings);
        $this->assertEquals('abc', $fooNewObjectMappings[0]->getIntegrationObjectId());

        $barNewObjectMappings = $orderResults->getNewObjectMappings('bar');
        $this->assertCount(1, $barNewObjectMappings);
        $this->assertEquals('efg', $barNewObjectMappings[0]->getIntegrationObjectId());

        $fooRemappedObjects = $orderResults->getRemappedObjects('foo');
        $this->assertCount(1, $fooRemappedObjects);
        $this->assertEquals('foo1', $fooRemappedObjects[0]->getNewObjectId());

        $barRemappedObjects = $orderResults->getRemappedObjects('bar');
        $this->assertCount(1, $barRemappedObjects);
        $this->assertEquals('bar1', $barRemappedObjects[0]->getNewObjectId());

        $fooDeletedObjects = $orderResults->getDeletedObjects('foo');
        $this->assertCount(1, $fooDeletedObjects);
        $this->assertEquals('foo1', $fooDeletedObjects[0]->getObjectId());

        $barDeletedObjects = $orderResults->getDeletedObjects('bar');
        $this->assertCount(1, $barDeletedObjects);
        $this->assertEquals('bar1', $barDeletedObjects[0]->getObjectId());
    }

    public function testExceptionThrownIfObjectNotFoundForNewObjectMappings(): void
    {
        $this->expectException(ObjectNotFoundException::class);

        $orderResults = new OrderResultsDAO([], [], [], []);
        $orderResults->getNewObjectMappings('foo');
    }

    public function testExceptionThrownIfObjectNotFoundForUpdatedObjectMappings(): void
    {
        $this->expectException(ObjectNotFoundException::class);

        $orderResults = new OrderResultsDAO([], [], [], []);
        $orderResults->getUpdatedObjectMappings('foo');
    }

    public function testExceptionThrownIfObjectNotFoundForRemappedObjects(): void
    {
        $this->expectException(ObjectNotFoundException::class);

        $orderResults = new OrderResultsDAO([], [], [], []);
        $orderResults->getRemappedObjects('foo');
    }

    public function testExceptionThrownIfObjectNotFoundForDeletedObjects(): void
    {
        $this->expectException(ObjectNotFoundException::class);

        $orderResults = new OrderResultsDAO([], [], [], []);
        $orderResults->getDeletedObjects('foo');
    }

    public function testGetObjectMappingsReturnsMergedNewAndUpdated(): void
    {
        $newObjectMapping = new ObjectMapping();
        $newObjectMapping->setIntegrationObjectName('foo');
        $newObjectMapping->setIntegrationObjectId('abc');

        $updatedObjectMapping = new ObjectMapping();
        $updatedObjectMapping->setIntegrationObjectName('foo');
        $updatedObjectMapping->setIntegrationObjectId('hij');

        $orderResults = new OrderResultsDAO([$newObjectMapping], [$updatedObjectMapping], [], []);

        $objectMappings = $orderResults->getObjectMappings('foo');
        $this->assertCount(2, $objectMappings);
        $this->assertEquals('abc', $objectMappings[0]->getIntegrationObjectId());
        $this->assertEquals('hij', $objectMappings[1]->getIntegrationObjectId());

        $objectMappings = $orderResults->getObjectMappings('bar');
        $this->assertEmpty($objectMappings);
    }
}
