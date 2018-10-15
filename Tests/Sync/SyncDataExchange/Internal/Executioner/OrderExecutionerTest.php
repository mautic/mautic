<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\SyncDataExchange\Internal\Executioner;


use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\OrderExecutioner;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\CompanyObject;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\ContactObject;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class OrderExecutionerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MappingHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mappingHelper;

    /**
     * @var ContactObject|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactObjectHelper;

    /**
     * @var CompanyObject|\PHPUnit_Framework_MockObject_MockObject
     */
    private $companyObjectHelper;

    protected function setup()
    {
        $this->mappingHelper = $this->createMock(MappingHelper::class);
        $this->contactObjectHelper = $this->createMock(ContactObject::class);
        $this->companyObjectHelper = $this->createMock(CompanyObject::class);
    }

    public function testContactsAreUpdatedAndCreated()
    {
        $this->contactObjectHelper->expects($this->exactly(1))
            ->method('update');
        $this->mappingHelper->expects($this->exactly(1))
            ->method('updateObjectMappings');

        $this->contactObjectHelper->expects($this->exactly(1))
            ->method('create');
        $this->mappingHelper->expects($this->exactly(1))
            ->method('saveObjectMappings');

        $this->companyObjectHelper->expects($this->never())
            ->method('update');
        $this->companyObjectHelper->expects($this->never())
            ->method('create');

        $syncOrder = $this->getSyncOrder(MauticSyncDataExchange::OBJECT_CONTACT);
        $this->getOrderExecutioner()->execute($syncOrder);
    }

    public function testCompaniesAreUpdatedAndCreated()
    {
        $this->companyObjectHelper->expects($this->exactly(1))
            ->method('update');
        $this->mappingHelper->expects($this->exactly(1))
            ->method('updateObjectMappings');

        $this->companyObjectHelper->expects($this->exactly(1))
            ->method('create');
        $this->mappingHelper->expects($this->exactly(1))
            ->method('saveObjectMappings');

        $this->contactObjectHelper->expects($this->never())
            ->method('update');
        $this->contactObjectHelper->expects($this->never())
            ->method('create');

        $syncOrder = $this->getSyncOrder(MauticSyncDataExchange::OBJECT_COMPANY);
        $this->getOrderExecutioner()->execute($syncOrder);
    }

    public function testMixedObjectsAreUpdatedAndCreated()
    {
        $this->companyObjectHelper->expects($this->exactly(1))
            ->method('update');
        $this->mappingHelper->expects($this->exactly(2))
            ->method('updateObjectMappings');

        $this->companyObjectHelper->expects($this->exactly(1))
            ->method('create');
        $this->mappingHelper->expects($this->exactly(2))
            ->method('saveObjectMappings');

        $this->contactObjectHelper->expects($this->exactly(1))
            ->method('update');
        $this->contactObjectHelper->expects($this->exactly(1))
            ->method('create');

        // Merge companies and contacts for the test
        $syncOrder = $this->getSyncOrder(MauticSyncDataExchange::OBJECT_CONTACT);
        $companySyncOrder = $this->getSyncOrder(MauticSyncDataExchange::OBJECT_COMPANY);
        foreach ($companySyncOrder->getChangedObjectsByObjectType(MauticSyncDataExchange::OBJECT_COMPANY) as $objectChange) {
            $syncOrder->addObjectChange($objectChange);
        }

        $this->getOrderExecutioner()->execute($syncOrder);
    }

    /**
     * @return OrderExecutioner
     */
    private function getOrderExecutioner()
    {
        return new OrderExecutioner($this->mappingHelper, $this->contactObjectHelper, $this->companyObjectHelper);
    }

    /**
     * @param $objectName
     *
     * @return OrderDAO
     * @throws \Exception
     */
    private function getSyncOrder($objectName)
    {
        $integration = 'Test';

        $syncOrder = new OrderDAO(new \DateTimeImmutable(), false, $integration);

        // Two updates
        $syncOrder->addObjectChange(new ObjectChangeDAO($integration, $objectName, 1, $objectName, 1));
        $syncOrder->addObjectChange(new ObjectChangeDAO($integration, $objectName, 2, $objectName, 2));

        // One create
        $syncOrder->addObjectChange(new ObjectChangeDAO($integration, $objectName, null, $objectName, 3));

        return $syncOrder;
    }
}