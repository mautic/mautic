<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\Helper;


use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\IntegrationsBundle\Entity\ObjectMappingRepository;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectDeletedException;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class MappingHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FieldModel|\PHPUnit_Framework_MockObject_MockObject
     */
    private $fieldModel;

    /**
     * @var ContactObjectHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contactObjectHelper;

    /**
     * @var CompanyObjectHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $companyObjectHelper;

    /**
     * @var ObjectMappingRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $objectMappingRepository;

    protected function setUp()
    {
        $this->fieldModel              = $this->createMock(FieldModel::class);
        $this->contactObjectHelper     = $this->createMock(ContactObjectHelper::class);
        $this->companyObjectHelper     = $this->createMock(CompanyObjectHelper::class);
        $this->objectMappingRepository = $this->createMock(ObjectMappingRepository::class);
    }

    public function testObjectReturnedIfKnwonMappingExists()
    {
        $mappingManual        = new MappingManualDAO('test');
        $integrationObjectDAO = new ObjectDAO('Object', 1);

        $internalObjectDAO = [
            'internal_object_id' => 1,
            'last_sync_date'     => '2018-10-01 00:00:00',
            'is_deleted'         => 0,
        ];

        $this->objectMappingRepository->expects($this->once())
            ->method('getInternalObject')
            ->willReturn($internalObjectDAO);

        $internalObjectName  = 'Contact';
        $foundInternalObject = $this->getMappingHelper()->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        $this->assertEquals($internalObjectName, $foundInternalObject->getObject());
        $this->assertEquals($internalObjectDAO['internal_object_id'], $foundInternalObject->getObjectId());
        $this->assertEquals($internalObjectDAO['last_sync_date'], $foundInternalObject->getChangeDateTime()->format('Y-m-d H:i:s'));
    }

    public function testMauticObjectSearchedAndEmptyObjectReturnedIfNoIdentifierFieldsAreMapped()
    {
        $this->fieldModel->expects($this->once())
            ->method('getUniqueIdentifierFields')
            ->willReturn([]);

        $mappingManual        = $this->createMock(MappingManualDAO::class);
        $internalObjectName   = 'Contact';
        $integrationObjectDAO = new ObjectDAO('Object', 1);

        $foundInternalObject = $this->getMappingHelper()->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        $this->assertEquals($internalObjectName, $foundInternalObject->getObject());
        $this->assertEquals(null, $foundInternalObject->getObjectId());
    }

    public function testEmptyObjectIsReturnedWhenMauticContactIsNotFound()
    {
        $this->fieldModel->expects($this->once())
            ->method('getUniqueIdentifierFields')
            ->willReturn(
                [
                    'email' => 'Email'
                ]
            );

        $internalObjectName   = MauticSyncDataExchange::OBJECT_CONTACT;
        $integrationObjectDAO = new ObjectDAO('Object', 1);
        $integrationObjectDAO->addField(new FieldDAO('integration_email', new NormalizedValueDAO('email', 'test@test.com')));

        $mappingManual = $this->createMock(MappingManualDAO::class);
        $mappingManual->expects($this->once())
            ->method('getIntegrationMappedField')
            ->with($integrationObjectDAO->getObject(), $internalObjectName, 'email')
            ->willReturn('integration_email');

        $this->contactObjectHelper->expects($this->once())
            ->method('findObjectsByFieldValues')
            ->with(['email' => 'test@test.com'])
            ->willReturn([]);

        $foundInternalObject = $this->getMappingHelper()->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        $this->assertEquals($internalObjectName, $foundInternalObject->getObject());
        $this->assertEquals(null, $foundInternalObject->getObjectId());
    }

    public function testMauticContactIsFoundAndReturnedAsObjectDAO()
    {
        $this->fieldModel->expects($this->once())
            ->method('getUniqueIdentifierFields')
            ->willReturn(
                [
                    'email' => 'Email'
                ]
            );

        $internalObjectName   = MauticSyncDataExchange::OBJECT_CONTACT;
        $changeDateTime = new \DateTime();
        $integrationObjectDAO = new ObjectDAO('Object', 1, $changeDateTime);
        $integrationObjectDAO->addField(new FieldDAO('integration_email', new NormalizedValueDAO('email', 'test@test.com')));

        $mappingManual = $this->createMock(MappingManualDAO::class);
        $mappingManual->expects($this->once())
            ->method('getIntegrationMappedField')
            ->with($integrationObjectDAO->getObject(), $internalObjectName, 'email')
            ->willReturn('integration_email');
        $mappingManual->expects($this->exactly(2))
            ->method('getIntegration')
            ->willReturn('Test');

        $this->contactObjectHelper->expects($this->once())
            ->method('findObjectsByFieldValues')
            ->with(['email' => 'test@test.com'])
            ->willReturn(
                [
                    [
                        'id' => 3
                    ]
                ]
            );
        $this->companyObjectHelper->expects($this->never())
            ->method('findObjectsByFieldValues');

        $foundInternalObject = $this->getMappingHelper()->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        $this->assertEquals($internalObjectName, $foundInternalObject->getObject());
        $this->assertEquals(3, $foundInternalObject->getObjectId());
    }

    public function testMauticCompanyIsFoundAndReturnedAsObjectDAO()
    {
        $this->fieldModel->expects($this->once())
            ->method('getUniqueIdentifierFields')
            ->willReturn(
                [
                    'email' => 'Email'
                ]
            );

        $internalObjectName   = MauticSyncDataExchange::OBJECT_COMPANY;
        $changeDateTime = new \DateTime();
        $integrationObjectDAO = new ObjectDAO('Object', 1, $changeDateTime);
        $integrationObjectDAO->addField(new FieldDAO('integration_email', new NormalizedValueDAO('email', 'test@test.com')));

        $mappingManual = $this->createMock(MappingManualDAO::class);
        $mappingManual->expects($this->once())
            ->method('getIntegrationMappedField')
            ->with($integrationObjectDAO->getObject(), $internalObjectName, 'email')
            ->willReturn('integration_email');
        $mappingManual->expects($this->exactly(2))
            ->method('getIntegration')
            ->willReturn('Test');

        $this->companyObjectHelper->expects($this->once())
            ->method('findObjectsByFieldValues')
            ->with(['email' => 'test@test.com'])
            ->willReturn(
                [
                    [
                        'id' => 3
                    ]
                ]
            );
        $this->contactObjectHelper->expects($this->never())
            ->method('findObjectsByFieldValues');

        $foundInternalObject = $this->getMappingHelper()->findMauticObject($mappingManual, $internalObjectName, $integrationObjectDAO);

        $this->assertEquals($internalObjectName, $foundInternalObject->getObject());
        $this->assertEquals(3, $foundInternalObject->getObjectId());
    }

    public function testIntegrationObjectReturnedIfMapped()
    {
        $objectName     = 'Object';
        $objectId       = 1;
        $changeDateTime = '2018-10-08 00:00:00';

        $this->objectMappingRepository->expects($this->once())
            ->method('getIntegrationObject')
            ->willReturn(
                [
                    'is_deleted'            => false,
                    'integration_object_id' => $objectId,
                    'last_sync_date'        => $changeDateTime,
                ]
            );

        $foundIntegrationObject = $this->getMappingHelper()->findIntegrationObject('Test', $objectName, new ObjectDAO('Contact', 1));

        $this->assertEquals($objectName, $foundIntegrationObject->getObject());
        $this->assertEquals($objectId, $foundIntegrationObject->getObjectId());
        $this->assertEquals($changeDateTime, $foundIntegrationObject->getChangeDateTime()->format('Y-m-d H:i:s'));
    }

    public function testEmptyIntegrationObjectReturnedIfNotMapped()
    {
        $objectName     = 'Object';
        $this->objectMappingRepository->expects($this->once())
            ->method('getIntegrationObject')
            ->willReturn([]);

        $foundIntegrationObject = $this->getMappingHelper()->findIntegrationObject('Test', $objectName, new ObjectDAO('Contact', 1));

        $this->assertEquals($objectName, $foundIntegrationObject->getObject());
        $this->assertEquals(null, $foundIntegrationObject->getObjectId());
        $this->assertEquals(null, $foundIntegrationObject->getChangeDateTime());
    }

    public function testDeletedExceptionThrownIfIntegrationObjectHasBeenNotedAsDeleted()
    {
        $this->expectException(ObjectDeletedException::class);

        $objectName     = 'Object';
        $objectId       = 1;
        $changeDateTime = '2018-10-08 00:00:00';

        $this->objectMappingRepository->expects($this->once())
            ->method('getIntegrationObject')
            ->willReturn(
                [
                    'is_deleted'            => true,
                    'integration_object_id' => $objectId,
                    'last_sync_date'        => $changeDateTime,
                ]
            );

        $this->getMappingHelper()->findIntegrationObject('Test', $objectName, new ObjectDAO('Contact', 1));
    }

    /**
     * @return MappingHelper
     */
    public function getMappingHelper()
    {
        return new MappingHelper($this->fieldModel, $this->objectMappingRepository, $this->contactObjectHelper, $this->companyObjectHelper);
    }
}