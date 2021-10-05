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

namespace Mautic\IntegrationsBundle\Sync\SyncProcess\Direction\Internal;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\ObjectDAO as RequestObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectDeletedException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use Mautic\IntegrationsBundle\Sync\Helper\SyncDateHelper;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class MauticSyncProcess
{
    /**
     * @var SyncDateHelper
     */
    private $syncDateHelper;

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
     * @var MauticSyncDataExchange
     */
    private $syncDataExchange;

    public function __construct(SyncDateHelper $syncDateHelper, ObjectChangeGenerator $objectChangeGenerator)
    {
        $this->syncDateHelper        = $syncDateHelper;
        $this->objectChangeGenerator = $objectChangeGenerator;
    }

    public function setupSync(InputOptionsDAO $inputOptionsDAO, MappingManualDAO $mappingManualDAO, MauticSyncDataExchange $syncDataExchange): void
    {
        $this->inputOptionsDAO  = $inputOptionsDAO;
        $this->mappingManualDAO = $mappingManualDAO;
        $this->syncDataExchange = $syncDataExchange;
    }

    /**
     * @return ReportDAO
     *
     * @throws ObjectNotFoundException
     */
    public function getSyncReport(int $syncIteration)
    {
        $internalRequestDAO = new RequestDAO($this->mappingManualDAO->getIntegration(), $syncIteration, $this->inputOptionsDAO);

        $internalObjectsNames = $this->mappingManualDAO->getInternalObjectNames();
        foreach ($internalObjectsNames as $internalObjectName) {
            $internalObjectFields = $this->mappingManualDAO->getInternalObjectFieldsToSyncToIntegration($internalObjectName);
            if (0 === count($internalObjectFields)) {
                // No fields configured for a sync
                DebugLogger::log(
                    $this->mappingManualDAO->getIntegration(),
                    sprintf(
                        'Mautic to integration; there are no fields for the %s object',
                        $internalObjectName
                    ),
                    __CLASS__.':'.__FUNCTION__
                );

                continue;
            }

            $objectSyncFromDateTime = $this->syncDateHelper->getSyncFromDateTime(MauticSyncDataExchange::NAME, $internalObjectName);
            $objectSyncToDateTime   = $this->syncDateHelper->getSyncToDateTime();
            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    'Mautic to integration; syncing from %s to %s for the %s object with %d fields',
                    $objectSyncFromDateTime->format('Y-m-d H:i:s'),
                    $objectSyncToDateTime->format('Y-m-d H:i:s'),
                    $internalObjectName,
                    count($internalObjectFields)
                ),
                __CLASS__.':'.__FUNCTION__
            );

            $internalRequestObject  = new RequestObjectDAO($internalObjectName, $objectSyncFromDateTime, $objectSyncToDateTime);
            foreach ($internalObjectFields as $internalObjectField) {
                $internalRequestObject->addField($internalObjectField);
            }

            // Set required fields for easy access; mainly for Mautic
            $internalRequestObject->setRequiredFields($this->mappingManualDAO->getInternalObjectRequiredFieldNames($internalObjectName));
            $internalRequestDAO->addObject($internalRequestObject);
        }

        return $internalRequestDAO->shouldSync()
            ? $this->syncDataExchange->getSyncReport($internalRequestDAO)
            :
            new ReportDAO(MauticSyncDataExchange::NAME);
    }

    /**
     * @return OrderDAO
     *
     * @throws ObjectNotFoundException
     * @throws ObjectNotSupportedException
     */
    public function getSyncOrder(ReportDAO $syncReport)
    {
        $syncOrder = new OrderDAO($this->syncDateHelper->getSyncDateTime(), $this->inputOptionsDAO->isFirstTimeSync(), $this->mappingManualDAO->getIntegration(), $this->inputOptionsDAO->getOptions());

        $integrationObjectsNames = $this->mappingManualDAO->getIntegrationObjectNames();
        foreach ($integrationObjectsNames as $integrationObjectName) {
            $integrationObjects         = $syncReport->getObjects($integrationObjectName);
            $mappedInternalObjectsNames = $this->mappingManualDAO->getMappedInternalObjectsNames($integrationObjectName);

            DebugLogger::log(
                $this->mappingManualDAO->getIntegration(),
                sprintf(
                    'Integration to Mautic; found %d objects for the %s object mapped to the %s Mautic object(s)',
                    count($integrationObjects),
                    $integrationObjectName,
                    implode(', ', $mappedInternalObjectsNames)
                ),
                __CLASS__.':'.__FUNCTION__
            );

            foreach ($mappedInternalObjectsNames as $mappedInternalObjectName) {
                $objectMapping = $this->mappingManualDAO->getObjectMapping($mappedInternalObjectName, $integrationObjectName);
                foreach ($integrationObjects as $integrationObject) {
                    try {
                        $internalObject = $this->syncDataExchange->getConflictedInternalObject(
                            $this->mappingManualDAO,
                            $mappedInternalObjectName,
                            $integrationObject
                        );
                        $objectChange   = $this->objectChangeGenerator->getSyncObjectChange(
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
                                'Integration to Mautic; the %s object with ID %s is marked deleted and thus not synced',
                                $integrationObject->getObject(),
                                $integrationObject->getObjectId()
                            ),
                            __CLASS__.':'.__FUNCTION__
                        );
                    }
                }
            }
        }

        return $syncOrder;
    }
}
