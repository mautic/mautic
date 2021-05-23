<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use MauticPlugin\MauticCrmBundle\Api\CrmApi;

abstract class CrmAbstractIntegration extends AbstractIntegration
{
    protected $auth;
    protected $helper;

    public function setIntegrationSettings(Integration $settings)
    {
        //make sure URL does not have ending /
        $keys = $this->getDecryptedApiKeys($settings);
        if (isset($keys['url']) && '/' == substr($keys['url'], -1)) {
            $keys['url'] = substr($keys['url'], 0, -1);
            $this->encryptAndSetApiKeys($keys, $settings);
        }

        parent::setIntegrationSettings($settings);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'rest';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'get_leads'];
    }

    /**
     * @param Lead|array $lead
     * @param array      $config
     *
     * @return array|bool
     */
    public function pushLead($lead, $config = [])
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return [];
        }

        $mappedData = $this->populateLeadData($lead, $config);

        $this->amendLeadDataBeforePush($mappedData);

        if (empty($mappedData)) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $this->getApiHelper()->createLead($mappedData, $lead);

                return true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return false;
    }

    /**
     * @param array $params
     */
    public function getLeads($params, $query, &$executed, $result = [], $object = 'Lead')
    {
        $executed = null;

        $query = $this->getFetchQuery($params);

        try {
            if ($this->isAuthorized()) {
                $result = $this->getApiHelper()->getLeads($query);

                return $this->amendLeadDataBeforeMauticPopulate($result, $object);
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    /**
     * Amend mapped lead data before pushing to CRM.
     *
     * @param $mappedData
     */
    public function amendLeadDataBeforePush(&$mappedData)
    {
    }

    /**
     * get query to fetch lead data.
     *
     * @param $config
     */
    public function getFetchQuery($config)
    {
    }

    /**
     * Ammend mapped lead data before creating to Mautic.
     *
     * @param $data
     * @param $object
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object)
    {
        return null;
    }

    /**
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_id';
    }

    /**
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'client_secret';
    }

    /**
     * {@inheritdoc}
     */
    public function sortFieldsAlphabetically()
    {
        return false;
    }

    /**
     * Get the API helper.
     *
     * @return CrmApi
     */
    public function getApiHelper()
    {
        if (empty($this->helper)) {
            $class        = '\\MauticPlugin\\MauticCrmBundle\\Api\\'.$this->getName().'Api';
            $this->helper = new $class($this);
        }

        return $this->helper;
    }

    /**
     * @param array $params
     */
    public function pushLeadActivity($params = [])
    {
    }

    /**
     * @param $leadId
     *
     * @return array
     */
    public function getLeadData(\DateTime $startDate = null, \DateTime $endDate = null, $leadId)
    {
        $leadIds      = (!is_array($leadId)) ? [$leadId] : $leadId;
        $leadActivity = [];

        $config = $this->mergeConfigToFeatureSettings();
        if (!isset($config['activityEvents'])) {
            // BC for pre 2.11.0
            $config['activityEvents'] = ['point.gained', 'form.submitted', 'email.read'];
        } elseif (empty($config['activityEvents'])) {
            // Inclusive filter meaning we only send events if something is selected
            return [];
        }

        $filters = [
            'search'        => '',
            'includeEvents' => $config['activityEvents'],
            'excludeEvents' => [],
        ];

        if ($startDate) {
            $filters['dateFrom'] = $startDate;
            $filters['dateTo']   = $endDate;
        }

        foreach ($leadIds as $leadId) {
            $i        = 0;
            $activity = [];
            $lead     = $this->em->getReference('MauticLeadBundle:Lead', $leadId);
            $page     = 1;

            while (true) {
                $engagements = $this->leadModel->getEngagements($lead, $filters, null, $page, 100, false);
                $events      = $engagements[0]['events'];

                if (empty($events)) {
                    break;
                }

                // inject lead into events
                foreach ($events as $event) {
                    $link  = '';
                    $label = (isset($event['eventLabel'])) ? $event['eventLabel'] : $event['eventType'];
                    if (is_array($label)) {
                        $link  = $label['href'];
                        $label = $label['label'];
                    }

                    $activity[$i]['eventType']   = $event['eventType'];
                    $activity[$i]['name']        = $event['eventType'].' - '.$label;
                    $activity[$i]['description'] = $link;
                    $activity[$i]['dateAdded']   = $event['timestamp'];

                    // We must keep BC with pre 2.11.0 formatting in order to prevent duplicates
                    switch ($event['eventType']) {
                        case 'point.gained':
                            $id = str_replace($event['eventType'], 'pointChange', $event['eventId']);
                            break;
                        case 'form.submitted':
                            $id = str_replace($event['eventType'], 'formSubmission', $event['eventId']);
                            break;
                        case 'email.read':
                            $id = str_replace($event['eventType'], 'emailStat', $event['eventId']);
                            break;
                        default:
                            // Just to keep congruent formatting with the three above
                            $id = str_replace(' ', '', ucwords(str_replace('.', ' ', $event['eventId'])));
                    }

                    $activity[$i]['id'] = $id;
                    ++$i;
                }

                ++$page;

                // Lots of entities will be loaded into memory while compiling these events so let's prevent memory overload by clearing the EM
                $entityToNotDetach = ['Mautic\PluginBundle\Entity\Integration', 'Mautic\PluginBundle\Entity\Plugin'];
                $loadedEntities    = $this->em->getUnitOfWork()->getIdentityMap();
                foreach ($loadedEntities as $name => $loadedEntity) {
                    if (!in_array($name, $entityToNotDetach)) {
                        $this->em->clear($name);
                    }
                }
            }

            $leadActivity[$leadId] = [
                'records' => $activity,
            ];

            unset($activity);
        }

        return $leadActivity;
    }

    /**
     * @param      $data
     * @param null $object
     *
     * @return Company|null
     */
    public function getMauticCompany($data, $object = null)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data), true);
        } elseif (is_string($data)) {
            // Assume JSON
            $data = json_decode($data, true);
        }
        $config        = $this->mergeConfigToFeatureSettings([]);
        $matchedFields = $this->populateMauticLeadData($data, $config, 'company');

        $companyFieldTypes = $this->fieldModel->getFieldListWithProperties('company');
        foreach ($matchedFields as $companyField => $value) {
            if (isset($companyFieldTypes[$companyField]['type'])) {
                switch ($companyFieldTypes[$companyField]['type']) {
                    case 'text':
                        $matchedFields[$companyField] = substr($value, 0, 255);
                        break;
                    case 'date':
                        $date                         = new \DateTime($value);
                        $matchedFields[$companyField] = $date->format('Y-m-d');
                        break;
                    case 'datetime':
                        $date                         = new \DateTime($value);
                        $matchedFields[$companyField] = $date->format('Y-m-d H:i:s');
                        break;
                }
            }
        }

        // Default to new company
        $company         = new Company();
        $existingCompany = IdentifyCompanyHelper::identifyLeadsCompany($matchedFields, null, $this->companyModel);
        if (!empty($existingCompany[2])) {
            $company = $existingCompany[2];
        }

        if (!empty($existingCompany[2])) {
            $fieldsToUpdate = $this->getPriorityFieldsForMautic($config, $object, 'mautic_company');
            $fieldsToUpdate = array_intersect_key($config['companyFields'], $fieldsToUpdate);
            $matchedFields  = array_intersect_key($matchedFields, array_flip($fieldsToUpdate));
        } else {
            $matchedFields = $this->hydrateCompanyName($matchedFields);

            // If we don't have an company name, don't create the company because it'll result in what looks like an "empty" company
            if (empty($matchedFields['companyname'])) {
                return null;
            }
        }

        $this->companyModel->setFieldValues($company, $matchedFields, false);
        $this->companyModel->saveEntity($company, false);

        return $company;
    }

    /**
     * Create or update existing Mautic lead from the integration's profile data.
     *
     * @param mixed       $data        Profile data from integration
     * @param bool|true   $persist     Set to false to not persist lead to the database in this method
     * @param array|null  $socialCache
     * @param mixed||null $identifiers
     * @param string|null $object
     *
     * @return Lead
     */
    public function getMauticLead($data, $persist = true, $socialCache = null, $identifiers = null, $object = null)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data), true);
        } elseif (is_string($data)) {
            // Assume JSON
            $data = json_decode($data, true);
        }
        $config = $this->mergeConfigToFeatureSettings([]);
        // Match that data with mapped lead fields
        $matchedFields = $this->populateMauticLeadData($data, $config);

        if (empty($matchedFields)) {
            return;
        }

        // Find unique identifier fields used by the integration
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel           = $this->leadModel;
        $uniqueLeadFields    = $this->fieldModel->getUniqueIdentifierFields();
        $uniqueLeadFieldData = [];
        $leadFieldTypes      = $this->fieldModel->getFieldListWithProperties();

        foreach ($matchedFields as $leadField => $value) {
            if (array_key_exists($leadField, $uniqueLeadFields) && !empty($value)) {
                $uniqueLeadFieldData[$leadField] = $value;
            }

            $fieldType                 = isset($leadFieldTypes[$leadField]['type']) ? $leadFieldTypes[$leadField]['type'] : '';
            $matchedFields[$leadField] = $this->limitString($value, $fieldType);
        }

        if (count(array_diff_key($uniqueLeadFields, $matchedFields)) == count($uniqueLeadFields)) {
            //return if uniqueIdentifiers have no data set to avoid duplicating leads.
            return;
        }

        // Default to new lead
        $lead = new Lead();
        $lead->setNewlyCreated(true);

        if (count($uniqueLeadFieldData)) {
            $existingLeads = $this->em->getRepository('MauticLeadBundle:Lead')
                ->getLeadsByUniqueFields($uniqueLeadFieldData);
            if (!empty($existingLeads)) {
                $lead = array_shift($existingLeads);
            }
        }

        $leadFields = $this->cleanPriorityFields($config, $object);
        if (!$lead->isNewlyCreated()) {
            $params = $this->commandParameters;

            $this->getLeadDoNotContactByDate('email', $matchedFields, $object, $lead, $data, $params);

            // Use only prioirty fields if updating
            $fieldsToUpdateInMautic = $this->getPriorityFieldsForMautic($config, $object, 'mautic');
            if (empty($fieldsToUpdateInMautic)) {
                return;
            }

            $fieldsToUpdateInMautic = array_intersect_key($leadFields, $fieldsToUpdateInMautic);
            $matchedFields          = array_intersect_key($matchedFields, array_flip($fieldsToUpdateInMautic));
            if ((isset($config['updateBlanks']) && isset($config['updateBlanks'][0]) && 'updateBlanks' == $config['updateBlanks'][0])) {
                $matchedFields = $this->getBlankFieldsToUpdateInMautic($matchedFields, $lead->getFields(true), $leadFields, $data, $object);
            }
        }

        $leadModel->setFieldValues($lead, $matchedFields, false, false);
        if (!empty($socialCache)) {
            // Update the social cache
            $leadSocialCache = $lead->getSocialCache();
            if (!isset($leadSocialCache[$this->getName()])) {
                $leadSocialCache[$this->getName()] = [];
            }
            $leadSocialCache[$this->getName()] = array_merge($leadSocialCache[$this->getName()], $socialCache);

            // Check for activity while here
            if (null !== $identifiers && in_array('public_activity', $this->getSupportedFeatures())) {
                $this->getPublicActivity($identifiers, $leadSocialCache[$this->getName()]);
            }

            $lead->setSocialCache($leadSocialCache);
        }

        // Update the internal info integration object that has updated the record
        if (isset($data['internal'])) {
            $internalInfo                   = $lead->getInternal();
            $internalInfo[$this->getName()] = $data['internal'];
            $lead->setInternal($internalInfo);
        }

        // Update the owner if it matches (needs to be set by the integration) when fetching the data
        if (isset($data['owner_email']) && isset($config['updateOwner']) && isset($config['updateOwner'][0])
            && 'updateOwner' == $config['updateOwner'][0]
        ) {
            if ($mauticUser = $this->em->getRepository('MauticUserBundle:User')->findOneBy(['email' => $data['owner_email']])) {
                $lead->setOwner($mauticUser);
            }
        }

        if ($persist && !empty($lead->getChanges(true))) {
            // Only persist if instructed to do so as it could be that calling code needs to manipulate the lead prior to executing event listeners
            $lead->setManipulator(new LeadManipulator(
                'plugin',
                $this->getName(),
                null,
                $this->getDisplayName()
            ));
            $leadModel->saveEntity($lead, false);
        }

        return $lead;
    }

    /**
     * @param $object
     *
     * @return array|mixed
     */
    protected function getFormFieldsByObject($object, $settings = [])
    {
        $settings['feature_settings']['objects'] = [$object => $object];

        $fields = ($this->isAuthorized()) ? $this->getAvailableLeadFields($settings) : [];

        return (isset($fields[$object])) ? $fields[$object] : [];
    }

    /**
     * @param        $config
     * @param null   $entityObject   Possibly used by the CRM
     * @param string $priorityObject
     *
     * @return array
     */
    protected function getPriorityFieldsForMautic($config, $entityObject = null, $priorityObject = 'mautic')
    {
        return $this->cleanPriorityFields(
            $this->getFieldsByPriority($config, $priorityObject, 1),
            $entityObject
        );
    }

    /**
     * @param        $config
     * @param null   $entityObject   Possibly used by the CRM
     * @param string $priorityObject
     *
     * @return array
     */
    protected function getPriorityFieldsForIntegration($config, $entityObject = null, $priorityObject = 'mautic')
    {
        return $this->cleanPriorityFields(
            $this->getFieldsByPriority($config, $priorityObject, 0),
            $entityObject
        );
    }

    /**
     * @param        $direction
     * @param string $priorityObject
     *
     * @return array
     */
    protected function getFieldsByPriority(array $config, $priorityObject, $direction)
    {
        return isset($config['update_'.$priorityObject]) ? array_keys($config['update_'.$priorityObject], $direction) : array_keys($config['leadFields']);
    }

    /**
     * @param       $fieldsToUpdate
     * @param array $objects
     *
     * @return array
     */
    protected function cleanPriorityFields($fieldsToUpdate, $objects = null)
    {
        if (!isset($fieldsToUpdate['leadFields'])) {
            return $fieldsToUpdate;
        }

        if (null === $objects || is_array($objects)) {
            return $fieldsToUpdate['leadFields'];
        }

        if (isset($fieldsToUpdate['leadFields'][$objects])) {
            return $fieldsToUpdate['leadFields'][$objects];
        }

        return $fieldsToUpdate;
    }

    /**
     * @return array
     */
    protected function getSyncTimeframeDates(array $params)
    {
        $fromDate = (isset($params['start'])) ? \DateTime::createFromFormat(\DateTime::ISO8601, $params['start'])->format('Y-m-d H:i:s')
            : null;
        $toDate = (isset($params['end'])) ? \DateTime::createFromFormat(\DateTime::ISO8601, $params['end'])->format('Y-m-d H:i:s')
            : null;

        return [$fromDate, $toDate];
    }

    /**
     * @param $objectFields
     */
    public function getBlankFieldsToUpdateInMautic($matchedFields, $leadFieldValues, $objectFields, $integrationData, $object = 'Lead')
    {
        foreach ($objectFields as $integrationField => $mauticField) {
            if (isset($leadFieldValues[$mauticField]) && empty($leadFieldValues[$mauticField]['value']) && !empty($integrationData[$integrationField.'__'.$object]) && $this->translator->trans('mautic.integration.form.lead.unknown') !== $integrationData[$integrationField.'__'.$object]) {
                $matchedFields[$mauticField] = $integrationData[$integrationField.'__'.$object];
            }
        }

        return $matchedFields;
    }

    /**
     * @param $fields
     * @param $sfRecord
     * @param $config
     * @param $objectFields
     */
    public function getBlankFieldsToUpdate($fields, $sfRecord, $objectFields, $config)
    {
        //check if update blank fields is selected
        if (isset($config['updateBlanks']) && isset($config['updateBlanks'][0])
            && 'updateBlanks' == $config['updateBlanks'][0]
            && !empty($sfRecord)
            && isset($objectFields['required']['fields'])
        ) {
            foreach ($sfRecord as $fieldName => $sfField) {
                if (array_key_exists($fieldName, $objectFields['required']['fields'])) {
                    continue; // this will be treated differently
                }
                if (empty($sfField) && array_key_exists($fieldName, $objectFields['create']) && !array_key_exists($fieldName, $fields)) {
                    //map to mautic field
                    $fields[$fieldName] = $objectFields['create'][$fieldName];
                }
            }
        }

        return $fields;
    }

    /**
     * @param $fields
     *
     * @return array
     */
    protected function prepareFieldsForPush($fields)
    {
        $fieldMappings = [];
        $required      = [];
        $config        = $this->mergeConfigToFeatureSettings();

        $leadFields = $config['leadFields'];
        foreach ($fields as $key => $field) {
            if ($field['required']) {
                $required[$key] = $field;
            }
        }
        $fieldMappings['required'] = [
            'fields' => $required,
        ];
        $fieldMappings['create'] = $leadFields;

        return $fieldMappings;
    }

    /**
     * @return array
     */
    private function hydrateCompanyName(array $matchedFields)
    {
        if (!empty($matchedFields['companyname'])) {
            return $matchedFields;
        }

        if (!empty($matchedFields['companywebsite'])) {
            $matchedFields['companyname'] = $matchedFields['companywebsite'];

            return $matchedFields;
        }

        // We need something as company name so save whatever we have
        if ($firstMatchedField = reset($matchedFields)) {
            $matchedFields['companyname'] = $firstMatchedField;

            return $matchedFields;
        }

        return $matchedFields;
    }

    /**
     * Limits the string.
     *
     * @param mixed  $value
     * @param string $fieldType
     *
     * @return mixed
     */
    protected function limitString($value, $fieldType = '')
    {
        // We must not convert boolean values to string, otherwise "false" will be converted to an empty string.
        // "False" has to be converted to 0 instead.
        if (('text' == $fieldType) && !is_bool($value)) {
            return substr($value, 0, 255);
        }

        return $value;
    }
}
