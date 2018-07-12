<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive;

use Doctrine\ORM\EntityManager;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;

abstract class AbstractPipedrive
{
    const PERSON_ENTITY_TYPE          = 'person';
    const LEAD_ENTITY_TYPE            = 'lead';
    const ORGANIZATION_ENTITY_TYPE    = 'organization';
    const COMPANY_ENTITY_TYPE         = 'company';
    const NO_ALLOWED_FIELDS_TO_EXPORT = ['ID'];

    /**
     * @var PipedriveIntegration
     */
    private $integration;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param PipedriveIntegration $integration
     */
    public function setIntegration(PipedriveIntegration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * @return PipedriveIntegration
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param array $data
     * @param array $objectFields
     *
     * @return array
     */
    protected function convertPipedriveData(array $data = [], $objectFields = [])
    {
        // Convert multiselect data
        // Pipedrive webhook return IDs not labels, but  Mautic to Pipedrive sync labels
        if (!empty($objectFields)) {
            $keys = array_keys($data);
            foreach ($objectFields as $field) {
                if (in_array($field['field_type'], ['set', 'enum']) && in_array($field['key'], $keys)) {
                    $pipedriveContactFieldOptions = array_flip(explode(',', $data[$field['key']]));
                    $pipedriveAllFieldOptions     = array_combine(array_values(array_column($field['options'], 'id')),
                        array_column($field['options'], 'label'));
                    foreach ($pipedriveAllFieldOptions as $key => $option) {
                        if (!isset($pipedriveContactFieldOptions[$key])) {
                            unset($pipedriveAllFieldOptions[$key]);
                        }
                    }
                    $data[$field['key']] = $pipedriveAllFieldOptions;
                }
            }
        }

        if (isset($data['email'])) {
            $data['email'] = $data['email'][0]['value'];
        }

        if (isset($data['phone'])) {
            $data['phone'] = $data['phone'][0]['value'];
        }

        if (isset($data['org_id']) && is_array($data['org_id'])) {
            $data['org_id'] = $data['org_id']['value'];
        }

        if (isset($data['owner_id']) && is_array($data['owner_id'])) {
            $data['owner_id'] = $data['owner_id']['value'];
        }

        return $data;
    }

    /**
     * @param $date
     * @param $integrationEntityId
     * @param $internalEntityId
     *
     * @return IntegrationEntity
     */
    public function createIntegrationLeadEntity($date, $integrationEntityId, $internalEntityId)
    {
        return $this->createIntegrationEntity($date, $integrationEntityId, $internalEntityId, self::PERSON_ENTITY_TYPE, self::LEAD_ENTITY_TYPE);
    }

    /**
     * @param $date
     * @param $integrationEntityId
     * @param $internalEntityId
     *
     * @return IntegrationEntity
     */
    public function createIntegrationCompanyEntity($date, $integrationEntityId, $internalEntityId)
    {
        return $this->createIntegrationEntity($date, $integrationEntityId, $internalEntityId, self::ORGANIZATION_ENTITY_TYPE, self::COMPANY_ENTITY_TYPE);
    }

    /**
     * @param $date
     * @param $integrationEntityId
     * @param $internalEntityId
     * @param $integrationEntityName
     * @param $internalEntityName
     *
     * @return IntegrationEntity
     */
    private function createIntegrationEntity($date, $integrationEntityId, $internalEntityId, $integrationEntityName, $internalEntityName)
    {
        $integrationEntity = new IntegrationEntity();
        $integrationEntity->setDateAdded($date);
        $integrationEntity->setLastSyncDate($date);
        $integrationEntity->setIntegration($this->getIntegration()->getName());
        $integrationEntity->setIntegrationEntity($integrationEntityName);
        $integrationEntity->setIntegrationEntityId($integrationEntityId);
        $integrationEntity->setInternalEntity($internalEntityName);
        $integrationEntity->setInternalEntityId($internalEntityId);

        return $integrationEntity;
    }

    /**
     * @param array $criteria
     *
     * @return IntegrationEntity|null|object
     */
    protected function getLeadIntegrationEntity(array $criteria = [])
    {
        $criteria['integrationEntity'] = self::PERSON_ENTITY_TYPE;
        $criteria['internalEntity']    = self::LEAD_ENTITY_TYPE;

        return $this->getIntegrationEntity($criteria);
    }

    /**
     * @param array $criteria
     *
     * @return IntegrationEntity|null|object
     */
    protected function getCompanyIntegrationEntity(array $criteria = [])
    {
        $criteria['integrationEntity'] = self::ORGANIZATION_ENTITY_TYPE;
        $criteria['internalEntity']    = self::COMPANY_ENTITY_TYPE;

        return $this->getIntegrationEntity($criteria);
    }

    /**
     * @param array $criteria
     *
     * @return IntegrationEntity|null|object
     */
    private function getIntegrationEntity(array $criteria = [])
    {
        $criteria['integration'] = $this->getIntegration()->getName();

        return $this->em->getRepository(IntegrationEntity::class)->findOneBy($criteria);
    }
}
