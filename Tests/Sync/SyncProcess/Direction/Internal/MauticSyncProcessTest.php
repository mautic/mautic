<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\SyncProcess\Direction\Internal;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO as OrderFieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO as ReportFieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\ObjectChangeGenerator;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Internal\MauticSyncProcess;

class MauticSyncProcessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SyncDateHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $syncDateHelper;

    /**
     * @var ObjectChangeGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectChangeGenerator;

    /**
     * @var MauticSyncDataExchange|\PHPUnit_Framework_MockObject_MockObject
     */
    private $syncDataExchange;

    protected function setUp()
    {
        $this->syncDateHelper = $this->createMock(SyncDateHelper::class);
        $this->objectChangeGenerator = $this->createMock(ObjectChangeGenerator::class);
        $this->syncDataExchange = $this->createMock(MauticSyncDataExchange::class);
    }

    public function testThatMauticGetSyncReportIsCalledBasedOnRequest()
    {
        $integration = 'Test';
        $objectName  = 'Contact';
        $mappingManual = new MappingManualDAO($integration);
        $objectMapping = new ObjectMappingDAO(MauticSyncDataExchange::OBJECT_CONTACT, $objectName);
        $objectMapping->addFieldMapping('email', 'email', ObjectMappingDAO::SYNC_BIDIRECTIONALLY, true);
        $objectMapping->addFieldMapping('firstname', 'first_name');
        $mappingManual->addObjectMapping($objectMapping);

        $fromSyncDateTime = new \DateTimeImmutable();
        $this->syncDateHelper->expects($this->once())
            ->method('getSyncFromDateTime')
            ->with(MauticSyncDataExchange::NAME, MauticSyncDataExchange::OBJECT_CONTACT)
            ->willReturn($fromSyncDateTime);

        $toSyncDateTime   = new \DateTimeImmutable();
        $this->syncDateHelper->expects($this->once())
            ->method('getSyncToDateTime')
            ->willReturn($toSyncDateTime);

        // SyncDateExchangeInterface::getSyncReport should sync because an object was added to the report
        $this->syncDataExchange->expects($this->once())
            ->method('getSyncReport')
            ->willReturnCallback(
                function (RequestDAO $requestDAO) use ($integration) {
                    $requestObjects = $requestDAO->getObjects();
                    $this->assertCount(1, $requestObjects);

                    /** @var RequestObjectDAO $requestObject */
                    $requestObject = $requestObjects[0];
                    $this->assertEquals(['email'], $requestObject->getRequiredFields());
                    $this->assertEquals(['email', 'firstname'], $requestObject->getFields());
                    $this->assertEquals(MauticSyncDataExchange::OBJECT_CONTACT, $requestObject->getObject());

                    return new ReportDAO($integration);
                }
            );

        $this->getSyncProcess($mappingManual)->getSyncReport(1);
    }

    public function testThatMauticGetSyncReportIsNotCalledBasedOnRequest()
    {
        $integration = 'Test';
        $objectName  = 'Contact';
        $mappingManual = new MappingManualDAO($integration);

        $this->syncDateHelper->expects($this->never())
            ->method('getSyncFromDateTime')
            ->with($integration, $objectName);

        // SyncDateExchangeInterface::getSyncReport should sync because an object was added to the report
        $this->syncDataExchange->expects($this->never())
            ->method('getSyncReport');

        $report = $this->getSyncProcess($mappingManual)->getSyncReport(1);

        $this->assertEquals(MauticSyncDataExchange::NAME, $report->getIntegration());
    }

    public function testOrderIsBuiltBasedOnMapping()
    {
        $integration = 'Test';
        $objectName  = 'Contact';
        $mappingManual = new MappingManualDAO($integration);
        $objectMapping = new ObjectMappingDAO(MauticSyncDataExchange::OBJECT_CONTACT, $objectName);
        $objectMapping->addFieldMapping('email', 'email', ObjectMappingDAO::SYNC_BIDIRECTIONALLY, true);
        $objectMapping->addFieldMapping('firstname', 'first_name');
        $mappingManual->addObjectMapping($objectMapping);

        $toSyncDateTime   = new \DateTimeImmutable();
        $this->syncDateHelper->expects($this->once())
            ->method('getSyncDateTime')
            ->willReturn($toSyncDateTime);

        $syncReport = new ReportDAO($integration);
        $objectDAO = new ReportObjectDAO($objectName, 2);
        $objectDAO->addField(new ReportFieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com')));
        $objectDAO->addField(new ReportFieldDAO('first_name', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob')));
        $syncReport->addObject($objectDAO);

        // Search for an internal object
        $this->syncDataExchange->expects($this->once())
            ->method('getConflictedInternalObject')
            ->with($mappingManual, MauticSyncDataExchange::OBJECT_CONTACT, $objectDAO)
            ->willReturn(
                new ReportObjectDAO(MauticSyncDataExchange::OBJECT_CONTACT, 1)
            );

        $objectChangeDAO = new ObjectChangeDAO(MauticSyncDataExchange::NAME, MauticSyncDataExchange::OBJECT_CONTACT, 1, $objectName, 2);
        $objectChangeDAO->addField(new OrderFieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com')));
        $objectChangeDAO->addField(new OrderFieldDAO('firstname', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob')));
        $this->objectChangeGenerator->expects($this->once())
            ->method('getSyncObjectChange')
            ->willReturn($objectChangeDAO);

        $syncOrder = $this->getSyncProcess($mappingManual)->getSyncOrder($syncReport);

        // The change should have been added to the order as an identified object
        $this->assertEquals([MauticSyncDataExchange::OBJECT_CONTACT => [1 => $objectChangeDAO]], $syncOrder->getIdentifiedObjects());
    }

    /**
     * @param MappingManualDAO $mappingManualDAO
     *
     * @return MauticSyncProcess
     */
    private function getSyncProcess(MappingManualDAO $mappingManualDAO)
    {
        $syncProcess = new MauticSyncProcess($this->syncDateHelper, $this->objectChangeGenerator);

        $syncProcess->setupSync(false, $mappingManualDAO, $this->syncDataExchange);

        return $syncProcess;
    }
}
