<?php

namespace MauticPlugin\MauticFullContactBundle\Services;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PointBundle\Model\PointModel;
use MauticPlugin\MauticFullContactBundle\Integration\Config;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use MauticPlugin\MauticFullContactBundle\Integration\Support\ConfigSupport;
use MauticPlugin\MauticFullContactBundle\Sync\Mapping\Field\Field;
use MauticPlugin\MauticFullContactBundle\Sync\Mapping\Field\FieldRepository;
use Monolog\Logger;

class ContactStorageHelper
{
    private $integrationName       = FullContactIntegration::NAME;
    private $integrationObjectName = FullContactIntegration::NAME;
    private $internalObjectName    = Contact::NAME;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var ObjectMappingRepository
     */
    private $objectMappingRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var FieldRepository
     */
    private $fieldRepository;

    /**
     * @var Logger
     */
    private $logger;

    private $mappedFields = [];

    protected $fullContactData = [];

    public function __construct(
        LeadModel $lead_model,
        ObjectMappingRepository $objectMappingRepository,
        Logger $logger,
        FieldRepository $fieldRepository,
        Config $config
    ) {
        $this->leadModel               = $lead_model;
        $this->objectMappingRepository = $objectMappingRepository;
        $this->logger                  = $logger;
        $this->fieldRepository         = $fieldRepository;
        $this->config                  = $config;
        $this->mappedFields            = $this->config->getMappedFields(ConfigSupport::CONTACT);
    }

    public function getInternalObject($integrationObjectId)
    {
        return $this->objectMappingRepository->getInternalObject(
            $this->integrationName,
            $this->integrationObjectName,
            $integrationObjectId,
            $this->internalObjectName);
    }

    public function mapContactObject($fullContactId, $mauticId)
    {
        $objectMapping = new ObjectMapping();
        $objectMapping->setIntegration($this->integrationName)
            ->setIntegrationObjectName($this->integrationObjectName)
            ->setInternalObjectName($this->internalObjectName)
            ->setIntegrationObjectId($fullContactId)
            ->setInternalObjectId($mauticId)
            ->setLastSyncDate(new \DateTime());
        $this->saveObjectMapping($objectMapping);
    }

    private function saveObjectMapping(ObjectMapping $objectMapping): void
    {
        $this->objectMappingRepository->saveEntity($objectMapping);
        $this->objectMappingRepository->clear();
    }

    public function processContactData($data, $lead)
    {
        // Process the response so we can map it with Mautic data.
        $contactData           = json_decode(json_encode($data), true);
        $this->fullContactData = $this->deflateFullContactData($contactData);

        $updates = $this->mapFieldData();

        // Get the lead and save all the values to lead entity.
        $model = $this->leadModel;
        $model->setFieldValues($lead, $updates);
        $model->saveEntity($lead);

        $this->logger->addInfo('Updated contact with '.$lead->getId().' id');
    }

    private function deflateFullContactData(array $data): array
    {
        // @todo: Check and review the approach for nested attributes returned from Fullcontact API response.
        foreach ($data as $field => $value) {
            $data[$field] = $value;
        }

        return $data;
    }

    private function mapFieldData(): array
    {
        $fields        = $this->fieldRepository->getFields($this->integrationObjectName);
        $contactValues = [];
        $data          = $this->fullContactData;
        foreach ($this->mappedFields as $integrationFieldAlias => $field) {
            if (ObjectMappingDAO::SYNC_TO_MAUTIC !== $field['syncDirection']
                && ObjectMappingDAO::SYNC_BIDIRECTIONALLY !== $field['syncDirection']) {
                continue;
            }
            $contactValues[$field['mappedField']] = $data[$integrationFieldAlias];
        }

        return $contactValues;
    }
}
