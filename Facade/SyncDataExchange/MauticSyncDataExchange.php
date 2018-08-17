<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Facade\SyncDataExchange;

use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Report\FieldDAO AS ReportFieldDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Report\ObjectDAO AS ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Order\ObjectChangeDAO AS OrderObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\DAO\Value\EncodedValueDAO;
use MauticPlugin\IntegrationsBundle\Entity\FieldChange;
use MauticPlugin\IntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\IntegrationsBundle\Facade\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Helpers\SyncJudge\SyncJudgeInterface;
use MauticPlugin\IntegrationsBundle\Helpers\VariableExpressor\VariableExpresserHelperInterface;

/**
 * Class MauticSyncDataExchange
 */
class MauticSyncDataExchange implements SyncDataExchangeInterface
{
    const NAME = 'mautic';
    const CONTACT_OBJECT = 'lead'; // kept as lead for BC

    /**
     * @var SyncJudgeInterface
     */
    private $syncJudge;

    /**
     * @var FieldChangeRepository
     */
    private $fieldChangeRepository;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var VariableExpresserHelperInterface
     */
    private $variableExpresserHelper;

    /**
     * MauticSyncDataExchange constructor.
     *
     * @param FieldChangeRepository            $fieldChangeRepository
     * @param LeadRepository                   $leadRepository
     * @param VariableExpresserHelperInterface $variableExpresserHelper
     */
    public function __construct(
        SyncJudgeInterface $syncJudge,
        FieldChangeRepository $fieldChangeRepository,
        LeadRepository $leadRepository,
        LeadModel $leadModel,
        VariableExpresserHelperInterface $variableExpresserHelper
    ) {
        $this->syncJudge               = $syncJudge;
        $this->fieldChangeRepository   = $fieldChangeRepository;
        $this->leadRepository          = $leadRepository;
        $this->leadModel = $leadModel;
        $this->variableExpresserHelper = $variableExpresserHelper;
    }

    /**
     * @param RequestDAO $requestDAO
     *
     * @return ReportDAO
     */
    public function getSyncReport(RequestDAO $requestDAO)
    {
        $requestedObjects = $requestDAO->getObjects();
        $syncReport       = new ReportDAO(self::NAME);

        foreach ($requestedObjects as $objectDAO) {
            $fieldsChanges = $this->fieldChangeRepository->findChangesAfter(
                $objectDAO->getObject(),
                $requestDAO->getFromDateTime()
            );

            $reportObjects = [];
            foreach ($fieldsChanges as $fieldChange) {
                $object = $fieldChange['object'];
                $objectId = $fieldChange['object_id'];

                if (!array_key_exists($object, $reportObjects)) {
                    $reportObjects[$object] = [];
                }

                if (!array_key_exists($objectId, $reportObjects[$object])) {
                    $reportObjects[$object][$objectId] = new ReportObjectDAO($object, $objectId);
                }

                /** @var ReportObjectDAO $reportObjectDAO */
                $reportObjectDAO = $reportObjects[$object][$objectId];

                $changeTimestamp = new \DateTimeImmutable($fieldChange['modified_at'], new \DateTimeZone('UTC'));
                $columnType      = $fieldChange['column_type'];
                $columnValue     = $fieldChange['column_value'];
                $newValue        = $this->variableExpresserHelper->decodeVariable(new EncodedValueDAO($columnType, $columnValue));

                $reportFieldDAO = new ReportFieldDAO($fieldChange['column_name'], $newValue);
                $reportFieldDAO->setChangeDateTime($changeTimestamp);

                $reportObjectDAO->addField($reportFieldDAO);
            }

            foreach ($reportObjects as $objectsDAO) {
                foreach ($objectsDAO as $reportObjectDAO) {
                    $syncReport->addObject($reportObjectDAO);
                }
            }
        }

        return $syncReport;
    }

    /**
     * @param OrderDAO $syncOrderDAO
     *
     * @throws \Doctrine\ORM\ORMException
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO)
    {
//        $objectChanges         = $syncOrderDAO->getIdentifiedObjects();
//        $chunkedObjectsChanges = [];
//        $objectsIds            = [];
//        foreach ($objectChanges as $objectChange) {
//            $objectName = $objectChange->getObject();
//            if (!array_key_exists($objectName, $objectsIds)) {
//                $objectsIds[$objectName]            = [];
//                $chunkedObjectsChanges[$objectName] = [];
//            }
//            $objectsIds[$objectName][]                                        = $objectChange->getObjectId();
//            $chunkedObjectsChanges[$objectName][$objectChange->getObjectId()] = $objectChange;
//        }
//        foreach ($objectsIds as $objectName => $ids) {
//            switch ($objectName) {
//                case 'lead':
//                    $leads = $this->leadRepository->findByIds($ids);
//                    /** @var Lead $lead */
//                    foreach ($leads as $lead) {
//                        /** @var OrderObjectChangeDAO $objectChange */
//                        $objectChange  = $chunkedObjectsChanges[$objectName][$lead->getId()];
//                        $fieldsChanges = $objectChange->getFields();
//                        foreach ($fieldsChanges as $fieldsChange) {
//                            $lead->addUpdatedField($fieldsChange->getName(), $fieldsChange->getValue());
//                        }
//                        $this->em->persist($lead);
//                    }
//            }
//        }
    }

    public function saveObjectRelationships(array $mappings)
    {

    }


//
//    private function generateSyncOrders()
//    {
//        $this->internalSyncOrder    = new OrderDAO($this->syncDateTime);
//        $this->integrationSyncOrder = new OrderDAO($this->syncDateTime);
//
//        $internalObjectsNames = $this->mappingManualDAO->getInternalObjectsNames();
//
//        // @todo convert to a factory/service/interface that is passed through SyncProcessFactory into this class
//        // @todo find matches based on Mautic unique identifiers
//        $matchDetectors = [
//            MauticSyncDataExchange::CONTACT_OBJECT => new class
//            {
//                public function isDuplicate(
//                    MappingManualDAO $mappingManualDAO,
//                    ReportObjectDAO $internalObjectDAO,
//                    ReportObjectDAO $integrationObjectDAO
//                ) {
//                    $internalEmail         = $internalObjectDAO->getField('email')->getValue()->getNormalizedValue();
//                    $integrationEmailField = $mappingManualDAO->getIntegrationMappedField(
//                        MauticSyncDataExchange::CONTACT_OBJECT,
//                        $integrationObjectDAO->getObject(),
//                        'email'
//                    );
//                    $integrationEmail      = $internalObjectDAO->getField($integrationEmailField)->getValue()->getNormalizedValue();
//
//                    return strtolower($internalEmail) === strtolower($integrationEmail);
//                }
//            }
//        ];
//
//        foreach ($internalObjectsNames as $internalObjectName) {
//            $internalObjects               = $this->internalSyncReport->getObjects($internalObjectName);
//            $mappedIntegrationObjectsNames = $this->mappingManualDAO->getMappedIntegrationObjectsNames($internalObjectName);
//            foreach ($mappedIntegrationObjectsNames as $mappedIntegrationObjectName) {
//                $objectMappingDAO   = $this->mappingManualDAO->getObjectMapping($internalObjectName, $mappedIntegrationObjectName);
//                $integrationObjects = $integrationSyncReport->getObjects($mappedIntegrationObjectName);
//                do {
//                    reset($integrationObjects);
//                    /** @var ReportObjectDAO $comparedInternalObject */
//                    $comparedInternalObject = current($internalObjects);
//                    $mappedIntegrationId    = $objectMappingDAO->getMappedIntegrationObjectId($comparedInternalObject->getObjectId());
//                    if ($mappedIntegrationId !== null) {
//                        $comparedIntegrationObject = $integrationSyncReport->getObject($mappedIntegrationObjectName, $mappedIntegrationId);
//                        $this->orderObjectsSync($objectMappingDAO, $comparedInternalObject, $comparedIntegrationObject);
//
//                        continue;
//                    }
//
//                    if (array_key_exists($internalObjectName, $matchDetectors)) {
//                        $duplicityDetector = $matchDetectors[$internalObjectName];
//                        $matches           = [];
//                        do {
//                            /** @var ReportObjectDAO $comparedIntegrationObject */
//                            $comparedIntegrationObject = current($integrationObjects);
//                            $mappedInternalId          = $objectMappingDAO->getMappedInternalObjectId($comparedIntegrationObject->getObjectId());
//                            if ($mappedInternalId === null) {
//                                $isDuplicate = $duplicityDetector->isDuplicate(
//                                    $this->mappingManualDAO,
//                                    $comparedInternalObject,
//                                    $comparedIntegrationObject
//                                );
//                                if ($isDuplicate) {
//                                    $matches[] = $comparedIntegrationObject;
//                                }
//                            }
//                        } while (next($integrationObjects) !== false);
//
//                        if (count($matches) === 0) {
//                            // No matches so continue
//                            continue;
//                        }
//
//                        $integrationObjectMatch = reset($matches);
//                        $objectMappingDAO->mapIds($comparedInternalObject->getObjectId(), $integrationObjectMatch->getObjectId());
//                        $this->orderObjectsSync($objectMappingDAO, $comparedInternalObject, $integrationObjectMatch);
//
//                        continue;
//                    }
//
//                    // Add internal object to integration sync order
//                    $this->addToIntegrationSyncOrder($objectMappingDAO, $comparedInternalObject);
//                } while (next($internalObjects) !== false);
//            }
//        }
//
//        $integrationObjectsNames = $this->mappingManualDAO->getIntegrationObjectsNames();
//        foreach ($integrationObjectsNames as $integrationObjectName) {
//            $integrationObjects         = $integrationSyncReport->getObjects($integrationObjectName);
//            $mappedInternalObjectsNames = $this->mappingManualDAO->getMappedInternalObjectsNames($integrationObjectName);
//            foreach ($mappedInternalObjectsNames as $mappedInternalObjectName) {
//                $objectMappingDAO = $this->mappingManualDAO->getObjectMapping($mappedInternalObjectName, $integrationObjectName);
//                foreach ($integrationObjects as $integrationObject) {
//                    $mappedInternalObjectId = $objectMappingDAO->getMappedInternalObjectId($integrationObject->getObjectId());
//                    if ($mappedInternalObjectId !== null) {
//                        continue;
//                    }
//
//                    // Object is new in integration and not matched
//                    $this->addToInternalSyncOrder($objectMappingDAO, $integrationObject);
//                }
//            }
//        }
//    }
//
//    /**
//     * @param ObjectMappingDAO     $objectMappingDAO
//     * @param ReportObjectDAO|null $internalObjectDAO
//     * @param ReportObjectDAO|null $integrationObjectDAO
//     */
//    private function orderObjectsSync(
//        ObjectMappingDAO $objectMappingDAO,
//        ReportObjectDAO $internalObjectDAO,
//        ReportObjectDAO $integrationObjectDAO
//    ) {
//        $integrationObjectChange = new ObjectChangeDAO(
//            $objectMappingDAO->getIntegrationObjectName(),
//            $integrationObjectDAO->getObjectId(),
//            $internalObjectDAO->getObject(),
//            $internalObjectDAO->getObjectId()
//        );
//
//        $internalObjectChange = new ObjectChangeDAO(
//            $objectMappingDAO->getInternalObjectName(),
//            $internalObjectDAO->getObjectId(),
//            $integrationObjectDAO->getObject(),
//            $integrationObjectDAO->getObjectId()
//        );
//
//        /** @var FieldMappingDAO[] $fieldMappings */
//        $fieldMappings = $objectMappingDAO->getFieldMappings();
//        foreach ($fieldMappings as $fieldMappingDAO) {
//            $internalInformationChangeRequest = $this->internalSyncReport->getInformationChangeRequest(
//                $objectMappingDAO->getInternalObjectName(),
//                $internalObjectDAO->getObjectId(),
//                $fieldMappingDAO->getInternalField()
//            );
//
//            $integrationInformationChangeRequest = $integrationSyncReport->getInformationChangeRequest(
//                $objectMappingDAO->getIntegrationObjectName(),
//                $integrationObjectDAO->getObjectId(),
//                $fieldMappingDAO->getIntegrationField()
//            );
//
//            // Perform the sync in the direction instructed
//            switch ($fieldMappingDAO->getSyncDirection()) {
//                case ObjectMappingDAO::SYNC_TO_MAUTIC:
//                    $internalFieldChange = new FieldDAO($fieldMappingDAO->getInternalField(), $integrationInformationChangeRequest->getNewValue());
//                    $internalObjectChange->addField($internalFieldChange);
//
//                    break;
//                case ObjectMappingDAO::SYNC_TO_INTEGRATION:
//                    $integrationFieldChange = new FieldDAO($fieldMappingDAO->getIntegrationField(), $internalInformationChangeRequest->getNewValue());
//                    $integrationObjectChange->addField($integrationFieldChange);
//
//                    break;
//                case ObjectMappingDAO::SYNC_BIDIRECTIONALLY:
//                    $judgeModes = [
//                        SyncJudgeInterface::PRESUMPTION_OF_INNOCENCE_MODE,
//                        SyncJudgeInterface::BEST_EVIDENCE_MODE
//                    ];
//                    foreach ($judgeModes as $judgeMode) {
//                        try {
//                            $result              = $this->syncJudgeService->adjudicate(
//                                $judgeMode,
//                                $internalInformationChangeRequest,
//                                $integrationInformationChangeRequest
//                            );
//                            $internalFieldChange = new FieldDAO($fieldMappingDAO->getInternalField(), $result);
//                            $internalObjectChange->addField($internalFieldChange);
//                            $integrationFieldChange = new FieldDAO($fieldMappingDAO->getIntegrationField(), $result);
//                            $integrationObjectChange->addField($integrationFieldChange);
//                            break;
//                        } catch (\LogicException $ex) {
//                            continue;
//                        }
//                    }
//            }
//        }
//
//        $this->internalSyncOrder->addObjectChange($internalObjectChange);
//        $this->integrationSyncOrder->addObjectChange($integrationObjectChange);
//    }
}
