<?php
/*
 * @copyright   2019 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Tests\Sync\Helper;


use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\ReferenceValueDAO;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\Helper\RelationsHelper;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\RelationDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\RelationsDAO;

class RelationsHelperTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MappingHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $mappingHelper;

    protected function setUp()
    {
        $this->mappingHelper = $this->createMock(MappingHelper::class);
    }

    public function testProcessRelationsWithUnsychronisedObjects()
    {
        $relationsHelper  = new RelationsHelper($this->mappingHelper);
        $syncReport       = $this->createMock(ReportDAO::class);
        $mappingManual    = $this->createMock(MappingManualDAO::class);

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

        $syncReport->expects($this->once())
            ->method('getRelations')
            ->willReturn($relationsObject);

        $mappingManual->expects($this->once())
            ->method('getMappedInternalObjectsNames')
            ->willReturn(['company']);

        $internalObject = new ObjectDAO('company', null);

        $this->mappingHelper->expects($this->once())
            ->method('findMauticObject')
            ->willReturn($internalObject);

        $relationsHelper->processRelations($mappingManual, $syncReport);

        $objectsToSynchronize = $relationsHelper->getObjectsToSynchronize();

        $this->assertCount(1, $objectsToSynchronize);

        $this->assertEquals($objectsToSynchronize[0]->getObjectId(), $integrationRelObjectId);
        $this->assertEquals($objectsToSynchronize[0]->getObject(), $relObjectName);
    }

    public function testProcessRelationsWithSychronisedObjects()
    {
        $relationsHelper = new RelationsHelper($this->mappingHelper);
        $syncReport      = $this->createMock(ReportDAO::class);
        $mappingManual   = $this->createMock(MappingManualDAO::class);

        $integrationObjectId    = 'IntegrationId-123';
        $integrationRelObjectId = 'IntegrationId-456';
        $internalRelObjectId    = 13;
        $relObjectName          = 'Account';
        $relFieldName           = 'AccountId';

        $referenceVlaue  = new ReferenceValueDAO($integrationRelObjectId);
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

        $syncReport->expects($this->once())
            ->method('getRelations')
            ->willReturn($relationsObject);


        $syncReport->expects($this->once())
            ->method('getObject')
            ->willReturn($objectDao);

        $mappingManual->expects($this->once())
            ->method('getMappedInternalObjectsNames')
            ->willReturn(['company']);

        $internalObject = new ObjectDAO('company', $internalRelObjectId);

        $this->mappingHelper->expects($this->once())
            ->method('findMauticObject')
            ->willReturn($internalObject);

        $relationsHelper->processRelations($mappingManual, $syncReport);

        $objectsToSynchronize = $relationsHelper->getObjectsToSynchronize();

        $this->assertCount(0, $objectsToSynchronize);
        $this->assertEquals($internalRelObjectId, $objectDao->getField($relFieldName)->getValue()->getNormalizedValue()->getValue());
    }
}
