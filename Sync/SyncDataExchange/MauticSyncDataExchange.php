<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange;

use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\IntegrationBundle\Sync\Duplicity\DuplicityFinder;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO AS ReportFieldDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO AS ReportObjectDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ReportDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\ObjectChangeDAO AS OrderObjectChangeDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order\OrderDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Request\RequestDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Value\EncodedValueDAO;
use MauticPlugin\IntegrationsBundle\Entity\FieldChange;
use MauticPlugin\IntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\IntegrationsBundle\Sync\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\IntegrationsBundle\Sync\SyncJudge\SyncJudgeInterface;
use MauticPlugin\IntegrationsBundle\Sync\ValueNormalizer\ValueNormalizer;
use MauticPlugin\IntegrationsBundle\Sync\VariableExpresser\VariableExpresserHelperInterface;

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
     * @var DuplicityFinder
     */
    private $duplicityFinder;

    /**
     * MauticSyncDataExchange constructor.
     *
     * @param SyncJudgeInterface               $syncJudge
     * @param FieldChangeRepository            $fieldChangeRepository
     * @param LeadRepository                   $leadRepository
     * @param LeadModel                        $leadModel
     * @param VariableExpresserHelperInterface $variableExpresserHelper
     * @param DuplicityFinder                  $duplicityFinder
     */
    public function __construct(
        SyncJudgeInterface $syncJudge,
        FieldChangeRepository $fieldChangeRepository,
        LeadRepository $leadRepository,
        LeadModel $leadModel,
        VariableExpresserHelperInterface $variableExpresserHelper,
        DuplicityFinder $duplicityFinder
    ) {
        $this->syncJudge               = $syncJudge;
        $this->fieldChangeRepository   = $fieldChangeRepository;
        $this->leadRepository          = $leadRepository;
        $this->leadModel               = $leadModel;
        $this->variableExpresserHelper = $variableExpresserHelper;
        $this->duplicityFinder         = $duplicityFinder;
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
                $objectDAO->getFromDateTime()
            );

            $reportObjects = [];
            foreach ($fieldsChanges as $fieldChange) {
                $object   = $fieldChange['object'];
                $objectId = $fieldChange['object_id'];

                if (!array_key_exists($object, $reportObjects)) {
                    $reportObjects[$object] = [];
                }

                if (!array_key_exists($objectId, $reportObjects[$object])) {
                    $reportObjects[$object][$objectId] = new ReportObjectDAO($object, $objectId);
                }

                /** @var ReportObjectDAO $reportObjectDAO */
                $reportObjectDAO = $reportObjects[$object][$objectId];

                $reportObjectDAO->addField(
                    $this->getFieldChangeObject($fieldChange)
                );

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

    /**
     * @param MappingManualDAO $mappingManualDAO
     * @param string           $internalObjectName
     * @param ReportObjectDAO        $integrationObjectDAO
     *
     * @return ReportObjectDAO
     * @throws \Doctrine\ORM\ORMException
     */
    public function getConflictedInternalObject(MappingManualDAO $mappingManualDAO, string $internalObjectName, ReportObjectDAO $integrationObjectDAO)
    {
        // Check to see if we have a match
        $internalObjectDAO = $this->duplicityFinder->findMauticObject($mappingManualDAO, $internalObjectName, $integrationObjectDAO);

        if (!$internalObjectDAO) {
            return new ReportObjectDAO($internalObjectName, null);
        }

        $fieldChanges = $this->fieldChangeRepository->findChangesForObject($internalObjectName, $internalObjectDAO->getObjectId());
        foreach ($fieldChanges as $fieldChange) {
            $internalObjectDAO->addField(
                $this->getFieldChangeObject($fieldChange)
            );
        }

        return $internalObjectDAO;
    }

    /**
     * @param string          $integrationObjectName
     * @param ReportObjectDAO $internalObjectDAO
     *
     * @return ReportObjectDAO
     */
    public function getMappedIntegrationObject($integrationObjectName, ReportObjectDAO $internalObjectDAO)
    {
        $integrationObject = $this->duplicityFinder->findIntegrationObject($integrationObjectName, $internalObjectDAO);

        if ($integrationObject) {
            return $integrationObject;
        }

        return new ReportObjectDAO($integrationObjectName, null);
    }

    /**
     * @param array $mappings
     */
    public function saveObjectMappings(array $mappings)
    {

    }

    /**
     * @param array $fieldChange
     *
     * @return ReportFieldDAO
     */
    private function getFieldChangeObject(array $fieldChange)
    {
        $changeTimestamp = new \DateTimeImmutable($fieldChange['modified_at'], new \DateTimeZone('UTC'));
        $columnType      = $fieldChange['column_type'];
        $columnValue     = $fieldChange['column_value'];
        $newValue        = $this->variableExpresserHelper->decodeVariable(new EncodedValueDAO($columnType, $columnValue));

        $reportFieldDAO = new ReportFieldDAO($fieldChange['column_name'], $newValue);
        $reportFieldDAO->setChangeDateTime($changeTimestamp);

        return $reportFieldDAO;
    }
}
