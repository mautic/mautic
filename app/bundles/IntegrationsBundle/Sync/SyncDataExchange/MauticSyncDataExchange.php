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

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange;

use MauticPlugin\IntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectDeletedException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use MauticPlugin\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use MauticPlugin\IntegrationsBundle\Sync\Helper\MappingHelper;
use MauticPlugin\IntegrationsBundle\Sync\Logger\DebugLogger;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\OrderExecutioner;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FullObjectReportBuilder;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\PartialObjectReportBuilder;

class MauticSyncDataExchange implements SyncDataExchangeInterface
{
    const NAME           = 'mautic';
    const OBJECT_CONTACT = 'lead'; // kept as lead for BC
    const OBJECT_COMPANY = 'company';

    /**
     * @var FieldChangeRepository
     */
    private $fieldChangeRepository;

    /**
     * @var FieldHelper
     */
    private $fieldHelper;

    /**
     * @var MappingHelper
     */
    private $mappingHelper;

    /**
     * @var FullObjectReportBuilder
     */
    private $fullObjectReportBuilder;

    /**
     * @var PartialObjectReportBuilder
     */
    private $partialObjectReportBuilder;

    /**
     * @var OrderExecutioner
     */
    private $orderExecutioner;

    /**
     * @param FieldChangeRepository      $fieldChangeRepository
     * @param FieldHelper                $fieldHelper
     * @param MappingHelper              $mappingHelper
     * @param FullObjectReportBuilder    $fullObjectReportBuilder
     * @param PartialObjectReportBuilder $partialObjectReportBuilder
     * @param OrderExecutioner           $orderExecutioner
     */
    public function __construct(
        FieldChangeRepository $fieldChangeRepository,
        FieldHelper $fieldHelper,
        MappingHelper $mappingHelper,
        FullObjectReportBuilder $fullObjectReportBuilder,
        PartialObjectReportBuilder $partialObjectReportBuilder,
        OrderExecutioner $orderExecutioner
    ) {
        $this->fieldChangeRepository      = $fieldChangeRepository;
        $this->fieldHelper                = $fieldHelper;
        $this->mappingHelper              = $mappingHelper;
        $this->fullObjectReportBuilder    = $fullObjectReportBuilder;
        $this->partialObjectReportBuilder = $partialObjectReportBuilder;
        $this->orderExecutioner           = $orderExecutioner;
    }

    /**
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     */
    public function getSyncReport(RequestDAO $requestDAO): ReportDAO
    {
        if ($requestDAO->isFirstTimeSync() || $requestDAO->getInputOptionsDAO()->getMauticObjectIds()) {
            return $this->fullObjectReportBuilder->buildReport($requestDAO);
        }

        return $this->partialObjectReportBuilder->buildReport($requestDAO);
    }

    /**
     * @param OrderDAO $syncOrderDAO
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO): void
    {
        $this->orderExecutioner->execute($syncOrderDAO);
    }

    /**
     * @param MappingManualDAO $mappingManualDAO
     * @param string           $internalObjectName
     * @param ReportObjectDAO  $integrationObjectDAO
     *
     * @return ReportObjectDAO
     *
     * @throws ObjectNotFoundException
     * @throws ObjectNotSupportedException
     * @throws ObjectDeletedException
     */
    public function getConflictedInternalObject(MappingManualDAO $mappingManualDAO, string $internalObjectName, ReportObjectDAO $integrationObjectDAO)
    {
        // Check to see if we have a match
        $internalObjectDAO = $this->mappingHelper->findMauticObject($mappingManualDAO, $internalObjectName, $integrationObjectDAO);

        if (!$internalObjectDAO) {
            return new ReportObjectDAO($internalObjectName, null);
        }

        $fieldChanges = $this->fieldChangeRepository->findChangesForObject(
            $mappingManualDAO->getIntegration(),
            $this->mappingHelper->getMauticEntityClassName($internalObjectName),
            $internalObjectDAO->getObjectId()
        );

        foreach ($fieldChanges as $fieldChange) {
            $internalObjectDAO->addField(
                $this->fieldHelper->getFieldChangeObject($fieldChange)
            );
        }

        return $internalObjectDAO;
    }

    /**
     * @param ObjectChangeDAO[] $objectChanges
     */
    public function cleanupProcessedObjects(array $objectChanges): void
    {
        foreach ($objectChanges as $changedObjectDAO) {
            try {
                $object   = $this->fieldHelper->getFieldObjectName($changedObjectDAO->getMappedObject());
                $objectId = $changedObjectDAO->getMappedObjectId();

                $this->fieldChangeRepository->deleteEntitiesForObject((int) $objectId, $object, $changedObjectDAO->getIntegration());
            } catch (ObjectNotSupportedException $exception) {
                DebugLogger::log(
                    self::NAME,
                    $exception->getMessage(),
                    __CLASS__.':'.__FUNCTION__
                );
            }
        }
    }
}
