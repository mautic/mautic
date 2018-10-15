<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner;


use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\CompanyObjectHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ObjectHelper\ContactObjectHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\MauticSyncDataExchange;

class OrderExecutioner
{
    /**
     * @var MappingHelper
     */
    private $mappingHelper;

    /**
     * @var ContactObjectHelper
     */
    private $contactObjectHelper;

    /**
     * @var CompanyObjectHelper
     */
    private $companyObjectHelper;

    /**
     * @var OrderDAO
     */
    private $syncOrder;

    /**
     * OrderExecutioner constructor.
     *
     * @param MappingHelper       $mappingHelper
     * @param ContactObjectHelper $contactObjectHelper
     * @param CompanyObjectHelper $companyObjectHelper
     */
    public function __construct(MappingHelper $mappingHelper, ContactObjectHelper $contactObjectHelper, CompanyObjectHelper $companyObjectHelper)
    {
        $this->mappingHelper       = $mappingHelper;
        $this->contactObjectHelper = $contactObjectHelper;
        $this->companyObjectHelper = $companyObjectHelper;
    }

    /**
     * @param OrderDAO $syncOrderDAO
     */
    public function execute(OrderDAO $syncOrderDAO)
    {
        $this->syncOrder = $syncOrderDAO;

        $identifiedObjects = $syncOrderDAO->getIdentifiedObjects();
        foreach ($identifiedObjects as $objectName => $updateObjects) {
            try {
                $this->updateObjects($objectName, $updateObjects);
            } catch (ObjectNotSupportedException $exception) {
                DebugLogger::log(
                    MauticSyncDataExchange::NAME,
                    $exception->getMessage(),
                    __CLASS__.':'.__FUNCTION__
                );
            }
        }

        $unidentifiedObjects = $syncOrderDAO->getUnidentifiedObjects();
        foreach ($unidentifiedObjects as $objectName => $createObjects) {
            try {
                $this->createObjects($objectName, $createObjects);
            } catch (ObjectNotSupportedException $exception) {
                DebugLogger::log(
                    MauticSyncDataExchange::NAME,
                    $exception->getMessage(),
                    __CLASS__.':'.__FUNCTION__
                );
            }
        }
    }

    /**
     * @param string $objectName
     * @param        $updateObjects
     *
     * @throws ObjectNotSupportedException
     */
    private function updateObjects(string $objectName, array $updateObjects)
    {
        $updateCount = count($updateObjects);
        DebugLogger::log(
            MauticSyncDataExchange::NAME,
            sprintf(
                "Updating %d %s object(s)",
                $updateCount,
                $objectName
            ),
            __CLASS__.':'.__FUNCTION__
        );

        if (0 === $updateCount) {
            return;
        }

        $identifiedObjectIds = $this->syncOrder->getIdentifiedObjectIds($objectName);

        switch ($objectName) {
            case MauticSyncDataExchange::OBJECT_CONTACT:
                $updatedObjectMappings = $this->contactObjectHelper->update($identifiedObjectIds, $updateObjects);
                break;
            case MauticSyncDataExchange::OBJECT_COMPANY:
                $updatedObjectMappings = $this->companyObjectHelper->update($identifiedObjectIds, $updateObjects);
                break;
            default:
                throw new ObjectNotSupportedException(MauticSyncDataExchange::NAME, $objectName);
        }

        $this->mappingHelper->updateObjectMappings($updatedObjectMappings);
    }

    /**
     * @param string $objectName
     * @param array  $createObjects
     *
     * @throws ObjectNotSupportedException
     */
    private function createObjects(string $objectName, array $createObjects)
    {
        $createCount = count($createObjects);

        DebugLogger::log(
            MauticSyncDataExchange::NAME,
            sprintf(
                "Creating %d %s object(s)",
                $createCount,
                $objectName
            ),
            __CLASS__.':'.__FUNCTION__
        );

        if (0 === $createCount) {
            return;
        }

        switch ($objectName) {
            case MauticSyncDataExchange::OBJECT_CONTACT:
                $objectMappings = $this->contactObjectHelper->create($createObjects);
                break;
            case MauticSyncDataExchange::OBJECT_COMPANY:
                $objectMappings = $this->companyObjectHelper->create($createObjects);
                break;
            default:
                throw new ObjectNotSupportedException(MauticSyncDataExchange::NAME, $objectName);
        }

        $this->mappingHelper->saveObjectMappings($objectMappings);
    }
}