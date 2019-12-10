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

use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectDeletedException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;

class IntegrationSyncProcess
{
    /**
     * @var SyncDateHelper
     */
    private $syncDateHelper;

    /**
     * @var MappingHelper
     */
    private $mappingHelper;

    /**
     * @var ObjectChangeGenerator
     */
    private $objectChangeGenerator;

    /**
     * @var InputOptionsDAO
     */
    private $inputOptionsDAO;

    /**
     * @var MappingManualDAO
     */
    private $mappingManualDAO;

    /**
     * @var SyncDataExchangeInterface
     */
    private $syncDataExchange;

    /**
     * @param SyncDateHelper        $syncDateHelper
     * @param MappingHelper         $mappingHelper
     * @param ObjectChangeGenerator $objectChangeGenerator
     */
    public function __construct(SyncDateHelper $syncDateHelper, MappingHelper $mappingHelper, ObjectChangeGenerator $objectChangeGenerator)
    {
        $this->syncDateHelper        = $syncDateHelper;
        $this->mappingHelper         = $mappingHelper;
        $this->objectChangeGenerator = $objectChangeGenerator;
    }

    /**
     * @param InputOptionsDAO           $inputOptionsDAO
     * @param MappingManualDAO          $mappingManualDAO
     * @param SyncDataExchangeInterface $syncDataExchange
     */
    public function setupSync(InputOptionsDAO $inputOptionsDAO, MappingManualDAO $mappingManualDAO, SyncDataExchangeInterface $syncDataExchange): void
    {
        $this->inputOptionsDAO  = $inputOptionsDAO;
        $this->mappingManualDAO = $mappingManualDAO;
        $this->syncDataExchange = $syncDataExchange;
    }

    /**
     * @param int $syncIteration
     *
     * @return ReportDAO
     *
     * @throws ObjectNotFoundException
     */
    public function getSyncReport(int $syncIteration)
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
                    __CLASS__.':'.__FUNCTION__
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
                __CLASS__.':'.__FUNCTION__
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
     * @param ReportDAO $syncReport
     *
     * @return OrderDAO
     *
     * @throws ObjectNotFoundException
     */
    public function getSyncOrder(ReportDAO $syncReport)
    {
        $syncOrder = new OrderDAO($this->syncDateHelper->getSyncDateTime(), $this->inputOptionsDAO->isFirstTimeSync(), $this->mappingManualDAO->getIntegration());

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
                    __CLASS__.':'.__FUNCTION__
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
                    } catch (ObjectDeletedException $exception) {
                        DebugLogger::log(
                            $this->mappingManualDAO->getIntegration(),
                            sprintf(
                                "Mautic to integration; Mautic's %s:%s object was deleted from the integration so don't try to sync",
                                $internalObject->getObject(),
                                $internalObject->getObjectId()
                            ),
                            __CLASS__.':'.__FUNCTION__
                        );

                        continue;
                    }
                }
            }
        }

        return $syncOrder;
    }
}
