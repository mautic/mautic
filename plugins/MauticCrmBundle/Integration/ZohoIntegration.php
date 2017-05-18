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
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\StageBundle\Entity\Stage;
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
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://crm.zoho.com/crm/private/json';
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
     * @param array $params
     * @param null  $query
     *
     * @return int
     */
    public function getLeads($params = [], $query = null)
    {
        $executed = 0;

        try {
            if ($this->isAuthorized()) {
                $config                         = $this->mergeConfigToFeatureSettings();
                $fields                         = implode('&property=', array_keys($config['leadFields']));
                $params['post_append_to_query'] = '&property='.$fields.'&property=lifecyclestage';

                $data = $this->getApiHelper()->getLeads($params);
                if (isset($data['Leads'])) {
                    /** @var array $leads */
                    $leads = $data['Leads'];
                    foreach ($leads as $lead) {
                        if (is_array($lead)) {
                            $leadData = $this->amendLeadDataBeforeMauticPopulate($lead, 'Lead');
                            $lead     = $this->getMauticLead($leadData);
                            if ($lead) {
                                ++$executed;
                            }
                        }
                    }
                    if ($data['has-more']) {
                        $params['vidOffset']  = $data['vid-offset'];
                        $params['timeOffset'] = $data['time-offset'];
                        $executed += $this->getLeads($params);
                    }
                }

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    /**
     * @param array $params
     * @param bool  $id
     *
     * @return int
     */
    public function getCompanies(array $params = [], $id = false)
    {
        $executed = 0;
        $results  = [];
        try {
            if ($this->isAuthorized()) {
                $data = $this->getApiHelper()->getCompanies($params, $id);
                if ($id) {
                    $results['results'][] = array_merge($results, $data);
                } else {
                    $results['results'] = array_merge($results, $data['results']);
                }
                if (isset($results['results'])) {
                    foreach ($results['results'] as $company) {
                        if (isset($company['properties'])) {
                            $companyData = $this->amendLeadDataBeforeMauticPopulate($company, null);
                            $company     = $this->getMauticCompany($companyData);
                            if ($id) {
                                return $company;
                            }
                            if ($company) {
                                ++$executed;
                            }
                        }
                    }
                    if (isset($data['hasMore']) && $data['hasMore']) {
                        $params['vidOffset']  = $data['vid-offset'];
                        $params['timeOffset'] = $data['time-offset'];
                        $executed += $this->getCompanies($params);
                    }
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

        if (isset($data['lifecyclestage'])) {
            $stageName = $data['lifecyclestage'];
            unset($data['lifecyclestage']);
        }

        if (isset($data['associatedcompanyid'])) {
            $company = $this->getCompanies([], $data['associatedcompanyid']);
            unset($data['associatedcompanyid']);
        }

        // Match that data with mapped lead fields
        $matchedFields = $this->populateMauticLeadData($data);

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

        if (isset($stageName)) {
            $stage = $this->factory->getEntityManager()->getRepository('MauticStageBundle:Stage')->getStageByName($stageName);

            if (empty($stage)) {
                $stage = new Stage();
                $stage->setName($stageName);
                $stages[$stageName] = $stage;
            }
            if (!$lead->getStage() && $lead->getStage() != $stage) {
                $lead->setStage($stage);

                //add a contact stage change log
                $log = new StagesChangeLog();
                $log->setStage($stage);
                $log->setEventName($stage->getId().':'.$stage->getName());
                $log->setLead($lead);
                $log->setActionName(
                    $this->factory->getTranslator()->trans(
                        'mautic.stage.import.action.name',
                        [
                            '%name%' => $this->factory->getUser()->getUsername(),
                        ]
                    )
                );
                $log->setDateAdded(new \DateTime());
                $lead->stageChangeLog($log);
            }
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
     * @param $object
     *
     * @return mixed
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object)
    {
        $fieldsValues = [];
        foreach ($data['properties'] as $key => $field) {
            $fieldsValues[$key] = $field['value'];
        }
        if ('Lead' === $object && !isset($fieldsValues['email'])) {
            foreach ($data['identity-profiles'][0]['identities'] as $identifiedProfile) {
                if ('EMAIL' === $identifiedProfile['type']) {
                    $fieldsValues['email'] = $identifiedProfile['value'];
                }
            }
        }

        return $fieldsValues;
    }

    /**
     * Format the lead data to the structure that Zoho requires for the createOrUpdate request.
     *
     * @param array $leadData All the lead fields mapped
     * @param $lead
     * @param bool $updateLink
     *
     * @return array
     *
     * @throws \Symfony\Component\Routing\Exception\RouteNotFoundException
     * @throws \Symfony\Component\Routing\Exception\MissingMandatoryParametersException
     * @throws \Symfony\Component\Routing\Exception\InvalidParameterException
     */
    public function formatLeadDataForCreateOrUpdate($leadData, $lead, $updateLink = false)
    {
        $formattedLeadData = [];

        if (!$updateLink) {
            foreach ($leadData as $field => $value) {
                if ($field === 'lifecyclestage' || $field === 'associatedcompanyid') {
                    continue;
                }
                $formattedLeadData['properties'][] = [
                    'property' => $field,
                    'value'    => $value,
                ];
            }
        }

        if ($lead && !empty($lead->getId())) {
            //put mautic timeline link
            $formattedLeadData['properties'][] = [
                'property' => 'mautic_timeline',
                'value'    => $this->factory->getRouter()->generate(
                    'mautic_plugin_timeline_view',
                    ['integration' => 'Zoho', 'leadId' => $lead->getId()],
                    UrlGeneratorInterface::ABSOLUTE_URL),
            ];
        }

        return $formattedLeadData;
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
        $mappedData = parent::populateLeadData($lead, $config);

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
}
