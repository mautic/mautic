<?php

namespace MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\FieldChangeDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Mapping\MappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\ObjectChangeDAO AS ReportObjectChangeDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\ObjectChangeDAO AS OrderObjectChangeDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Order\OrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report\ReportDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\VariableEncodeDAO;
use MauticPlugin\MauticIntegrationsBundle\Services\VariableExpressorHelperInterface;

/**
 * Class MauticSyncDataExchange
 * @package MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService
 */
class MauticSyncDataExchange implements SyncDataExchangeInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var VariableExpressorHelperInterface
     */
    private $variableExpressorHelper;

    /**
     * MauticSyncDataExchangeService constructor.
     * @param EntityManager $entityManager
     * @param LeadRepository $leadRepository
     * @param VariableExpressorHelperInterface $variableExpressorHelper
     */
    public function __construct(
        EntityManager $entityManager,
        LeadRepository $leadRepository,
        VariableExpressorHelperInterface $variableExpressorHelper
    )
    {
        $this->em = $entityManager;
        $this->leadRepository = $leadRepository;
        $this->variableExpressorHelper = $variableExpressorHelper;
    }

    /**
     * @return string
     */
    public function getIntegration()
    {
        return 'mautic';
    }

    /**
     * @param MappingManualDAO $integrationMapping
     * @param int|null $fromTimestamp
     * @return ReportDAO
     */
    public function getSyncReport(MappingManualDAO $integrationMapping, $fromTimestamp = null)
    {
        $syncReport = new ReportDAO('mautic');
        $fieldsChanges = $this->repo->findAll();
        $objectChanges = [];
        foreach($fieldsChanges as $fieldChange) {
            $object = $fieldChange['object'];
            $objectId = $fieldChange['object_id'];
            if(!array_key_exists($object, $objectChanges)) {
                $objectChanges[$object] = [];
            }
            if(!array_key_exists($objectId, $objectChanges[$object])) {
                $objectChanges[$object][$objectId] = new ReportObjectChangeDAO($object, $objectId);
            }
            /** @var ReportObjectChangeDAO $objectChangeDAO */
            $objectChangeDAO = $objectChanges[$object][$objectId];
            $changeTimestamp = $fieldChange['modified_at'];
            $columnType = $fieldChange['column_type'];
            $columnValue = $fieldChange['column_value'];
            $newValue = $this->variableExpressorHelper->decodeVariable(new VariableEncodeDAO($columnType, $columnValue));
            $objectChangeDAO->addFieldChange(new FieldChangeDAO($fieldChange['column_name'], $newValue, $changeTimestamp));
        }
        foreach($objectChanges as $entities) {
            foreach($entities as $objectChange) {
                $syncReport->addObjectChange($objectChange);
            }
        }
        return $syncReport;
    }

    /**
     * @param OrderDAO $syncOrderDAO
     */
    public function executeSyncOrder(OrderDAO $syncOrderDAO)
    {
        $objectChanges = $syncOrderDAO->getObjectsChanges();
        $chunkedObjectsChanges = [];
        $objectsIds = [];
        foreach($objectChanges as $objectChange) {
            $objectName = $objectChange->getObject();
            if(!array_key_exists($objectName, $objectsIds)) {
                $objectsIds[$objectName] = [];
                $chunkedObjectsChanges[$objectName] = [];
            }
            $objectsIds[$objectName][] = $objectChange->getObjectId();
            $chunkedObjectsChanges[$objectName][$objectChange->getObjectId()] = $objectChange;
        }
        foreach($objectsIds as $objectName => $ids) {
            switch($objectName) {
                case 'lead':
                    $leads = $this->leadRepository->findByIds($ids);
                    /** @var Lead $lead */
                    foreach($leads as $lead) {
                        /** @var OrderObjectChangeDAO $objectChange */
                        $objectChange = $chunkedObjectsChanges[$objectName][$lead->getId()];
                        $fieldsChanges = $objectChange->getFields();
                        foreach($fieldsChanges as $fieldsChange) {
                            $lead->addUpdatedField($fieldsChange->getName(), $fieldsChange->getValue());
                        }
                        $this->em->persist($lead);
                    }
            }
        }
    }
}
