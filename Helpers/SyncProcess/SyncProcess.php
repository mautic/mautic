<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Helpers\SyncProcess;

use MauticPlugin\IntegrationsBundle\DAO\Mapping\ObjectMappingDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Order\FieldDAO;
use MauticPlugin\IntegrationsBundle\DAO\Mapping\FieldMappingDAO;
use MauticPlugin\IntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Facade\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Facade\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Helpers\SyncDateHelper;

/**
 * Class SyncProcess
 */
class SyncProcess
{
    /**
     * @var MappingManualDAO
     */
    private $mappingManualDAO;

    /**
     * @var MauticSyncDataExchange
     */
    private $internalSyncDataExchange;

    /**
     * @var SyncDataExchangeInterface
     */
    private $integrationSyncDataExchange;

    /**
     * @var SyncDateHelper
     */
    private $syncDateHelper;

    /**
     * @var \DateTimeInterface
     */
    private $syncDateTime;

    /**
     * @var \DateTimeInterface|null
     */
    private $syncFromDateTime;

    /**
     * @var int
     */
    private $syncIteration = 1;

    /**
     * @var \DateTimeInterface[]
     */
    private $lastObjectSyncDates = [];

    /**
     * SyncProcess constructor.
     *
     * @param MappingManualDAO          $mappingManualDAO
     * @param SyncDataExchangeInterface $internalSyncDataExchange
     * @param SyncDataExchangeInterface $integrationSyncDataExchange
     * @param SyncDateHelper            $syncDateHelper
     * @param \DateTimeInterface|null   $syncFromDateTime
     */
    public function __construct(
        MappingManualDAO $mappingManualDAO,
        SyncDataExchangeInterface $internalSyncDataExchange,
        SyncDataExchangeInterface $integrationSyncDataExchange,
        SyncDateHelper $syncDateHelper,
        \DateTimeInterface $syncFromDateTime = null
    )
    {
        $this->mappingManualDAO            = $mappingManualDAO;
        $this->internalSyncDataExchange    = $internalSyncDataExchange;
        $this->integrationSyncDataExchange = $integrationSyncDataExchange;
        $this->mappingManualDAO            = $mappingManualDAO;
        $this->syncDateHelper              = $syncDateHelper;
        $this->syncFromDateTime            = $syncFromDateTime;
    }

    /**
     * Execute sync with integration
     */
    public function execute()
    {
        defined('MAUTIC_INTEGRATION_ACTIVE_SYNC') or define('MAUTIC_INTEGRATION_ACTIVE_SYNC', 1);

        $this->syncDateTime  = new \DateTimeImmutable();

        $this->syncIteration = 1;
        do {
            $syncReport = $this->generateIntegrationSyncReport();

            if ($syncReport->shouldSync()) {
                $internalSyncOrder = $this->generateInternalSyncOrder($syncReport);
                $this->internalSyncDataExchange->executeSyncOrder($internalSyncOrder);
            }

            $this->syncIteration++;
        } while ($syncReport->shouldSync());

        do {
            $syncReport = $this->generateInternalSyncReport();

            if ($syncReport->shouldSync()) {
                $integrationSyncOrder = $this->generateIntegrationSyncOrder($syncReport);
                $this->integrationSyncDataExchange->executeSyncOrder($integrationSyncOrder);

                $this->internalSyncDataExchange->saveObjectRelationships($integrationSyncOrder->getEntityMappings());
            }
        } while ($syncReport->shouldSync());
    }

    /**
     * @return ReportDAO
     */
    private function generateIntegrationSyncReport()
    {
        $integrationRequestDAO = new RequestDAO($this->syncIteration);

        $integrationObjectsNames = $this->mappingManualDAO->getIntegrationObjectsNames();
        foreach ($integrationObjectsNames as $integrationObjectName) {
            $integrationObjectFields = $this->mappingManualDAO->getIntegrationObjectFieldNames($integrationObjectName);
            if (count($integrationObjectFields) === 0) {
                // No fields configured for a sync
                continue;
            }

            $objectSyncFromDateTime = $this->getSyncFromDateTime($this->mappingManualDAO->getIntegration(), $integrationObjectName);
            $integrationRequestObject = new RequestObjectDAO($integrationObjectName, $objectSyncFromDateTime, $this->syncDateTime);
            foreach ($integrationObjectFields as $integrationObjectField) {
                $integrationRequestObject->addField($integrationObjectField);
            }
            $integrationRequestDAO->addObject($integrationRequestObject);
        }

        $integrationSyncReport = $integrationRequestDAO->shouldSync()
            ? $this->integrationSyncDataExchange->getSyncReport($integrationRequestDAO)
            :
            new ReportDAO($this->mappingManualDAO->getIntegration());

        return $integrationSyncReport;
    }


    /**
     * @return ReportDAO
     */
    private function generateInternalSyncReport()
    {
        $internalRequestDAO = new RequestDAO($this->syncIteration);

        $internalObjectsNames = $this->mappingManualDAO->getInternalObjectsNames();
        foreach ($internalObjectsNames as $internalObjectName) {
            $internalObjectFields = $this->mappingManualDAO->getInternalObjectFieldNames($internalObjectName);
            if (count($internalObjectFields) === 0) {
                // No fields configured for a sync
                continue;
            }

            // Sync date does not matter in this case because Mautic will simply process anything in the queue
            $internalRequestObject = new RequestObjectDAO($internalObjectName);
            foreach ($internalObjectFields as $internalObjectField) {
                $internalRequestObject->addField($internalObjectField);
            }
            $internalRequestDAO->addObject($internalRequestObject);
        }

        $internalSyncReport = $internalRequestDAO->shouldSync()
            ? $this->internalSyncDataExchange->getSyncReport($internalRequestDAO)
            :
            new ReportDAO(MauticSyncDataExchange::NAME);

        return $internalSyncReport;
    }

    /**
     * @param ReportDAO $syncReport
     *
     * @return OrderDAO
     */
    private function generateInternalSyncOrder(ReportDAO $syncReport)
    {
        $syncOrder = new OrderDAO($this->syncDateTime);

        $integrationObjectsNames = $this->mappingManualDAO->getIntegrationObjectsNames();
        foreach ($integrationObjectsNames as $integrationObjectName) {
            $integrationObjects         = $syncReport->getObjects($integrationObjectName);
            $mappedInternalObjectsNames = $this->mappingManualDAO->getMappedInternalObjectsNames($integrationObjectName);
            foreach ($mappedInternalObjectsNames as $mappedInternalObjectName) {
                $objectMappingDAO = $this->mappingManualDAO->getObjectMapping($mappedInternalObjectName, $integrationObjectName);
                foreach ($integrationObjects as $integrationObject) {
                    $objectChange = $this->getIntegrationSyncObjectChange($syncReport, $objectMappingDAO, $integrationObject);

                    $syncOrder->addObjectChange($objectChange);
                }
            }
        }

        return $syncOrder;
    }

    /**
     * @param ReportDAO $syncReport
     *
     * @return OrderDAO
     */
    private function generateIntegrationSyncOrder(ReportDAO $syncReport)
    {
        $syncOrder = new OrderDAO($this->syncDateTime);

        $internalObjectNames = $this->mappingManualDAO->getIntegrationObjectsNames();
        foreach ($internalObjectNames as $internalObjectName) {
            $internalObjects         = $syncReport->getObjects($internalObjectName);
            $mappedIntegrationObjectNames = $this->mappingManualDAO->getMappedIntegrationObjectsNames($internalObjectName);
            foreach ($mappedIntegrationObjectNames as $mappedIntegrationObjectName) {
                $objectMappingDAO = $this->mappingManualDAO->getObjectMapping($mappedIntegrationObjectName, $internalObjectName);
                foreach ($internalObjects as $internalObject) {
                    $objectChange = $this->getInternalSyncObjectChange($syncReport, $objectMappingDAO, $internalObject);

                    $syncOrder->addObjectChange($objectChange);
                }
            }
        }

        return $syncOrder;
    }

    /**
     * @param string $integration
     * @param string $object
     *
     * @return \DateTimeInterface
     */
    private function getSyncFromDateTime(string $integration, string $object): \DateTimeInterface
    {
        if ($this->syncFromDateTime) {
            // The command requested a specific start date so use it

            return $this->syncFromDateTime;
        }

        $key = $integration.$object;
        if (isset($this->lastObjectSyncDates[$key])) {
            // Use the same sync date for integrations to paginate properly

            return $this->lastObjectSyncDates[$key];
        }

        if ($lastSync = $this->syncDateHelper->getLastSyncDateForObject($integration, $object)) {
            // Use the latest sync date recorded
            $this->lastObjectSyncDates[$key] = new \DateTimeImmutable($lastSync, new \DateTimeZone('UTC'));
        } else {
            // Otherwise, just sync the last 24 hours
            $this->lastObjectSyncDates[$key] = new \DateTimeImmutable('-24 hours', new \DateTimeZone('UTC'));
        }

        return $this->lastObjectSyncDates[$key];
    }


    /**
     * @param ReportDAO        $syncReport
     * @param ObjectMappingDAO $objectMappingDAO
     * @param ReportObjectDAO  $internalObjectDAO
     *
     * @return ObjectChangeDAO
     */
    private function getIntegrationSyncObjectChange(ReportDAO $syncReport, ObjectMappingDAO $objectMappingDAO, ReportObjectDAO $internalObjectDAO)
    {
        $integrationObjectChange = new ObjectChangeDAO(
            $objectMappingDAO->getIntegrationObjectName(),
            null,
            $internalObjectDAO->getObject(),
            $internalObjectDAO->getObjectId()
        );

        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMappingDAO->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            $internalInformationChangeRequest = $syncReport->getInformationChangeRequest(
                $objectMappingDAO->getInternalObjectName(),
                $internalObjectDAO->getObjectId(),
                $fieldMappingDAO->getInternalField()
            );

            // Perform the sync in the direction instructed
            switch ($fieldMappingDAO->getSyncDirection()) {
                case ObjectMappingDAO::SYNC_TO_MAUTIC:
                    // Ignore this field
                    break;
                case ObjectMappingDAO::SYNC_BIDIRECTIONALLY:
                case ObjectMappingDAO::SYNC_TO_INTEGRATION:
                    $integrationFieldChange = new FieldDAO($fieldMappingDAO->getIntegrationField(), $internalInformationChangeRequest->getNewValue());
                    $integrationObjectChange->addField($integrationFieldChange);

                    break;
            }
        }

        return $integrationObjectChange;
    }

    /**
     * @param ReportDAO        $syncReport
     * @param ObjectMappingDAO $objectMappingDAO
     * @param ReportObjectDAO  $integrationObjectDAO
     *
     * @return ObjectChangeDAO
     */
    private function getInternalSyncObjectChange(ReportDAO $syncReport, ObjectMappingDAO $objectMappingDAO, ReportObjectDAO $integrationObjectDAO)
    {
        $internalObjectChange = new ObjectChangeDAO(
            $objectMappingDAO->getInternalObjectName(),
            null,
            $integrationObjectDAO->getObject(),
            $integrationObjectDAO->getObjectId()
        );

        /** @var FieldMappingDAO[] $fieldMappings */
        $fieldMappings = $objectMappingDAO->getFieldMappings();
        foreach ($fieldMappings as $fieldMappingDAO) {
            $integrationInformationChangeRequest = $syncReport->getInformationChangeRequest(
                $objectMappingDAO->getIntegrationObjectName(),
                $integrationObjectDAO->getObjectId(),
                $fieldMappingDAO->getIntegrationField()
            );

            // Perform the sync in the direction instructed
            switch ($fieldMappingDAO->getSyncDirection()) {
                case ObjectMappingDAO::SYNC_TO_INTEGRATION:
                    // Ignore this field

                    break;
                case ObjectMappingDAO::SYNC_TO_MAUTIC:
                case ObjectMappingDAO::SYNC_BIDIRECTIONALLY:

                    $internalFieldChange = new FieldDAO($fieldMappingDAO->getInternalField(), $integrationInformationChangeRequest->getNewValue());
                    $internalObjectChange->addField($internalFieldChange);

                    break;
            }
        }

        return $internalObjectChange;
    }
}
