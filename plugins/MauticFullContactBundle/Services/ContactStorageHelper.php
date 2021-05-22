<?php

namespace MauticPlugin\MauticFullContactBundle\Services;

use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticFullContactBundle\Integration\Config;
use MauticPlugin\MauticFullContactBundle\Integration\Support\ConfigSupport;
use Monolog\Logger;

class ContactStorageHelper
{
    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string[]
     */
    private $mappedFields = [];

    public function __construct(
        LeadModel $lead_model,
        Logger $logger,
        Config $config
    ) {
        $this->leadModel               = $lead_model;
        $this->logger                  = $logger;
        $this->config                  = $config;
        $this->mappedFields            = $this->config->getMappedFields(ConfigSupport::CONTACT);
    }

    public function processContactData($data, $lead)
    {
        // Process the response so we can map it with Mautic data.
        $contactData           = json_decode(json_encode($data), true);

        $updates = $this->mapFieldData($contactData);

        // Get the lead and save all the values to lead entity.
        $model = $this->leadModel;
        $model->setFieldValues($lead, $updates);
        $model->saveEntity($lead);

        $this->logger->addInfo('Updated contact with '.$lead->getId().' id');
    }

    /**
     * Map field's sync settings and prepare array of values to be updated.
     *
     * @param array $contactData
     *                           Data from the FullContact Json response
     */
    private function mapFieldData($contactData): array
    {
        $contactValues = [];
        $data          = $contactData;
        foreach ($this->mappedFields as $integrationFieldAlias => $field) {
            if (ObjectMappingDAO::SYNC_TO_MAUTIC !== $field['syncDirection']
                && ObjectMappingDAO::SYNC_BIDIRECTIONALLY !== $field['syncDirection']) {
                continue;
            }
            $contactValues[$field['mappedField']] = $this->fetchFieldValue($data, $integrationFieldAlias);
        }

        return $contactValues;
    }

    /**
     * Fetch values from the data returned by FullContact.
     *
     * @param array $data
     *                    Data from the FullContact Json response
     * @param $fieldName
     *   Field name that we want to retrieve
     *
     * @return mixed|string
     */
    public function fetchFieldValue($data, $fieldName)
    {
        switch ($fieldName) {
            case 'twitter':
            case 'linkedin':
            case 'facebook':
            case 'title':
            case 'organization':
            case 'website':
                $value = isset($data[$fieldName]) ? $data[$fieldName] : '';
                break;

            case 'given':
            case 'family':
                $value = isset($data['details']['name'][$fieldName]) ? $data['details']['name'][$fieldName] : '';
                break;

            case 'city':
            case 'region':
            case 'country':
                $value = isset($data['details']['locations'][0][$fieldName]) ? $data['details']['locations'][0][$fieldName] : '';
                break;

            default:
                $value = '';
        }

        return $value;
    }
}
