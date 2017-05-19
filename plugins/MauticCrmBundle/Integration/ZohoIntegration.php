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

use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\FormBundle\Entity\Form;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Form\FormBuilder;

/**
 * Class ZohoIntegration.
 */
class ZohoIntegration extends CrmAbstractIntegration
{
    /**
     * Returns the name of the social integration that must match the name of the file.
     *
     * @return string
     */
    public function getName()
    {
        return 'Zoho';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            $this->getClientIdKey()     => 'mautic.zoho.form.email',
            $this->getClientSecretKey() => 'mautic.zoho.form.password',
        ];
    }

    /**
     * @return string
     */
    public function getClientIdKey()
    {
        return 'EMAIL_ID';
    }

    /**
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'PASSWORD';
    }

    /**
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'AUTHTOKEN';
    }

    /**
     * @param bool $isJson
     *
     * @return string
     */
    public function getApiUrl($isJson = true)
    {
        return 'https://crm.zoho.com/crm/private/'.($isJson ? 'json' : 'xml');
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => true,
        ];
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'get_leads', 'push_leads'];
    }

    /**
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
     */
    public function getLeads($params = [], $query = null, &$executed = null, $result = [], $object = 'Leads')
    {
        if ('Lead' === $object || 'Contact' === $object) {
            $object .= 's'; // pluralize object name for Zoho
        }

        $executed = 0;

        try {
            if ($this->isAuthorized()) {
                $config           = $this->mergeConfigToFeatureSettings();
                $fields           = $config['leadFields'];
                $config['object'] = $object;
                $aFields          = $this->getAvailableLeadFields($config);
                $mappedData       = [];

                foreach (array_keys($fields) as $k) {
                    if (isset($aFields[$object][$k])) {
                        $mappedData[] = $aFields[$object][$k]['dv'];
                    }
                }

                $fields = implode(',', $mappedData);

                $params['selectColumns'] = $object.'('.$fields.')';
                $params['toIndex']       = 200; // maximum number of records

                $data = $this->getApiHelper()->getLeads($params, $object);
                if (isset($data[$object])) {
                    /** @var array $leads */
                    $rows = $data[$object];
                    foreach ($rows as $row) {
                        if (is_array($row)) {
                            $leads = $this->amendLeadDataBeforeMauticPopulate($row, $object);
                            foreach ($leads as $leadData) {
                                $lead = $this->getMauticLead($leadData);
                                if ($lead) {
                                    ++$executed;
                                }
                            }
                        }
                    }
                    //TODO: fetch more records using fromIndex and toIndex until exception is thrown
//                    if ($data['has-more']) {
//                        $executed += $this->getLeads($params, $object);
//                    }
                }

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    /**
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
     */
    public function getCompanies($params = [], $query = null, &$executed = null, $result = [])
    {
        $executed = 0;
        $object   = 'company';

        try {
            if ($this->isAuthorized()) {
                $config           = $this->mergeConfigToFeatureSettings();
                $fields           = $config['companyFields'];
                $config['object'] = $object;
                $aFields          = $this->getAvailableLeadFields($config);
                $mappedData       = [];

                foreach (array_keys($fields) as $k) {
                    $mappedData[] = $aFields['company'][$k]['dv'];
                }

                $fields = implode(',', $mappedData);

                $params['selectColumns'] = 'Accounts('.$fields.')';
                $params['toIndex']       = 200; // maximum number of records

                $data = $this->getApiHelper()->getCompanies($params);
                if (isset($data['Accounts'])) {
                    /** @var array $rows */
                    $rows = $data['Accounts'];
                    foreach ($rows as $row) {
                        if (is_array($row)) {
                            $companies = $this->amendLeadDataBeforeMauticPopulate($row);
                            foreach ($companies as $companyData) {
                                $company = $this->getMauticCompany($companyData);
                                if ($company) {
                                    ++$executed;
                                }
                            }
                        }
                    }
                    //TODO: fetch more records using fromIndex and toIndex until exception is thrown
//                    if (isset($data['hasMore']) && $data['hasMore']) {
//                        $executed += $this->getCompanies($params);
//                    }
                }

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    /**
     * Create or update existing Mautic lead from the integration's profile data.
     *
     * @param mixed       $data        Profile data from integration
     * @param bool|true   $persist     Set to false to not persist lead to the database in this method
     * @param array|null  $socialCache
     * @param mixed||null $identifiers
     *
     * @return Lead|null
     *
     * @throws \InvalidArgumentException
     */
    public function getMauticLead($data, $persist = true, $socialCache = null, $identifiers = null)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data), true);
        } elseif (is_string($data)) {
            // Assume JSON
            $data = json_decode($data, true);
        }

        // Match that data with mapped lead fields
        $config        = $this->mergeConfigToFeatureSettings();
        $aFields       = $this->getAvailableLeadFields($config);
        $matchedFields = [];
        foreach ($aFields['Leads'] as $k => $v) {
            foreach ($data as $dk => $dv) {
                if ($dk === $v['dv']) {
                    $matchedFields[$config['leadFields'][$k]] = $dv;
                }
            }
        }

        if (empty($matchedFields)) {
            return null;
        }

        // Find unique identifier fields used by the integration
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel           = $this->factory->getModel('lead');
        $uniqueLeadFields    = $this->factory->getModel('lead.field')->getUniqueIdentiferFields();
        $uniqueLeadFieldData = [];

        foreach ($matchedFields as $leadField => $value) {
            if (array_key_exists($leadField, $uniqueLeadFields) && !empty($value)) {
                $uniqueLeadFieldData[$leadField] = $value;
            }
        }

        // Default to new lead
        $lead = new Lead();
        $lead->setNewlyCreated(true);

        if (count($uniqueLeadFieldData)) {
            $existingLeads = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')
                ->getLeadsByUniqueFields($uniqueLeadFieldData);

            if (!empty($existingLeads)) {
                $lead = array_shift($existingLeads);
            }
        }

        $leadModel->setFieldValues($lead, $matchedFields, false, false);

        // Update the social cache
        $leadSocialCache = $lead->getSocialCache();
        if (!isset($leadSocialCache[$this->getName()])) {
            $leadSocialCache[$this->getName()] = [];
        }

        if (null !== $socialCache) {
            $leadSocialCache[$this->getName()] = array_merge($leadSocialCache[$this->getName()], $socialCache);
        }

        // Check for activity while here
        if (null !== $identifiers && in_array('public_activity', $this->getSupportedFeatures())) {
            $this->getPublicActivity($identifiers, $leadSocialCache[$this->getName()]);
        }

        $lead->setSocialCache($leadSocialCache);

        // Update the internal info integration object that has updated the record
        if (isset($data['internal'])) {
            $internalInfo                   = $lead->getInternal();
            $internalInfo[$this->getName()] = $data['internal'];
            $lead->setInternal($internalInfo);
        }

        if (isset($company)) {
            if (!isset($matchedFields['companyname'])) {
                if (isset($matchedFields['companywebsite'])) {
                    $matchedFields['companyname'] = $matchedFields['companywebsite'];
                }
            }
            $leadModel->addToCompany($lead, $company);
        }

        $pushData['email'] = $lead->getEmail();
        $this->getApiHelper()->createLead($pushData, $lead, $updateLink = true);
        if ($persist) {
            // Only persist if instructed to do so as it could be that calling code needs to manipulate the lead prior to executing event listeners
            try {
                $leadModel->saveEntity($lead, false);
            } catch (\Exception $exception) {
                $this->factory->getLogger()->addWarning($exception->getMessage());

                return null;
            }
        }

        return $lead;
    }

    /**
     * @param $data
     *
     * @return Company|void
     */
    public function getMauticCompany($data)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data), true);
        } elseif (is_string($data)) {
            // Assume JSON
            $data = json_decode($data, true);
        }
        // Match that data with mapped lead fields
        $config        = $this->mergeConfigToFeatureSettings();
        $aFields       = $this->getAvailableLeadFields($config);
        $matchedFields = [];
        foreach ($aFields['company'] as $k => $v) {
            foreach ($data as $dk => $dv) {
                if ($dk === $v['dv']) {
                    $matchedFields[$config['companyFields'][$k]] = $dv;
                }
            }
        }

        if (empty($matchedFields)) {
            return;
        }

        // Find unique identifier fields used by the integration
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $companyModel = $this->factory->getModel('lead.company');

        // Default to new company
        $company = new Company();
        $companyModel->setFieldValues($company, $matchedFields, false, false);
        $companyModel->saveEntity($company, false);

        return $company;
    }

    /**
     * Amend mapped lead data before creating to Mautic.
     *
     * @param array  $data
     * @param string $object
     *
     * @return array
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object = null)
    {
        if (isset($data['FL'])) {
            $data = [$data];
        }
        $fieldsValues = [];
        foreach ($data as $row) {
            if (isset($row['FL'])) {
                foreach ($row['FL'] as $field) {
                    $fieldsValues[$row['no'] - 1][$field['val']] = $field['content'];
                }
            }
        }

        return $fieldsValues;
    }

    /**
     * @param Form|FormBuilder $builder
     * @param array            $data
     * @param string           $formArea
     *
     * @throws \InvalidArgumentException
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder->add(
                'objects',
                'choice',
                [
                    'choices' => [
                        'Leads'    => 'mautic.zoho.object.lead',
                        'Contacts' => 'mautic.zoho.object.contact',
                        'company'  => 'mautic.zoho.object.account',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => $this->getTranslator()->trans('mautic.crm.form.objects_to_pull_from', ['%crm%' => 'Zoho']),
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
        }
    }

    /**
     * @param array $settings
     * @param array $parameters
     *
     * @return bool|string
     *
     * @throws \InvalidArgumentException
     */
    public function authCallback($settings = [], $parameters = [])
    {
        $request_url = 'https://accounts.zoho.com/apiauthtoken/nb/create';
        $parameters  = array_merge($parameters, [
            'SCOPE'    => 'ZohoCRM/crmapi',
            'EMAIL_ID' => $this->keys[$this->getClientIdKey()],
            'PASSWORD' => $this->keys[$this->getClientSecretKey()],
        ]);

        $response = $this->makeRequest($request_url, $parameters, 'GET', ['authorize_session' => true]);

        if ('FALSE' === $response['RESULT']) {
            return $this->factory->getTranslator()->trans('mautic.zoho.auth_error', ['%cause%' => isset($response['CAUSE']) ? $response['CAUSE'] : 'UNKNOWN']);
        }

        return $this->extractAuthKeys($response);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $data
     * @param bool   $postAuthorization
     *
     * @return mixed
     */
    public function parseCallbackResponse($data, $postAuthorization = false)
    {
        if ($postAuthorization) {
            /*
            #
            #Wed Feb 29 03:07:33 PST 2012
            AUTHTOKEN=bad18eba1ff45jk7858b8ae88a77fa30
            RESULT=TRUE
            */
            preg_match_all('(\w*=\w*)', $data, $matches);
            if (!empty($matches[0])) {
                /** @var array $match */
                $match      = $matches[0];
                $attributes = [];
                foreach ($match as $string_attribute) {
                    $parts                 = explode('=', $string_attribute);
                    $attributes[$parts[0]] = $parts[1];
                }

                return $attributes;
            }

            return [];
        }

        return parent::parseCallbackResponse($data, $postAuthorization);
    }

    /**
     * Get available company fields for choices in the config UI.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormCompanyFields($settings = [])
    {
        return $this->getFormFieldsByObject('Accounts', $settings);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields($settings = [])
    {
        $leadFields    = $this->getFormFieldsByObject('Leads', $settings);
        $contactFields = $this->getFormFieldsByObject('Contacts', $settings);

        return array_merge($leadFields, $contactFields);
    }

    /**
     * @param array $settings
     *
     * @return array|bool
     *
     * @throws ApiErrorException
     */
    public function getAvailableLeadFields($settings = [])
    {
        if ($fields = parent::getAvailableLeadFields($settings)) {
            return $fields;
        }

        $zohoFields        = [];
        $silenceExceptions = isset($settings['silence_exceptions']) ? $settings['silence_exceptions'] : true;

        if (isset($settings['feature_settings']['objects'])) {
            $zohoObjects = $settings['feature_settings']['objects'];
        } else {
            $settings    = $this->settings->getFeatureSettings();
            $zohoObjects = isset($settings['objects']) ? $settings['objects'] : ['Leads'];
        }

        try {
            if ($this->isAuthorized()) {
                if (!empty($zohoObjects) && is_array($zohoObjects)) {
                    foreach ($zohoObjects as $key => $zohoObject) {
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$zohoObject;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $zohoFields[$zohoObject] = $fields;

                            continue;
                        }
                        $leadObject = $this->getApiHelper()->getLeadFields($zohoObject);

                        if (null === $leadObject || (isset($leadObject['response']) && isset($leadObject['response']['error']))) {
                            return [];
                        }

                        $objKey = 'company' === $zohoObject ? 'Accounts' : $zohoObject;
                        /** @var array $opts */
                        $opts = $leadObject[$objKey]['section'];
                        foreach ($opts as $optgroup) {
                            //$zohoFields[$optgroup['dv']] = array();
                            if (!array_key_exists(0, $optgroup['FL'])) {
                                $optgroup['FL'] = [$optgroup['FL']];
                            }
                            foreach ($optgroup['FL'] as $field) {
                                if (!(bool) $field['isreadonly'] || in_array($field['type'], ['Lookup', 'OwnerLookup', 'Boolean'], true)) {
                                    continue;
                                }

                                $zohoFields[$zohoObject][$this->getFieldKey($field['dv'])] = [
                                    'type'     => 'string',
                                    'label'    => $field['label'],
                                    'dv'       => $field['dv'],
                                    'required' => $field['req'] === 'true',
                                ];
                            }
                        }

                        $this->cache->set('leadFields'.$cacheSuffix, $zohoFields[$zohoObject]);
                    }
                }
            }
        } catch (ApiErrorException $exception) {
            $this->logIntegrationError($exception);

            if (!$silenceExceptions) {
                if (strpos($exception->getMessage(), 'Invalid Ticket Id') !== false) {
                    // Use a bit more friendly message
                    $exception = new ApiErrorException('There was an issue with communicating with Zoho. Please try to reauthorize.');
                }

                throw $exception;
            }

            return false;
        }

        return $zohoFields;
    }

    /**
     * {@inheritdoc}
     *
     * @param $key
     * @param $field
     *
     * @return array
     */
    public function convertLeadFieldKey($key, $field)
    {
        return [$this->getFieldKey($field['dv']), $field['dv']];
    }

    /**
     * @param $lead
     * @param $config
     *
     * @return string
     */
    public function populateLeadData($lead, $config = [])
    {
        $config['object'] = 'Leads';
        $mappedData       = parent::populateLeadData($lead, $config);

        $xmlData = '<Leads>';
        $xmlData .= '<row no="1">';
        foreach ($mappedData as $name => $value) {
            $xmlData .= sprintf('<FL val="%s"><![CDATA[%s]]></FL>', $name, $this->cleanPushData($value));
        }
        $xmlData .= '</row>';
        $xmlData .= '</Leads>';

        return $xmlData;
    }

    /**
     * @param $dv
     *
     * @return string
     */
    protected function getFieldKey($dv)
    {
        return InputHelper::alphanum(InputHelper::transliterate($dv));
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function pushLeads($params = [])
    {
        $limit                 = $params['limit'];
        $config                = $this->mergeConfigToFeatureSettings();
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $fieldsToUpdateInZoho  = isset($config['update_mautic']) ? array_keys($config['update_mautic'], 1) : [];
        $leadFields            = array_unique(array_values($config['leadFields']));
        $totalUpdated          = $totalCreated          = $totalErrors          = 0;
        $leadModel             = $this->leadModel;
        $companyModel          = $this->companyModel;

        if (empty($leadFields)) {
            return [0, 0, 0];
        }

        $fields        = implode(', l.', $leadFields);
        $fields        = 'l.'.$fields;
        $originalLimit = $limit;
        $progress      = false;
        $totalToUpdate = array_sum($integrationEntityRepo->findLeadsToUpdate('Zoho', 'lead', $fields, false));
        $totalToCreate = $integrationEntityRepo->findLeadsToCreate('Zoho', $fields, false);
        $totalCount    = $totalToCreate + $totalToUpdate;

        if (defined('IN_MAUTIC_CONSOLE')) {
            // start with update
            if ($totalToUpdate + $totalToCreate) {
                $output = new ConsoleOutput();
                $output->writeln("About $totalToUpdate to update and about $totalToCreate to create/update");
                $progress = new ProgressBar($output, $totalCount);
            }
        }

        $limit       = $originalLimit;
        $leadsToSync = [];

        // Fetch them separately so we can determine
        $toUpdate = $integrationEntityRepo->findLeadsToUpdate('Zoho', 'lead', $fields, $limit, 'Lead', [])['Lead'];
        $totalCount -= count($toUpdate);
        $totalUpdated += count($toUpdate);
        foreach ($toUpdate as $lead) {
            if (isset($lead['email']) && !empty($lead['email'])) {
                $key               = mb_strtolower($this->cleanPushData($lead['email']));
                $leadsToSync[$key] = $lead;

                if ($progress) {
                    $progress->advance();
                }
            }
        }
        unset($toUpdate);

        //create lead records
        $leadsToCreate = $integrationEntityRepo->findLeadsToCreate('Zoho', $fields, $limit);
        $totalCount -= count($leadsToCreate);
        $totalCreated += count($leadsToCreate);
        foreach ($leadsToCreate as $lead) {
            if (isset($lead['email']) && !empty($lead['email'])) {
                $key               = mb_strtolower($this->cleanPushData($lead['email']));
                $leadsToSync[$key] = $lead;
            }

            if ($progress) {
                $progress->advance();
            }
        }
        unset($leadsToCreate);

        $config     = $this->mergeConfigToFeatureSettings();
        $aFields    = $this->getAvailableLeadFields($config);
        $rowid      = 1;
        $mappedData = [];
        $xmlData    = '<Leads>';
        foreach ($leadsToSync as $email => $lead) {
            $xmlData .= '<row no="'.($rowid++).'">';
            // Match that data with mapped lead fields
            foreach ($config['leadFields'] as $k => $v) {
                foreach ($lead as $dk => $dv) {
                    if ($v === $dk) {
                        if ($dv) {
                            $mappedData[$aFields['Leads'][$k]['dv']] = $dv;
                        }
                    }
                }
            }
            foreach ($mappedData as $name => $value) {
                $xmlData .= sprintf('<FL val="%s"><![CDATA[%s]]></FL>', $name, $this->cleanPushData($value));
            }
            $xmlData .= '</row>';
        }
        $xmlData .= '</Leads>';

        $this->getApiHelper()->createLead($xmlData);

        if ($progress) {
            $progress->finish();
            $output->writeln('');
        }

        return [$totalUpdated, $totalCreated, $totalErrors];
    }
}
