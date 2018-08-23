<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\Mapping;

use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\FieldModel;
use MauticPlugin\IntegrationsBundle\Entity\ObjectMapping;
use MauticPlugin\IntegrationsBundle\Entity\ObjectMappingRepository;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Mapping\MappingManualDAO;
use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\ObjectDAO;

class MappingHelper
{
    /**
     * @var FieldModel
     */
    private $fieldModel;

    /**
     * @var LeadRepository
     */
    private $leadRepository;

    /**
     * @var ObjectMappingRepository
     */
    private $objectMappingRepository;

    /**
     * MappingHelper constructor.
     *
     * @param FieldModel              $fieldModel
     * @param LeadRepository          $leadRepository
     * @param ObjectMappingRepository $objectMappingRepository
     */
    public function __construct(FieldModel $fieldModel, LeadRepository $leadRepository, ObjectMappingRepository $objectMappingRepository)
    {
        $this->fieldModel              = $fieldModel;
        $this->leadRepository          = $leadRepository;
        $this->objectMappingRepository = $objectMappingRepository;
    }

    /**
     * @param MappingManualDAO $mappingManualDAO
     * @param string           $internalObjectName
     * @param ObjectDAO        $integrationObjectDAO
     *
     * @return ObjectDAO
     */
    public function findMauticObject(MappingManualDAO $mappingManualDAO, string $internalObjectName, ObjectDAO $integrationObjectDAO)
    {
        // Check if this contact is already tracked
        if ($internalObject = $this->objectMappingRepository->getInternalObject(
            $mappingManualDAO->getIntegration(),
            $integrationObjectDAO->getObject(),
            $integrationObjectDAO->getObjectId(),
            $internalObjectName
        )) {
            return new ObjectDAO($internalObjectName, $internalObject['internal_object_id'], $internalObject['last_sync']);
        }

        // We don't know who this is so search Mautic
        $uniqueIdentifierFields = $this->fieldModel->getUniqueIdentifierFields(['object' => $internalObjectName]);
        $identifiers            = [];
        foreach ($uniqueIdentifierFields as $field => $fieldLabel) {
            $integrationField = $mappingManualDAO->getIntegrationMappedField($internalObjectName, $integrationObjectDAO->getObject(), $field);

            if ($integrationValue = $integrationObjectDAO->getField($integrationField)) {
                $identifiers[$integrationField] = trim($integrationValue);
            }
        }

        if (empty($identifiers)) {
            // No fields found to search for contact so return null
            return new ObjectDAO($internalObjectName, null);
        }

        if (!$foundContacts = $this->leadRepository->getLeadIdsByUniqueFields($identifiers)) {
            // No contacts were found
            return new ObjectDAO($internalObjectName, null);
        }

        // Match found!
        $objectId = (int) reset($foundContacts);

        // Let's store the relationship since we know it
        $objectMapping = new ObjectMapping();
        $objectMapping->setLastSyncDate($integrationObjectDAO->getChangeDateTime())
            ->setIntegration($mappingManualDAO->getIntegration())
            ->setIntegrationObjectName($integrationObjectDAO->getObject())
            ->setIntegrationObjectId($integrationObjectDAO->getObjectId())
            ->setInternalObjectName($internalObjectName)
            ->setInternalObjectId($objectId);
        $this->saveObjectMapping($objectMapping);

        return new ObjectDAO($internalObjectName, $objectId);
    }

    /**
     * @param string    $integration
     * @param string    $integrationObjectName
     * @param ObjectDAO $internalObjectDAO
     *
     * @return ObjectDAO
     */
    public function findIntegrationObject(string $integration, string $integrationObjectName, ObjectDAO $internalObjectDAO)
    {
        if ($integrationObject = $this->objectMappingRepository->getIntegrationObject(
            $integration,
            $internalObjectDAO->getObject(),
            $internalObjectDAO->getObjectId(),
            $integrationObjectName
        )) {
            return new ObjectDAO($integrationObjectName, $integrationObject['integration_object_id'], $integrationObject['last_sync']);
        }

        return new ObjectDAO($integrationObjectName, null);
    }

    /**
     * @param ObjectMapping $objectMapping
     */
    public function saveObjectMapping(ObjectMapping $objectMapping)
    {
        $this->objectMappingRepository->saveEntity($objectMapping);
        $this->objectMappingRepository->clear();
    }
}