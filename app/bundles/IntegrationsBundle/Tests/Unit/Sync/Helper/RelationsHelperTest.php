<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Unit\Sync\Helper;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\RelationsDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\RelationDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\ReferenceValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\Helper\RelationsHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class RelationsHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MappingHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mappingHelper;

    /**
     * @var RelationsHelper
     */
    private $relationsHelper;

    /**
     * @var ReportDAO|\PHPUnit_Framework_MockObject_MockObject
     */
    private $syncReport;

    /**
     * @var MappingManualDAO|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mappingManual;

    protected function setUp(): void
    {
        $this->mappingHelper   = $this->createMock(MappingHelper::class);
        $this->relationsHelper = new RelationsHelper($this->mappingHelper);
        $this->syncReport      = $this->createMock(ReportDAO::class);
        $this->mappingManual   = $this->createMock(MappingManualDAO::class);
    }

    public function testProcessRelationsWithUnsychronisedObjects(): void
    {
        $integrationObjectId    = 'IntegrationId-123';
        $integrationRelObjectId = 'IntegrationId-456';
        $relObjectName          = 'Account';

        $relationObject = new RelationDAO(
            'Contact',
            'AccountId',
            $relObjectName,
            $integrationObjectId,
            $integrationRelObjectId
        );

        $relationsObject = new RelationsDAO();
        $relationsObject->addRelation($relationObject);

        $this->syncReport->expects($this->once())
            ->method('getRelations')
            ->willReturn($relationsObject);

        $this->mappingManual->expects($this->any())
            ->method('getMappedInternalObjectsNames')
            ->willReturn(['company']);

        $internalObject = new ObjectDAO('company', null);

        $this->mappingHelper->expects($this->once())
            ->method('findMauticObject')
            ->willReturn($internalObject);

        $this->relationsHelper->processRelations($this->mappingManual, $this->syncReport);

        $objectsToSynchronize = $this->relationsHelper->getObjectsToSynchronize();

        $this->assertCount(1, $objectsToSynchronize);

        $this->assertEquals($objectsToSynchronize[0]->getObjectId(), $integrationRelObjectId);
        $this->assertEquals($objectsToSynchronize[0]->getObject(), $relObjectName);
    }

    public function testProcessRelationsWithSychronisedObjects(): void
    {
        $integrationObjectId    = 'IntegrationId-123';
        $integrationRelObjectId = 'IntegrationId-456';
        $internalRelObjectId    = 13;
        $relObjectName          = 'Account';
        $relFieldName           = 'AccountId';

        $referenceVlaue  = new ReferenceValueDAO();
        $normalizedValue = new NormalizedValueDAO(NormalizedValueDAO::REFERENCE_TYPE, $integrationRelObjectId, $referenceVlaue);

        $fieldDao  = new FieldDAO('AccountId', $normalizedValue);
        $objectDao = new ObjectDAO('Contact', 1);
        $objectDao->addField($fieldDao);

        $relationObject = new RelationDAO(
            'Contact',
            $relFieldName,
            $relObjectName,
            $integrationObjectId,
            $integrationRelObjectId
        );

        $relationsObject = new RelationsDAO();
        $relationsObject->addRelation($relationObject);

        $this->syncReport->expects($this->once())
            ->method('getRelations')
            ->willReturn($relationsObject);

        $this->syncReport->expects($this->once())
            ->method('getObject')
            ->willReturn($objectDao);

        $this->mappingManual->expects($this->any())
            ->method('getMappedInternalObjectsNames')
            ->willReturn(['company']);

        $internalObject = new ObjectDAO(MauticSyncDataExchange::OBJECT_COMPANY, $internalRelObjectId);

        $this->mappingHelper->expects($this->once())
            ->method('findMauticObject')
            ->willReturn($internalObject);

        $this->relationsHelper->processRelations($this->mappingManual, $this->syncReport);

        $objectsToSynchronize = $this->relationsHelper->getObjectsToSynchronize();

        $this->assertCount(0, $objectsToSynchronize);
        $this->assertEquals($internalRelObjectId, $objectDao->getField($relFieldName)->getValue()->getNormalizedValue()->getValue());
        $this->assertEquals(MauticSyncDataExchange::OBJECT_COMPANY, $objectDao->getField($relFieldName)->getValue()->getNormalizedValue()->getType());
    }
}
