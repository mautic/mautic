<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\SyncProcess\Direction\Integration;


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
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\IntegrationSyncProcess;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Integration\ObjectChangeGenerator;

class IntegrationSyncProcessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SyncDateHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $syncDateHelper;

    /**
     * @var MappingHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mappingHelper;

    /**
     * @var ObjectChangeGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectChangeGenerator;

    /**
     * @var SyncDataExchangeInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $syncDataExchange;

    protected function setUp()
    {
        $this->syncDateHelper = $this->createMock(SyncDateHelper::class);
        $this->mappingHelper = $this->createMock(MappingHelper::class);
        $this->objectChangeGenerator = $this->createMock(ObjectChangeGenerator::class);
        $this->syncDataExchange = $this->createMock(SyncDataExchangeInterface::class);
    }

    public function testThatIntegrationGetSyncReportIsCalledBasedOnRequest()
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
            ->with($integration, $objectName)
            ->willReturn($fromSyncDateTime);

        $toSyncDateTime   = new \DateTimeImmutable();
        $this->syncDateHelper->expects($this->once())
            ->method('getSyncToDateTime')
            ->willReturn($toSyncDateTime);

        $this->syncDateHelper->expects($this->once())
            ->method('getLastSyncDateForObject')
            ->with($integration, $objectName)
            ->willReturn(null);

        // SyncDateExchangeInterface::getSyncReport should sync because an object was added to the report
        $this->syncDataExchange->expects($this->once())
            ->method('getSyncReport')
            ->willReturnCallback(
                function (RequestDAO $requestDAO) use ($integration, $objectName) {
                    $requestObjects = $requestDAO->getObjects();
                    $this->assertCount(1, $requestObjects);

                    /** @var RequestObjectDAO $requestObject */
                    $requestObject = $requestObjects[0];
                    $this->assertEquals(['email'], $requestObject->getRequiredFields());
                    $this->assertEquals(['email', 'first_name'], $requestObject->getFields());
                    $this->assertEquals($objectName, $requestObject->getObject());

                    return new ReportDAO($integration);
                }
            );

        $this->getSyncProcess($mappingManual)->getSyncReport(1);
    }

    public function testThatIntegrationGetSyncReportIsNotCalledBasedOnRequest()
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

        $this->assertEquals($integration, $report->getIntegration());
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

        $syncReport = new ReportDAO(MauticSyncDataExchange::NAME);
        $objectDAO = new ReportObjectDAO(MauticSyncDataExchange::OBJECT_CONTACT, 1);
        $objectDAO->addField(new ReportFieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com')));
        $objectDAO->addField(new ReportFieldDAO('firstname', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob')));
        $syncReport->addObject($objectDAO);

        // It should search for an integration object mapped to an internal object
        $this->mappingHelper->expects($this->once())
            ->method('findIntegrationObject')
            ->with($integration, $objectName, $objectDAO)
            ->willReturn(
                new ReportObjectDAO($objectName, 2)
            );

        $objectChangeDAO = new ObjectChangeDAO($integration, $objectName, 2, MauticSyncDataExchange::OBJECT_CONTACT, 1);
        $objectChangeDAO->addField(new OrderFieldDAO('email', new NormalizedValueDAO(NormalizedValueDAO::EMAIL_TYPE, 'test@test.com')));
        $objectChangeDAO->addField(new OrderFieldDAO('first_name', new NormalizedValueDAO(NormalizedValueDAO::TEXT_TYPE, 'Bob')));
        $this->objectChangeGenerator->expects($this->once())
            ->method('getSyncObjectChange')
            ->willReturn($objectChangeDAO);
        
        $syncOrder = $this->getSyncProcess($mappingManual)->getSyncOrder($syncReport);

        // The change should have been added to the order as an identified object
        $this->assertEquals([$objectName => [2 => $objectChangeDAO]], $syncOrder->getIdentifiedObjects());
    }

    /**
     * @param MappingManualDAO $mappingManualDAO
     *
     * @return IntegrationSyncProcess
     */
    private function getSyncProcess(MappingManualDAO $mappingManualDAO)
    {
        $syncProcess = new IntegrationSyncProcess($this->syncDateHelper, $this->mappingHelper, $this->objectChangeGenerator);

        $syncProcess->setupSync(false, $mappingManualDAO, $this->syncDataExchange);

        return $syncProcess;
    }
}