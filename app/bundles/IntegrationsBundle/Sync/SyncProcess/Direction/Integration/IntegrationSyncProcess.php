<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Integration;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectDeletedException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Helper\MappingHelper;
use Mautic\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;

class IntegrationSyncProcess
{
    private ?\Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO $inputOptionsDAO = null;

    private ?\Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO $mappingManualDAO = null;

    private ?\Mautic\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface $syncDataExchange = null;

    public function __construct(
        private SyncDateHelper $syncDateHelper,
        private MappingHelper $mappingHelper,
        private ObjectChangeGenerator $objectChangeGenerator
    ) {
    }

    public function setupSync(InputOptionsDAO $inputOptionsDAO, MappingManualDAO $mappingManualDAO, SyncDataExchangeInterface $syncDataExchange): void
    {
        $this->inputOptionsDAO  = $inputOptionsDAO;
        $this->mappingManualDAO = $mappingManualDAO;
        $this->syncDataExchange = $syncDataExchange;
    }

    /**
     * @throws ObjectNotFoundException
     */
    public function getSyncReport(int $syncIteration): ReportDAO
    {
        $integrationRequestDAO   = new RequestDAO(MauticSyncDataExchange::NAME, $syncIteration, $this->inputOptionsDAO);
        $integrationObjectsNames = $this->mappingManualDAO->getIntegrationObjectNames();
        foreach ($integrationObjectsNames as $integrationObjectName) {
            $integrationObjectFields = $this->mappingManualDAO->getIntegrationObjectFieldsToSyncToMautic($integrationObjectName);

            if (0 === count($integrationObjectFields)) {
                // No fields configured for a sync
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    sprintf(
                        'Integration to Mautic; there are no fields for the %s object',
                        $integrationObjectName
                    ),
                    self::class.':'.__FUNCTION__
                );

                continue;
            }

            $objectSyncFromDateTime = $this->syncDateHelper->getSyncFromDateTime($this->mappingManualDAO->getIntegration(), $integrationObjectName);
            $objectSyncToDateTime   = $this->syncDateHelper->getSyncToDateTime();
            $lastObjectSyncDateTime = $this->syncDateHelper->getLastSyncDateForObject($this->mappingManualDAO->getIntegration(), $integrationObjectName);
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    "Integration to Mautic; syncing from %s to %s for the %s object with %d fields but giving the option to sync from the object's last sync date of %s",
                    $objectSyncFromDateTime->format('Y-m-d H:i:s'),
                    $objectSyncToDateTime->format('Y-m-d H:i:s'),
                    $lastObjectSyncDateTime ? $lastObjectSyncDateTime->format('Y-m-d H:i:s') : 'null',
                    $integrationObjectName,
                    count($integrationObjectFields)
                ),
                self::class.':'.__FUNCTION__
            );

            $integrationRequestObject = new RequestObjectDAO(
                $integrationObjectName,
                $objectSyncFromDateTime,
                $objectSyncToDateTime,
                $lastObjectSyncDateTime
            );

            foreach ($integrationObjectFields as $integrationObjectField) {
                $integrationRequestObject->addField($integrationObjectField);
            }

            $integrationRequestObject->setRequiredFields($this->mappingManualDAO->getIntegrationObjectRequiredFieldNames($integrationObjectName));

            $integrationRequestDAO->addObject($integrationRequestObject);
        }

        return $integrationRequestDAO->shouldSync()
            ? $this->syncDataExchange->getSyncReport($integrationRequestDAO)
            :
            new ReportDAO($this->mappingManualDAO->getIntegration());
    }

    /**
     * @throws ObjectNotFoundException
     */
    public function getSyncOrder(ReportDAO $syncReport): OrderDAO
    {
        $syncOrder = new OrderDAO($this->syncDateHelper->getSyncDateTime(), $this->inputOptionsDAO->isFirstTimeSync(), $this->mappingManualDAO->getIntegration(), $this->inputOptionsDAO->getOptions());

        $internalObjectNames = $this->mappingManualDAO->getInternalObjectNames();
        foreach ($internalObjectNames as $internalObjectName) {
            $internalObjects              = $syncReport->getObjects($internalObjectName);
            $mappedIntegrationObjectNames = $this->mappingManualDAO->getMappedIntegrationObjectsNames($internalObjectName);

            foreach ($mappedIntegrationObjectNames as $mappedIntegrationObjectName) {
                $objectMapping = $this->mappingManualDAO->getObjectMapping($internalObjectName, $mappedIntegrationObjectName);
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    sprintf(
                        'Mautic to integration; syncing %d objects for the %s object mapped to the %s integration object',
                        count($internalObjects),
                        $internalObjectName,
                        $mappedIntegrationObjectName
                    ),
                    self::class.':'.__FUNCTION__
                );

                foreach ($internalObjects as $internalObject) {
                    try {
                        $integrationObject = $this->mappingHelper->findIntegrationObject(
                            $this->mappingManualDAO->getIntegration(),
                            $mappedIntegrationObjectName,
                            $internalObject
                        );

                        $objectChange = $this->objectChangeGenerator->getSyncObjectChange(
                            $syncReport,
                            $this->mappingManualDAO,
                            $objectMapping,
                            $internalObject,
                            $integrationObject
                        );

                        if ($objectChange->shouldSync()) {
                            $syncOrder->addObjectChange($objectChange);
                        }
                    } catch (ObjectDeletedException) {
                        DebugLogger::log(
                            $this->mappingManualDAO->getIntegration(),
                            sprintf(
                                "Mautic to integration; Mautic's %s:%s object was deleted from the integration so don't try to sync",
                                $internalObject->getObject(),
                                $internalObject->getObjectId()
                            ),
                            self::class.':'.__FUNCTION__
                        );

                        continue;
                    }
                }
            }
        }

        return $syncOrder;
    }
}
