<?php

namespace MauticPlugin\MauticCrmBundle\Integration\Pipedrive;

use Doctrine\ORM\EntityManager;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use MauticPlugin\MauticCrmBundle\Integration\PipedriveIntegration;

abstract class AbstractPipedrive
{
    const PERSON_ENTITY_TYPE       = 'person';
    const LEAD_ENTITY_TYPE         = 'lead';
    const ORGANIZATION_ENTITY_TYPE = 'organization';
    const COMPANY_ENTITY_TYPE      = 'company';

    /**
     * @var PipedriveIntegration
     */
    private $integration;

    /**
     * @var EntityManager
     */
    protected $em;

    public function setIntegration(PipedriveIntegration $integration)
    {
        $this->integration = $integration;
    }

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
     * @return array
     */
    protected function convertPipedriveData(array $data = [], array $objectFields = [])
    {

        // Convert multiselect data
        // Pipedrive webhook return IDs not labels, but  Mautic to Pipedrive sync labels
        if (!empty($objectFields)) {
            foreach ($objectFields as $field) {
                if ($field['field_type'] == 'set' && in_array($field['key'], array_keys($data))) {
                    $pipedriveContactFieldOptions = array_flip(explode(',', $data[$field['key']]));
                    $pipedriveAllFieldOptions = array_combine(array_values(array_column($field['options'], 'id')),
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

    public function createIntegrationLeadEntity($date, $integrationEntityId, $internalEntityId)
    {
        return $this->createIntegrationEntity($date, $integrationEntityId, $internalEntityId, self::PERSON_ENTITY_TYPE, self::LEAD_ENTITY_TYPE);
    }

    public function createIntegrationCompanyEntity($date, $integrationEntityId, $internalEntityId)
    {
        return $this->createIntegrationEntity($date, $integrationEntityId, $internalEntityId, self::ORGANIZATION_ENTITY_TYPE, self::COMPANY_ENTITY_TYPE);
    }

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

    protected function getLeadIntegrationEntity(array $criteria = [])
    {
        $criteria['integrationEntity'] = self::PERSON_ENTITY_TYPE;
        $criteria['internalEntity']    = self::LEAD_ENTITY_TYPE;

        return $this->getIntegrationEntity($criteria);
    }

    protected function getCompanyIntegrationEntity(array $criteria = [])
    {
        $criteria['integrationEntity'] = self::ORGANIZATION_ENTITY_TYPE;
        $criteria['internalEntity']    = self::COMPANY_ENTITY_TYPE;

        return $this->getIntegrationEntity($criteria);
    }

    private function getIntegrationEntity(array $criteria = [])
    {
        $criteria['integration'] = $this->getIntegration()->getName();

        return $this->em->getRepository(IntegrationEntity::class)->findOneBy($criteria);
    }
}
