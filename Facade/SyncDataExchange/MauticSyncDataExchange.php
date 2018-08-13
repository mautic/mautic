<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchange;

use Mautic\LeadBundle\Entity\LeadRepository;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\FieldDAO AS ReportFieldDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\ObjectDAO AS ReportObjectDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\ObjectChangeDAO AS OrderObjectChangeDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\OrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\ReportDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Request\RequestDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Value\EncodedValueDAO;
use MauticPlugin\MauticIntegrationsBundle\Entity\FieldChange;
use MauticPlugin\MauticIntegrationsBundle\Entity\FieldChangeRepository;
use MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchange\SyncDataExchangeInterface;
use MauticPlugin\MauticIntegrationsBundle\Helpers\VariableExpressor\VariableExpressorHelperInterface;

/**
 * Class MauticSyncDataExchange
 */
class MauticSyncDataExchange implements SyncDataExchangeInterface
{
    const NAME = 'mautic';
    const CONTACT_OBJECT = 'lead'; // kept as lead for BC

    /**
     * @var FieldChangeRepository
     */
    private $fieldChangeRepository;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var VariableExpressorHelperInterface
     */
    private $variableExpressorHelper;

    /**
     * MauticSyncDataExchange constructor.
     *
     * @param FieldChangeRepository            $fieldChangeRepository
     * @param LeadRepository                   $leadRepository
     * @param VariableExpressorHelperInterface $variableExpressorHelper
     */
    public function __construct(
        FieldChangeRepository $fieldChangeRepository,
        LeadRepository $leadRepository,
        VariableExpressorHelperInterface $variableExpressorHelper
    ) {
        $this->fieldChangeRepository   = $fieldChangeRepository;
        $this->leadRepository          = $leadRepository;
        $this->variableExpressorHelper = $variableExpressorHelper;
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
            $fieldsChanges = $this->fieldChangeRepository->findChangesBetween(
                $objectDAO->getObject(),
                $requestDAO->getFromTimestamp(),
                $requestDAO->getToTimestamp()
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

                $changeTimestamp = $fieldChange['modified_at'];
                $columnType      = $fieldChange['column_type'];
                $columnValue     = $fieldChange['column_value'];
                $newValue        = $this->variableExpressorHelper->decodeVariable(new EncodedValueDAO($columnType, $columnValue));

                $reportFieldDAO = new ReportFieldDAO($fieldChange['column_name'], $newValue);
                $reportFieldDAO->setChangeTimestamp($changeTimestamp);

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
}
