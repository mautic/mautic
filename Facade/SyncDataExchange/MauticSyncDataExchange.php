<?php

namespace MauticPlugin\MauticIntegrationsBundle\Facade\SyncDataExchangeService;

use Doctrine\ORM\EntityManager;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\FieldChangeDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\IntegrationMappingManualDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\ObjectChangeDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\SyncOrderDAO;
use MauticPlugin\MauticIntegrationsBundle\DAO\Sync\SyncReportDAO;
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
     * @param IntegrationMappingManualDAO $integrationMapping
     * @param int|null $fromTimestamp
     * @return SyncReportDAO
     */
    public function getSyncReport(IntegrationMappingManualDAO $integrationMapping, $fromTimestamp = null)
    {
        $syncReport = new SyncReportDAO('mautic');
        $fieldsChanges = $this->repo->findAll();
        $objectChanges = [];
        foreach($fieldsChanges as $fieldChange) {
            $object = $fieldChange['object'];
            $objectId = $fieldChange['object_id'];
            if(!array_key_exists($object, $objectChanges)) {
                $objectChanges[$object] = [];
            }
            if(!array_key_exists($objectId, $objectChanges[$object])) {
                $objectChanges[$object][$objectId] = new ObjectChangeDAO($objectId, $object);
            }
            /** @var ObjectChangeDAO $objectChangeDAO */
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
     * @param SyncOrderDAO $syncOrderDAO
     */
    public function executeSyncOrder(SyncOrderDAO $syncOrderDAO)
    {
        $objectChanges = $syncOrderDAO->getObjectsChanges();
        $chunkedObjectsChanges = [];
        $objectsIds = [];
        foreach($objectChanges as $objectChange) {
            $entity = $objectChange->getEntity();
            if(!array_key_exists($entity, $objectsIds)) {
                $objectsIds[$entity] = [];
                $chunkedObjectsChanges[$entity] = [];
            }
            $objectsIds[$entity][] = $objectChange->getId();
            $chunkedObjectsChanges[$entity][$objectChange->getId()] = $objectChange;
        }
        foreach($objectsIds as $entity => $ids) {
            switch($entity) {
                case 'lead':
                    $leads = $this->leadRepository->findByIds($ids);
                    /** @var Lead $lead */
                    foreach($leads as $lead) {
                        /** @var ObjectChangeDAO $objectChange */
                        $objectChange = $chunkedObjectsChanges[$entity][$lead->getId()];
                        $fieldsChanges = $objectChange->getFieldsChanges();
                        foreach($fieldsChanges as $fieldsChange) {
                            $lead->addUpdatedField($fieldsChange->getField(), $fieldsChange->getValue());
                        }
                        $this->em->persist($lead);
                    }
            }
        }

    }
}
