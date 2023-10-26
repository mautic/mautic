<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\SyncDataExchange;

use Mautic\IntegrationsBundle\Entity\FieldChangeRepository;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO as ReportObjectDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectDeletedException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;
use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotSupportedException;
use Mautic\IntegrationsBundle\Sync\Helper\MappingHelper;
use Mautic\IntegrationsBundle\Sync\Logger\DebugLogger;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Helper\FieldHelper;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Executioner\OrderExecutioner;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\FullObjectReportBuilder;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\ReportBuilder\PartialObjectReportBuilder;

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

    public function getSyncReport(RequestDAO $requestDAO): ReportDAO
    {
        if ($requestDAO->isFirstTimeSync() || $requestDAO->getInputOptionsDAO()->getMauticObjectIds()) {
            return $this->fullObjectReportBuilder->buildReport($requestDAO);
        }

        return $this->partialObjectReportBuilder->buildReport($requestDAO);
    }

    public function executeSyncOrder(OrderDAO $syncOrderDAO): void
    {
        $this->orderExecutioner->execute($syncOrderDAO);
    }

    /**
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

        if (!$internalObjectDAO->getObjectId()) {
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
