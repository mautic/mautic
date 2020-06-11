<?php

namespace MauticPlugin\MauticFullContactBundle\Services;

use Mautic\IntegrationsBundle\Entity\ObjectMappingRepository;
use Mautic\IntegrationsBundle\Sync\DAO\Mapping\ObjectMappingDAO;
use Mautic\IntegrationsBundle\Sync\SyncDataExchange\Internal\Object\Contact;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Model\CompanyModel;
use MauticPlugin\MauticFullContactBundle\Integration\Config;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use MauticPlugin\MauticFullContactBundle\Integration\Support\ConfigSupport;
use MauticPlugin\MauticFullContactBundle\Sync\Mapping\Field\FieldRepository;
use Monolog\Logger;

class CompanyStorageHelper
{
    /**
     * @var CompanyModel
     */
    private $companyModel;

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
        CompanyModel $companyModel,
        Logger $logger,
        Config $config
    ) {
        $this->companyModel               = $companyModel;
        $this->logger                     = $logger;
        $this->config                     = $config;
        $this->mappedFields               = $this->config->getMappedFields(ConfigSupport::COMPANY);
    }

    /**
     * Process and save the data returned by fullcontact response.
     *
     * @param string  $data
     *                         JSON response returned from the API
     * @param Company $company
     */
    public function processCompanyData($data, $company): void
    {
        // Process the response so we can map it with Mautic data.
        $companyData           = json_decode(json_encode($data), true);

        $updates = $this->mapFieldData($companyData);

        // Get the lead and save all the values to company entity.
        $model = $this->companyModel;
        $model->setFieldValues($company, $updates);
        $model->saveEntity($company);

        $this->logger->addInfo('Updated company with '.$company->getId().' id');
    }

    /**
     * Map field's sync settings and prepare array of values to be updated.
     *
     * @param array $companyData
     *                           Array of compnay data returned from FullContact API
     */
    private function mapFieldData($companyData): array
    {
        $companyValues = [];
        foreach ($this->mappedFields as $integrationFieldAlias => $field) {
            if (ObjectMappingDAO::SYNC_TO_MAUTIC !== $field['syncDirection']
                && ObjectMappingDAO::SYNC_BIDIRECTIONALLY !== $field['syncDirection']) {
                continue;
            }
            $companyValues[$field['mappedField']] = $this->fetchFieldValue($companyData, $integrationFieldAlias);
        }

        return $companyValues;
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
            case 'name':
            case 'twitter':
            case 'linkedin':
            case 'facebook':
            case 'title':
            case 'website':
            case 'employees':
            case 'location':
                $value = isset($data[$fieldName]) ? $data[$fieldName] : '';
                break;

            case 'emails':
            case 'phones':
                $value = isset($data['details'][$fieldName][0]['value']) ? $data['details'][$fieldName][0]['value'] : '';
                break;

            case 'industries':
                $value = isset($data['details'][$fieldName][0]['name']) ? $data['details'][$fieldName][0]['name'] : '';
                break;

            case 'addressLine1':
            case 'addressLine2':
            case 'city':
            case 'region':
            case 'country':
            case 'postalCode':
                $value = isset($data['details']['locations'][0][$fieldName]) ? $data['details']['locations'][0][$fieldName] : '';
                break;

            default:
                $value = '';
        }

        return $value;
    }
}
