<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Tests\Unit\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\RemappedObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderResultsDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class OrderResultsDAOTest extends TestCase
{
    public function testObjectsOrganizedByObjectName()
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
        Assert::assertCount(1, $fooNewObjectMappings);
        Assert::assertEquals('abc', $fooNewObjectMappings[0]->getIntegrationObjectId());

        $barNewObjectMappings = $orderResults->getNewObjectMappings('bar');
        Assert::assertCount(1, $barNewObjectMappings);
        Assert::assertEquals('efg', $barNewObjectMappings[0]->getIntegrationObjectId());

        $fooRemappedObjects = $orderResults->getRemappedObjects('foo');
        Assert::assertCount(1, $fooRemappedObjects);
        Assert::assertEquals('foo1', $fooRemappedObjects[0]->getNewObjectId());

        $barRemappedObjects = $orderResults->getRemappedObjects('bar');
        Assert::assertCount(1, $barRemappedObjects);
        Assert::assertEquals('bar1', $barRemappedObjects[0]->getNewObjectId());

        $fooDeletedObjects = $orderResults->getDeletedObjects('foo');
        Assert::assertCount(1, $fooDeletedObjects);
        Assert::assertEquals('foo1', $fooDeletedObjects[0]->getObjectId());

        $barDeletedObjects = $orderResults->getDeletedObjects('bar');
        Assert::assertCount(1, $barDeletedObjects);
        Assert::assertEquals('bar1', $barDeletedObjects[0]->getObjectId());
    }

    public function testExceptionThrownIfObjectNotFoundForNewObjectMappings()
    {
        $this->expectException(ObjectNotFoundException::class);

        $orderResults = new OrderResultsDAO([], [], [], []);
        $orderResults->getNewObjectMappings('foo');
    }

    public function testExceptionThrownIfObjectNotFoundForUpdatedObjectMappings()
    {
        $this->expectException(ObjectNotFoundException::class);

        $orderResults = new OrderResultsDAO([], [], [], []);
        $orderResults->getUpdatedObjectMappings('foo');
    }

    public function testExceptionThrownIfObjectNotFoundForRemappedObjects()
    {
        $this->expectException(ObjectNotFoundException::class);

        $orderResults = new OrderResultsDAO([], [], [], []);
        $orderResults->getRemappedObjects('foo');
    }

    public function testExceptionThrownIfObjectNotFoundForDeletedObjects()
    {
        $this->expectException(ObjectNotFoundException::class);

        $orderResults = new OrderResultsDAO([], [], [], []);
        $orderResults->getDeletedObjects('foo');
    }
}
