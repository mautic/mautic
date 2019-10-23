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

namespace MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Integration;

use MauticPlugin\IntegrationsBundle\Exception\InvalidValueException;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\FieldMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\FieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\FieldNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\SyncProcess\Direction\Helper\ValueHelper;

class ObjectChangeGenerator
{
    /**
     * @var ValueHelper
     */
    private $valueHelper;

    /**
     * @var ReportDAO
     */
    private $syncReport;

    /**
     * @var MappingManualDAO
     */
    private $mappingManual;

    /**
     * @var ReportObjectDAO
     */
    private $internalObject;

    /**
     * @var ReportObjectDAO
     */
    private $integrationObject;

    /**
     * @var ObjectChangeDAO
     */
    private $objectChange;

    /**
     * @param ValueHelper $valueHelper
     */
    public function __construct(ValueHelper $valueHelper)
    {
        $this->valueHelper = $valueHelper;
    }

    /**
     * @param ReportDAO        $syncReport
     * @param MappingManualDAO $mappingManual
     * @param ObjectMappingDAO $objectMapping
     * @param ReportObjectDAO  $internalObject
     * @param ReportObjectDAO  $integrationObject
     *
     * @return ObjectChangeDAO
     *
     * @throws ObjectNotFoundException
     */
    public function getSyncObjectChange(
        ReportDAO $syncReport,
        MappingManualDAO $mappingManual,
        ObjectMappingDAO $objectMapping,
        ReportObjectDAO $internalObject,
        ReportObjectDAO $integrationObject
    ) {
        $this->syncReport        = $syncReport;
        $this->mappingManual     = $mappingManual;
        $this->internalObject    = $internalObject;
        $this->integrationObject = $integrationObject;

        $this->objectChange = new ObjectChangeDAO(
            $this->mappingManual->getIntegration(),
            $integrationObject->getObject(),
            $integrationObject->getObjectId(),
            $internalObject->getObject(),
            $internalObject->getObjectId()
        );

        if ($integrationObject->getObjectId()) {
            DebugLogger::log(
                $this->mappingManual->getIntegration(),
                sprintf(
                    "Mautic to integration; found a match between the integration %s:%s object and Mautic's %s:%s object",
                    $integrationObject->getObject(),
                    (string) $integrationObject->getObjectId(),
                    $internalObject->getObject(),
                    (string) $internalObject->getObjectId()
                ),
                __CLASS__.':'.__FUNCTION__
            );
        } else {
            DebugLogger::log(
                $this->mappingManual->getIntegration(),
                sprintf(
                    'Mautic to integration: no match found for %s:%s',
                    $internalObject->getObject(),
                    (string) $internalObject->getObjectId()
                ),
                __CLASS__.':'.__FUNCTION__
            );
        }

        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMapping->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            $this->addFieldToObjectChange($fieldMappingDAO);
        }

        // Set the change date/time from the object so that we can update last sync date based on this
        $this->objectChange->setChangeDateTime($internalObject->getChangeDateTime());

        return $this->objectChange;
    }

    /**
     * @param FieldMappingDAO $fieldMappingDAO
     *
     * @throws ObjectNotFoundException
     */
    private function addFieldToObjectChange(FieldMappingDAO $fieldMappingDAO): void
    {
        try {
            $fieldState = $this->internalObject->getField($fieldMappingDAO->getInternalField())->getState();

            $internalInformationChangeRequest = $this->syncReport->getInformationChangeRequest(
                $this->internalObject->getObject(),
                $this->internalObject->getObjectId(),
                $fieldMappingDAO->getInternalField()
            );
        } catch (FieldNotFoundException $e) {
            return;
        }

        try {
            $newValue = $this->valueHelper->getValueForIntegration(
                $internalInformationChangeRequest->getNewValue(),
                $fieldState,
                $fieldMappingDAO->getSyncDirection()
            );
        } catch (InvalidValueException $e) {
            return; // Field has to be skipped
        }

        // Note: bidirectional conflicts were handled by Internal\ObjectChangeGenerator
        $this->objectChange->addField(
            new FieldDAO($fieldMappingDAO->getIntegrationField(), $newValue),
            $fieldState
        );

        /*
         * Below here is just debug logging
         */

        // ObjectMappingDAO::SYNC_TO_MAUTIC
        if (ObjectMappingDAO::SYNC_TO_MAUTIC === $fieldMappingDAO->getSyncDirection()) {
            DebugLogger::log(
                $this->mappingManual->getIntegration(),
                sprintf(
                    "Mautic to integration; the %s object's %s field %s was added to the list of %s fields",
                    $this->integrationObject->getObject(),
                    $fieldState,
                    $fieldMappingDAO->getIntegrationField(),
                    $fieldState
                ),
                __CLASS__.':'.__FUNCTION__
            );

            return;
        }

        // ObjectMappingDAO::SYNC_TO_INTEGRATION
        // ObjectMappingDAO::SYNC_BIDIRECTIONALLY
        DebugLogger::log(
            $this->mappingManual->getIntegration(),
            sprintf(
                "Mautic to integration; syncing %s object's %s field %s with a value of %s",
                $this->integrationObject->getObject(),
                $fieldState,
                $fieldMappingDAO->getIntegrationField(),
                var_export($newValue->getNormalizedValue(), true)
            ),
            __CLASS__.':'.__FUNCTION__
        );
    }
}
