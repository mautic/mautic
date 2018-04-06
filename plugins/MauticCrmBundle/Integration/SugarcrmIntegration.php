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

use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class SugarcrmIntegration.
 */
class SugarcrmIntegration extends CrmAbstractIntegration
{
    private $objects = [
        'Leads',
        'Contacts',
        'Accounts',
    ];

    private $sugarDncKeys = ['email_opt_out', 'invalid_email'];
    private $authorzationError;

    /**
     * @var DoNotContact
     */
    protected $doNotContactModel;

    /**
     * SugarcrmIntegration constructor.
     *
     * @param DoNotContact $doNotContactModel
     */
    public function __construct(DoNotContact $doNotContactModel)
    {
        $this->doNotContactModel = $doNotContactModel;

        parent::__construct();
    }

    /**
     * Returns the name of the social integration that must match the name of the file.
     *
     * @return string
     */
    public function getName()
    {
        return 'Sugarcrm';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        //Version 6.x supports all features
        if (isset($this->keys['version']) && $this->keys['version'] == '6') {
            return ['push_lead', 'get_leads', 'push_leads'];
        }
        //Only push_lead is currently supported for version 7
        return ['push_lead', 'get_leads', 'push_leads'];
    }

    /**
     * Get the array key for clientId.
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_id';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'client_secret';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSecretKeys()
    {
        return [
            'client_secret',
            'password',
        ];
    }

    /**
     * Get the array key for the auth token.
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        return (isset($this->keys['version']) && $this->keys['version'] == '6') ? 'id' : 'access_token';
    }

    /**
     * SugarCRM 7 refresh tokens.
     */
    public function getRefreshTokenKeys()
    {
        return [
            'refresh_token',
            'expires',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        $apiUrl = ($this->keys['version'] == '6') ? 'service/v4_1/rest.php' : 'rest/v10/oauth2/token';

        return sprintf('%s/%s', $this->keys['sugarcrm_url'], $apiUrl);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthLoginUrl()
    {
        return $this->router->generate('mautic_integration_auth_callback', ['integration' => $this->getName()]);
    }

    /**
     * Retrieves and stores tokens returned from oAuthLogin.
     *
     * @param array $settings
     * @param array $parameters
     *
     * @return array
     */
    public function authCallback($settings = [], $parameters = [])
    {
        if (isset($this->keys['version']) && $this->keys['version'] == '6') {
            $success = $this->isAuthorized();
            if (!$success) {
                return $this->authorzationError;
            } else {
                return false;
            }
        } else {
            $settings = [
                'grant_type'         => 'password',
                'ignore_redirecturi' => true,
            ];
            $parameters = [
                'username' => $this->keys['username'],
                'password' => $this->keys['password'],
                'platform' => 'base',
            ];

            return parent::authCallback($settings, $parameters);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'sugarcrm_url'  => 'mautic.sugarcrm.form.url',
            'client_id'     => 'mautic.sugarcrm.form.clientkey',
            'client_secret' => 'mautic.sugarcrm.form.clientsecret',
            'username'      => 'mautic.sugarcrm.form.username',
            'password'      => 'mautic.sugarcrm.form.password',
        ];
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
        return $this->getFormFieldsByObject('company', $settings);
    }

    /**
     * Get available fields for choices in the config UI.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormLeadFields($settings = [])
    {
        if (!$this->isAuthorized()) {
            return [];
        }

        // combine keys with values
        $settings['feature_settings']['objects'] = array_combine(array_values($settings['feature_settings']['objects']),
            $settings['feature_settings']['objects']);

        // unset company object
        if (isset($settings['feature_settings']['objects']['company'])) {
            unset($settings['feature_settings']['objects']['company']);
        }

        if (empty($settings['feature_settings']['objects'])) {
            // BC force add Leads and Contacts from Integration
            $settings['feature_settings']['objects']['Leads']    = 'Leads';
            $settings['feature_settings']['objects']['Contacts'] = 'Contacts';
        }

        $fields = [];
        // merge all arrays from level 1
        $fieldsromObjects = $this->getAvailableLeadFields($settings);
        foreach ($fieldsromObjects as $fieldsFromObject) {
            $fields = array_merge($fields, $fieldsFromObject);
        }

        return $fields;
    }

    /**
     * @param array $settings
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getAvailableLeadFields($settings = [])
    {
        $sugarFields       = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        $sugarObjects      = [];

        if (!empty($settings['feature_settings']['objects'])) {
            $sugarObjects = $settings['feature_settings']['objects'];
        } else {
            $sugarObjects['Leads']                   = 'Leads';
            $sugarObjects['Contacts']                = 'Contacts';
            $settings['feature_settings']['objects'] = $sugarObjects;
        }

        $isRequired = function (array $field, $object) {
            switch (true) {
                case 'Leads' === $object && ('webtolead_email1' === $field['name'] || 'email1' === $field['name']):
                    return true;
                case 'Contacts' === $object && 'email1' === $field['name']:
                    return true;
                case 'id' !== $field['name'] && !empty($field['required']):
                    return true;
                default:
                    return false;
            }
        };

        try {
            if (!empty($sugarObjects) and is_array($sugarObjects)) {
                foreach ($sugarObjects as $key => $sObject) {
                    if ('Accounts' === $sObject) {
                        // Match Sugar object to Mautic's
                        $sObject = 'company';
                    }
                    $sObject = trim($sObject);
                    if ($this->isAuthorized()) {
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$sObject;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            if (('company' === $sObject && isset($fields['id'])) || isset($fields['id__'.$sObject])) {
                                $sugarFields[$sObject] = $fields;
                                continue;
                            }
                        }
                        if (!isset($sugarFields[$sObject])) {
                            $fields = $this->getApiHelper()->getLeadFields($sObject);

                            if ($fields != null && !empty($fields)) {
                                if (isset($fields['module_fields']) && !empty($fields['module_fields'])) {
                                    //6.x/community

                                    foreach ($fields['module_fields'] as $fieldInfo) {
                                        if (isset($fieldInfo['name']) && (!in_array($fieldInfo['type'], ['id', 'assigned_user_name', 'bool', 'link', 'relate'])
                                                ||
                                                ($fieldInfo['type'] == 'id' && $fieldInfo['name'] == 'id')
                                            )) {
                                            $type      = 'string';
                                            $fieldName = (strpos($fieldInfo['name'], 'webtolead_email') === false) ? $fieldInfo['name'] : str_replace('webtolead_', '', $fieldInfo['name']);
                                            // make these congruent as some come in with colons and some do not
                                            $label = str_replace(':', '', $fieldInfo['label']);
                                            if ($sObject !== 'company') {
                                                $sugarFields[$sObject][$fieldName.'__'.$sObject] = [
                                                    'type'        => $type,
                                                    'label'       => $sObject.'-'.$label,
                                                    'required'    => $isRequired($fieldInfo, $sObject),
                                                    'group'       => $sObject,
                                                    'optionLabel' => $fieldInfo['label'],
                                                ];
                                            } else {
                                                $sugarFields[$sObject][$fieldName] = [
                                                    'type'     => $type,
                                                    'label'    => $label,
                                                    'required' => $isRequired($fieldInfo, $sObject),
                                                ];
                                            }
                                        }
                                    }
                                } elseif (isset($fields['fields']) && !empty($fields['fields'])) {
                                    //7.x
                                    foreach ($fields['fields'] as $fieldInfo) {
                                        if (isset($fieldInfo['name']) && empty($fieldInfo['readonly'])
                                            && (!in_array(
                                                    $fieldInfo['type'],
                                                    ['id', 'team_list', 'bool', 'link', 'relate']
                                                )
                                                ||
                                                ($fieldInfo['type'] == 'id' && $fieldInfo['name'] == 'id')
                                            )
                                        ) {
                                            if (!empty($fieldInfo['comment'])) {
                                                $label = $fieldInfo['comment'];
                                            } elseif (!empty($fieldInfo['help'])) {
                                                $label = $fieldInfo['help'];
                                            } else {
                                                $label = ucfirst(str_replace('_', ' ', $fieldInfo['name']));
                                            }
                                            // make these congruent as some come in with colons and some do not
                                            $label = str_replace(':', '', $label);

                                            $fieldName = (strpos($fieldInfo['name'], 'webtolead_email') === false)
                                                ? $fieldInfo['name']
                                                : str_replace(
                                                    'webtolead_',
                                                    '',
                                                    $fieldInfo['name']
                                                );

                                            $type = 'string';
                                            if ($sObject !== 'company') {
                                                $sugarFields[$sObject][$fieldName.'__'.$sObject] = [
                                                    'type'        => $type,
                                                    'label'       => $sObject.'-'.$label,
                                                    'required'    => $isRequired($fieldInfo, $sObject),
                                                    'group'       => $sObject,
                                                    'optionLabel' => $label,
                                                ];
                                            } else {
                                                $sugarFields[$sObject][$fieldName] = [
                                                    'type'     => $type,
                                                    'label'    => $label,
                                                    'required' => $isRequired($fieldInfo, $sObject),
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                            $this->cache->set('leadFields'.$cacheSuffix, $sugarFields[$sObject]);
                        }
                    } else {
                        throw new ApiErrorException($this->authorzationError);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $sugarFields;
    }

    /**
     * @param $params
     *
     * @return mixed
     */
    public function getFetchQuery($params)
    {
        $dateRange = $params;

        return $dateRange;
    }

    /**
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
     */
    public function getCompanies($params = [], $query = null, $executed = null)
    {
        $executed = null;

        $sugarObject           = 'Accounts';
        $params['max_results'] = 100;
        if (!isset($params['offset'])) {
            //First call
            $params['offset'] = 0;
        }

        $query = $params;

        try {
            if ($this->isAuthorized()) {
                $result           = $this->getApiHelper()->getLeads($query, $sugarObject);
                $params['offset'] = $result['next_offset'];
                $executed += $this->amendLeadDataBeforeMauticPopulate($result, $sugarObject);
                if (
                    (isset($result['total_count']) && $result['total_count'] > $params['offset'])   //Sugar 6
                    || (!isset($result['total_count']) && $params['offset'] > -1)) {            //Sugar 7
                    $result = null;
                    $executed += $this->getCompanies($params, null, $executed);
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
     *
     * @return int|null
     *
     * @throws \Exception
     *                    To be modified
     */
    public function pushLeadActivity($params = [])
    {
        $executed = null;

        $query  = $this->getFetchQuery($params);
        $config = $this->mergeConfigToFeatureSettings([]);

        /** @var SugarApi $apiHelper */
        $apiHelper = $this->getApiHelper();

        $sugarObjects[] = 'Leads';
        if (isset($config['objects']) && !empty($config['objects'])) {
            $sugarObjects = $config['objects'];
        }

        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $startDate             = new \DateTime($query['start']);
        $endDate               = new \DateTime($query['end']);
        $limit                 = 100;

        foreach ($sugarObjects as $object) {
            try {
                if ($this->isAuthorized()) {
                    // Get first batch
                    $start    = 0;
                    $sugarIds = $integrationEntityRepo->getIntegrationsEntityId(
                        'Sugarcrm',
                        $object,
                        'lead',
                        null,
                        $startDate->format('Y-m-d H:m:s'),
                        $endDate->format('Y-m-d H:m:s'),
                        true,
                        $start,
                        $limit
                    );

                    while (!empty($sugarIds)) {
                        $executed += count($sugarIds);

                        // Extract a list of lead Ids
                        $leadIds = [];
                        foreach ($sugarIds as $ids) {
                            $leadIds[] = $ids['internal_entity_id'];
                        }

                        // Collect lead activity for this batch
                        $leadActivity = $this->getLeadData(
                            $startDate,
                            $endDate,
                            $leadIds
                        );

                        $saugarLeadData = [];
                        foreach ($sugarIds as $ids) {
                            $leadId = $ids['internal_entity_id'];
                            if (isset($leadActivity[$leadId])) {
                                $sugarId                            = $ids['integration_entity_id'];
                                $sugarLeadData[$sugarId]            = $leadActivity[$leadId];
                                $sugarLeadData[$sugarId]['id']      = $ids['integration_entity_id'];
                                $sugarLeadData[$sugarId]['leadId']  = $ids['internal_entity_id'];
                                $sugarLeadData[$sugarId]['leadUrl'] = $this->factory->getRouter()->generate(
                                    'mautic_plugin_timeline_view',
                                    ['integration' => 'Sugarcrm', 'leadId' => $leadId],
                                    UrlGeneratorInterface::ABSOLUTE_URL
                                );
                            }
                        }

                        if (!empty($sugarLeadData)) {
                            $apiHelper->createLeadActivity($sugarLeadData, $object);
                        }

                        // Get the next batch
                        $start += $limit;
                        $sugarIds = $integrationEntityRepo->getIntegrationsEntityId(
                            'Sugarcrm',
                            $object,
                            'lead',
                            null,
                            $startDate->format('Y-m-d H:m:s'),
                            $endDate->format('Y-m-d H:m:s'),
                            true,
                            $start,
                            $limit
                        );
                    }
                }
            } catch (\Exception $e) {
                $this->logIntegrationError($e);
            }
        }

        return $executed;
    }

    /**
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
     */
    public function getLeads($params = [], $query = null, &$executed = null, $result = [], $object = 'Leads')
    {
        $params['max_results'] = 100;
        $config                = $this->mergeConfigToFeatureSettings([]);

        if (!isset($params['offset'])) {
            //First call
            $params['offset'] = 0;
        }
        $query = $params;

        try {
            if ($this->isAuthorized()) {
                if ($object !== 'Activity' and $object !== 'company') {
                    $result           = $this->getApiHelper()->getLeads($query, $object);
                    $params['offset'] = $result['next_offset'];
                    $executed += $this->amendLeadDataBeforeMauticPopulate($result, $object);
                    if (
                        (isset($result['total_count']) && $result['total_count'] > $params['offset'])   //Sugar 6
                        || (!isset($result['total_count']) && $params['offset'] > -1)) {            //Sugar 7
                        $params['object'] = $object;
                        $executed += $this->getLeads($params, null, $executed, [], $object);
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
     * @param $response
     *
     * @return string
     */
    public function getErrorsFromResponse($response)
    {
        if ($this->keys['version'] == '6') {
            if (!empty($response['name'])) {
                return $response['description'];
            } else {
                return $this->translator->trans('mautic.integration.error.genericerror', [], 'flashes');
            }
        } else {
            return parent::getErrorsFromResponse($response);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return (isset($this->keys['version']) && $this->keys['version'] == '6') ? 'rest' : 'oauth2';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function getDataPriority()
    {
        return true;
    }

    /**
     * @param $url
     * @param $parameters
     * @param $method
     * @param $settings
     * @param $authType
     *
     * @return array
     */
    public function prepareRequest($url, $parameters, $method, $settings, $authType)
    {
        if ($authType == 'oauth2' && empty($settings['authorize_session']) && isset($this->keys['access_token'])) {
            // Append the access token as the oauth-token header
            $headers = [
                "oauth-token: {$this->keys['access_token']}",
            ];

            return [$parameters, $headers];
        } else {
            return parent::prepareRequest($url, $parameters, $method, $settings, $authType);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized()
    {
        if (!$this->isConfigured()) {
            return false;
        }

        if (!isset($this->keys['version'])) {
            return false;
        }

        if ($this->keys['version'] == '6') {
            $loginParams = [
                'user_auth' => [
                    'user_name' => $this->keys['username'],
                    'password'  => md5($this->keys['password']),
                    'version'   => '1',
                ],
                'application_name' => 'Mautic',
                'name_value_list'  => [],
                'method'           => 'login',
                'input_type'       => 'JSON',
                'response_type'    => 'JSON',
            ];
            $parameters = [
                'method'        => 'login',
                'input_type'    => 'JSON',
                'response_type' => 'JSON',
                'rest_data'     => json_encode($loginParams),
            ];

            $settings['auth_type']         = 'rest';
            $settings['authorize_session'] = true;

            $response = $this->makeRequest($this->getAccessTokenUrl(), $parameters, 'GET', $settings);

            unset($response['module'], $response['name_value_list']);
            $error = $this->extractAuthKeys($response, 'id');

            $this->authorzationError = $error;

            return empty($error);
        } else {
            if ($this->isConfigured()) {
                // SugarCRM 7 uses password grant type so login each time to ensure session is valid
                $this->authCallback();
            }

            return parent::isAuthorized();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $data
     */
    public function prepareResponseForExtraction($data)
    {
        // Extract expiry and set expires for 7.x
        if (is_array($data) && isset($data['expires_in'])) {
            $data['expires'] = $data['expires_in'] + time();
        }

        return $data;
    }

    /**
     * Amend mapped lead data before creating to Mautic.
     *
     * @param array  $data
     * @param string $object
     *
     * @return int
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object)
    {
        $settings['feature_settings']['objects'][] = $object;
        $fields                                    = array_keys($this->getAvailableLeadFields($settings));
        $params['fields']                          = implode(',', $fields);

        $count  = 0;
        $entity = null;

        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $companyRepo           = $this->em->getRepository('MauticLeadBundle:Company');

        $sugarRejectedLeads = [];
        if (isset($data['entry_list'])) {
            $SUGAR_VERSION     = '6';
            $RECORDS_LIST_NAME = 'entry_list';
            $MODULE_FIELD_NAME = 'module_name';
        }
        if (isset($data['records'])) {
            $SUGAR_VERSION     = '7';
            $RECORDS_LIST_NAME = 'records';
            $MODULE_FIELD_NAME = '_module';
        }

        if (isset($data[$RECORDS_LIST_NAME]) and $object !== 'Activity') {
            //Get assigned user ids
            $assignedUserIds            = [];
            $onwerEmailByAssignedUserId = [];
            if ($object == 'Leads' || $object == 'Contacts' || $object == 'Accounts') {
                foreach ($data[$RECORDS_LIST_NAME] as $key => $record) {
                    if ($SUGAR_VERSION == '6') {
                        foreach ($record['name_value_list'] as $item) {
                            if ($item['name'] == 'assigned_user_id' && $item['value'] && $item['value'] != '') {
                                $assignedUserIds[] = $item['value'];
                            }
                        }
                    } else {
                        if (isset($record['assigned_user_id']) && $record['assigned_user_id'] != '') {
                            $assignedUserIds[] = $record['assigned_user_id'];
                        }
                    }
                }
            }
            if (!empty($assignedUserIds)) {
                $assignedUserIds            = array_unique($assignedUserIds);
                $onwerEmailByAssignedUserId = $this->getApiHelper()->getEmailBySugarUserId(['ids' => $assignedUserIds]);
            }

            //Get all leads emails
            $checkEmailsInSugar = [];
            if ($object == 'Leads') {
                if ($SUGAR_VERSION == '6') {
                    foreach ($data[$RECORDS_LIST_NAME] as $key => $record) {
                        foreach ($record['name_value_list'] as $item) {
                            if ($item['name'] == 'email1' && $item['value'] && $item['value'] != '') {
                                $checkEmailsInSugar[] = $item['value'];
                            }
                        }
                    }
                } else {
                    if (isset($record['email1']) && $record['email1'] != '') {
                        $checkEmailsInSugar[] = $record['email1'];
                    }
                }
            }
            if (!empty($checkEmailsInSugar)) {
                $sugarLeads = $this->getApiHelper()->getLeads(['checkemail_contacts' => $checkEmailsInSugar, 'offset' => 0, 'max_results' => 1000], 'Contacts');
                if (isset($sugarLeads[$RECORDS_LIST_NAME])) {
                    foreach ($sugarLeads[$RECORDS_LIST_NAME] as $k => $record) {
                        $sugarLeadRecord = [];
                        if ($SUGAR_VERSION == '6') {
                            foreach ($record['name_value_list'] as $item) {
                                if ($item['name'] == 'email1' && $item['value'] && $item['value'] != '') {
                                    $sugarRejectedLeads[] = $item['value'];
                                }
                            }
                        } else {
                            if (isset($record['email1']) && $record['email1'] != '') {
                                $sugarRejectedLeads[] = $record['email1'];
                            }
                        }
                    }
                }
            }

            foreach ($data[$RECORDS_LIST_NAME] as $key => $record) {
                $integrationEntities = [];
                $dataObject          = [];
                if (isset($record[$MODULE_FIELD_NAME]) && $record[$MODULE_FIELD_NAME] == 'Accounts') {
                    $newName = '';
                } else {
                    $newName = '__'.$object;
                }
                if ($SUGAR_VERSION == '6') {
                    foreach ($record['name_value_list'] as $item) {
                        if ($object !== 'Activity') {
                            if ($this->checkIfSugarCrmMultiSelectString($item['value'])) {
                                $convertedMultiSelectString         = $this->convertSuiteCrmToMauticMultiSelect($item['value']);
                                $dataObject[$item['name'].$newName] = $convertedMultiSelectString;
                            } else {
                                $dataObject[$item['name'].$newName] = $item['value'];
                            }
                            if ($item['name'] == 'date_entered') {
                                $itemDateEntered = new \DateTime($item['value']);
                            }
                            if ($item['name'] == 'date_modified') {
                                $itemDateModified = new \DateTime($item['value']);
                            }
                        }
                    }
                } else {
                    if ($object !== 'Activity') {
                        if (isset($record['date_entered']) && $record['date_entered'] != '') {
                            $itemDateEntered = new \DateTime($record['date_entered']);
                        }
                        if (isset($record['date_modified']) && $record['date_modified'] != '') {
                            $itemDateEntered = new \DateTime($record['date_modified']);
                        }
                        foreach ($record as $k => $item) {
                            $dataObject[$k.$newName] = $item;
                        }
                    }
                }
                if ($object == 'Leads' && isset($dataObject['email1__Leads']) && $dataObject['email1__Leads'] != null
                    && $dataObject['email1__Leads'] != '' && in_array($dataObject['email1__Leads'], $sugarRejectedLeads)) {
                    continue; //Lead email is already in Sugar Contacts. Do not carry on
                }
                //$itemLastDate = $itemDateModified;
                //if ($itemDateEntered > $itemLastDate) {
                //    $itemLastDate = $itemDateEntered;
                //}

                if (isset($dataObject) && $dataObject && !empty($dataObject)) {
                    if ($object == 'Leads' or $object == 'Contacts') {
                        $email_present = false;
                        $email         = null;

                        if (isset($dataObject['assigned_user_id'.'__'.$object])) {
                            $auid = $dataObject['assigned_user_id'.'__'.$object];
                            if (isset($onwerEmailByAssignedUserId[$auid])) {
                                $dataObject['owner_email'] = $onwerEmailByAssignedUserId[$auid];
                            }
                        }
                        $mauticObjectReference = 'lead';
                        $entity                = $this->getMauticLead($dataObject, true, null, null, $object);
                        $detachClass           = Lead::class;
                        $company               = null;
                        if ($entity && isset($dataObject['account_id'.$newName]) && trim($dataObject['account_id'.$newName]) != '') {
                            $integrationCompanyEntity = $integrationEntityRepo->findOneBy(
                                [
                                    'integration'         => 'Sugarcrm',
                                    'integrationEntity'   => 'Accounts',
                                    'internalEntity'      => 'company',
                                    'integrationEntityId' => $dataObject['account_id'.$newName],
                                ]
                            );
                            if (isset($integrationCompanyEntity)) {
                                $companyId    = $integrationCompanyEntity->getInternalEntityId();
                                $companyModel = $this->factory->getModel('lead.company');
                                $company      = $companyRepo->find($companyId);

                                $companyModel->addLeadToCompany($company, $entity);
                                $this->em->clear(Company::class);
                                $this->em->detach($entity);
                            }
                        }
                    } elseif ($object == 'Accounts') {
                        $entity                = $this->getMauticCompany($dataObject, null);
                        $detachClass           = Company::class;
                        $mauticObjectReference = 'company';
                    } else {
                        $this->logIntegrationError(
                            new \Exception(
                                sprintf('Received an unexpected object without an internalObjectReference "%s"', $object)
                            )
                        );

                        continue;
                    }

                    if ($entity) {
                        $integrationId = $integrationEntityRepo->getIntegrationsEntityId(
                            'Sugarcrm',
                            $object,
                            $mauticObjectReference,
                            $entity->getId()
                        );

                        if ($integrationId == null) {
                            $integrationEntity = new IntegrationEntity();
                            $integrationEntity->setDateAdded(new \DateTime());
                            $integrationEntity->setLastSyncDate(new \DateTime());
                            $integrationEntity->setIntegration('Sugarcrm');
                            $integrationEntity->setIntegrationEntity($object);
                            $integrationEntity->setIntegrationEntityId($record['id']);
                            $integrationEntity->setInternalEntity($mauticObjectReference);
                            $integrationEntity->setInternalEntityId($entity->getId());
                            $integrationEntities[] = $integrationEntity;
                        } else {
                            $integrationEntity = $integrationEntityRepo->getEntity($integrationId[0]['id']);
                            $integrationEntity->setLastSyncDate(new \DateTime());
                            $integrationEntities[] = $integrationEntity;
                        }
                        $this->em->detach($entity);
                        $this->em->clear($detachClass);
                        unset($entity);
                    } else {
                        continue;
                    }
                    ++$count;
                }

                $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($integrationEntities);
                $this->em->clear('Mautic\PluginBundle\Entity\IntegrationEntity');
            }
            unset($data);
            unset($integrationEntities);
            unset($dataObject);
        }

        return $count;
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'keys') {
            $builder->add('version', 'button_group', [
                'choices' => [
                    '6' => '6.x/community',
                    '7' => '7.x',
                ],
                'label'       => 'mautic.sugarcrm.form.version',
                'constraints' => [
                    new NotBlank([
                        'message' => 'mautic.core.value.required',
                    ]),
                ],
                'required' => true,
            ]);
        }
        if ($formArea == 'features') {
            $builder->add(
                'updateOwner',
                'choice',
                [
                    'choices' => [
                        'updateOwner' => 'mautic.sugarcrm.updateOwner',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.sugarcrm.form.updateOwner',
                    'label_attr'  => ['class' => 'control-label'],
                    'empty_value' => false,
                    'required'    => false,
                    'attr'        => [
                        'onclick' => 'Mautic.postForm(mQuery(\'form[name="integration_details"]\'),\'\');',
                    ],
                ]
            );

            $builder->add(
                'updateDnc',
                'choice',
                [
                    'choices' => [
                        'updateDnc' => 'mautic.sugarcrm.updateDnc',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.sugarcrm.form.updateDnc',
                    'label_attr'  => ['class' => 'control-label'],
                    'empty_value' => false,
                    'required'    => false,
                    'attr'        => [
                        'onclick' => 'Mautic.postForm(mQuery(\'form[name="integration_details"]\'),\'\');',
                    ],
                ]
            );

            $builder->add(
                'updateBlanks',
                'choice',
                [
                    'choices' => [
                        'updateBlanks' => 'mautic.integrations.blanks',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.integrations.form.blanks',
                    'label_attr'  => ['class' => 'control-label'],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );

            $builder->add(
                'objects',
                'choice',
                [
                    'choices' => [
                        'Leads'    => 'mautic.sugarcrm.object.lead',
                        'Contacts' => 'mautic.sugarcrm.object.contact',
                        'company'  => 'mautic.sugarcrm.object.company',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.sugarcrm.form.objects_to_pull_from',
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );

            $builder->add(
                'activityEvents',
                ChoiceType::class,
                [
                    'choices'    => $this->leadModel->getEngagementTypes(),
                    'label'      => 'mautic.salesforce.form.activity_included_events',
                    'label_attr' => [
                        'class'       => 'control-label',
                        'data-toggle' => 'tooltip',
                        'title'       => $this->translator->trans('mautic.salesforce.form.activity.events.tooltip'),
                    ],
                    'multiple'   => true,
                    'empty_data' => ['point.gained', 'form.submitted', 'email.read'], // BC with pre 2.11.0
                    'required'   => false,
                ]
            );
        }
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        $settings                           = parent::getFormSettings();
        $settings['requires_callback']      = false;
        $settings['requires_authorization'] = true;
        //'requires_callback'      => false,
        //'requires_authorization' => true,
        return $settings;
    }

    /**
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @param array                          $config
     *
     * @return array|bool
     */
    public function pushLead($lead, $config = [])
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return [];
        }

        $object = 'Leads'; //Sugar objects, default is Leads

        //Check if lead has alredy been synched
        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        //Check if it is a sugar CRM alredy synched lead
        $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Sugarcrm', $object, 'lead', $lead->getId());
        if (empty($integrationId)) {
            //Check if it is a sugar CRM alredy synched lead
            $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Sugarcrm', 'Contacts', 'lead', $lead->getId());
            if (!empty($integrationId)) {
                $object = 'Contacts';
            }
        }
        if (!empty($integrationId)) {
            $integrationEntity = $integrationEntityRepo->getEntity($integrationId[0]['id']);
            $lastSyncDate      = $integrationEntity->getLastSyncDate();
            $addedSyncDate     = $integrationEntity->getDateAdded();
            if ($addedSyncDate > $lastSyncDate) {
                $lastSyncDate = $addedSyncDate;
            }

            $leadDateModified = $lead->getDateModified();
            $leadDateAdded    = $lead->getDateAdded();
            $leadLastDate     = $leadDateModified;
            if ($leadDateAdded > $leadDateModified) {
                $leadLastDate = $leadDateAdded;
            }

            if ($lastSyncDate >= $leadLastDate) {
                return false;
            } //Do not push lead if it was already synched
        }
        $fields = array_keys($config['leadFields']);

        $leadFields = $this->cleanSugarData($config, $fields, $object);

        $mappedData[$object] = $this->populateLeadData($lead, ['leadFields' => $leadFields, 'object' => $object]);

        $this->amendLeadDataBeforePush($mappedData[$object]);

        if (empty($mappedData[$object])) {
            return false;
        }

        if (!empty($integrationId)) {
            $integrationEntity = $integrationEntityRepo->findOneBy(
                [
                    'integration'       => 'Sugarcrm',
                    'integrationEntity' => $object,
                    'internalEntity'    => 'lead',
                    'internalEntityId'  => $lead->getId(),
                ]
            );

            $mappedData[$object]['id'] = $integrationEntity->getIntegrationEntityId();
        }
        try {
            if ($this->isAuthorized()) {
                if (!is_null($lead->getOwner())) {
                    $sugarOwnerId = $this->getApiHelper()->getIdBySugarEmail(['emails' => [$lead->getOwner()->getEmail()]]);
                    if (!empty($sugarOwnerId)) {
                        $mappedData[$object]['assigned_user_id'] = array_values($sugarOwnerId)[0];
                    }
                }
                $createdLeadData = $this->getApiHelper()->createLead($mappedData[$object], $lead);
                if (isset($createdLeadData['id'])) {
                    if (empty($integrationId)) {
                        $integrationEntity = new IntegrationEntity();
                        $integrationEntity->setDateAdded(new \DateTime());
                        $integrationEntity->setLastSyncDate(new \DateTime());
                        $integrationEntity->setIntegration('Sugarcrm');
                        $integrationEntity->setIntegrationEntity($object);
                        $integrationEntity->setIntegrationEntityId($createdLeadData['id']);
                        $integrationEntity->setInternalEntity('lead');
                        $integrationEntity->setInternalEntityId($lead->getId());
                    } else {
                        $integrationEntity = $integrationEntityRepo->getEntity($integrationId[0]['id']);
                    }
                    $integrationEntity->setLastSyncDate(new \DateTime());
                    $this->em->persist($integrationEntity);
                    $this->em->flush($integrationEntity);
                }

                return true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return false;
    }

    /**
     * Return key recognized by integration.
     *
     * @param $key
     * @param $field
     *
     * @return mixed
     */
    public function convertLeadFieldKey($key, $field)
    {
        $search = [];
        foreach ($this->objects as $object) {
            $search[] = '__'.$object;
        }

        return str_replace($search, '', $key);
    }

    /**
     * @param array  $fields
     * @param array  $keys
     * @param string $object
     *
     * @return array
     */
    public function cleanSugarData($fields, $keys, $object)
    {
        $leadFields = [];

        foreach ($keys as $key) {
            if (strstr($key, '__'.$object)) {
                $newKey = str_replace('__'.$object, '', $key);
                //$leadFields[$object][$newKey] = $fields['leadFields'][$key];
                $leadFields[$newKey] = $fields['leadFields'][$key];
            }
        }

        return $leadFields;
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    public function pushLeads($params = [])
    {
        list($fromDate, $toDate) = $this->getSyncTimeframeDates($params);
        $limit                   = $params['limit'];
        $config                  = $this->mergeConfigToFeatureSettings();
        $integrationEntityRepo   = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $mauticData              = $leadsToUpdate              = $fields              = [];
        $fieldsToUpdateInSugar   = isset($config['update_mautic']) ? array_keys($config['update_mautic'], 1) : [];
        $leadFields              = $config['leadFields'];
        if (!empty($leadFields)) {
            if ($keys = array_keys($leadFields, 'mauticContactTimelineLink')) {
                foreach ($keys as $key) {
                    unset($leadFields[$key]);
                }
            }

            if ($keys = array_keys($leadFields, 'mauticContactIsContactableByEmail')) {
                foreach ($keys as $key) {
                    unset($leadFields[$key]);
                }
            }

            $fields = implode(', l.', $leadFields);
            $fields = 'l.owner_id,l.'.$fields;
            $result = 0;

            //Leads fields
            $leadSugarFieldsToCreate    = $this->cleanSugarData($config, array_keys($config['leadFields']), 'Leads');
            $fieldsToUpdateInLeadsSugar = $this->cleanSugarData($config, $fieldsToUpdateInSugar, 'Leads');
            $leadSugarFields            = array_diff_key($leadSugarFieldsToCreate, $fieldsToUpdateInLeadsSugar);

            //Contacts fields
            $contactSugarFields            = $this->cleanSugarData($config, array_keys($config['leadFields']), 'Contacts');
            $fieldsToUpdateInContactsSugar = $this->cleanSugarData($config, $fieldsToUpdateInSugar, 'Contacts');
            $contactSugarFields            = array_diff_key($contactSugarFields, $fieldsToUpdateInContactsSugar);

            $availableFields = $this->getAvailableLeadFields(['feature_settings' => ['objects' => ['Leads', 'Contacts']]]);

            //update lead/contact records
            $leadsToUpdate = $integrationEntityRepo->findLeadsToUpdate($this->getName(), 'lead', $fields, $limit, $fromDate, $toDate, ['Contacts', 'Leads']);
        }
        $checkEmailsInSugar = [];
        $deletedSugarLeads  = [];
        foreach ($leadsToUpdate as $object => $records) {
            foreach ($records as $lead) {
                if (isset($lead['email']) && !empty($lead['email'])) {
                    $lead                                                       = $this->getCompoundMauticFields($lead);
                    $checkEmailsInSugar[$object][mb_strtolower($lead['email'])] = $lead;
                }
            }
        }
        // Only get the max limit
        if ($limit) {
            $limit -= count($leadsToUpdate);
        }

        //create lead records
        if (null === $limit || $limit && !empty($fields)) {
            $leadsToCreate = $integrationEntityRepo->findLeadsToCreate('Sugarcrm', $fields, $limit, $fromDate, $toDate);
            foreach ($leadsToCreate as $lead) {
                if (isset($lead['email'])) {
                    $lead                                                       = $this->getCompoundMauticFields($lead);
                    $checkEmailsInSugar['Leads'][mb_strtolower($lead['email'])] = $lead;
                }
            }
        }

        foreach ($checkEmailsInSugar as $object => $checkObjectEmailsInSugar) {
            list($checkEmailsUpdatedInSugar, $deletedRedords) = $this->getObjectDataToUpdate($checkObjectEmailsInSugar, $mauticData, $availableFields, $contactSugarFields, $leadSugarFields, $object);
            //recheck synced records that might have been deleted in Sugar (deleted records don't come back in the query)
            foreach ($checkEmailsUpdatedInSugar as $key => $deletedSugarRedords) {
                if (isset($deletedSugarRedords['integration_entity_id']) && !empty($deletedSugarRedords['integration_entity_id'])) {
                    $deletedSugarLeads[$key] = $deletedSugarRedords['integration_entity_id'];
                }
                unset($checkEmailsUpdatedInSugar[$key]);
            }
        }

        if (!empty($checkEmailsUpdatedInSugar)) {
            $checkEmailsInSugar = array_merge($checkEmailsUpdatedInSugar, $checkEmailsInSugar);
        }
        // If there are any deleted, mark it as so to prevent them from being queried over and over or recreated
        if ($deletedSugarLeads) {
            $integrationEntityRepo->markAsDeleted($deletedSugarLeads, $this->getName(), 'lead');
        }

        // Create any left over
        if ($checkEmailsInSugar) {
            list($checkEmailsInSugar, $deletedSugarLeads) = $this->getObjectDataToUpdate($checkEmailsInSugar['Leads'], $mauticData, $availableFields, $contactSugarFields, $leadSugarFields, 'Leads');
            $ownerAssignedUserIdByEmail                   = null;
            foreach ($checkEmailsInSugar as $lead) {
                if (isset($lead['email'])) {
                    $lead['owner_email'] = $this->getOwnerEmail($lead);
                    if ($lead['owner_email']) {
                        $ownerAssignedUserIdByEmail = $this->getApiHelper()->getIdBySugarEmail(['emails' => [$lead['owner_email']]]);
                    }
                    $this->buildCompositeBody(
                        $mauticData,
                        $availableFields,
                        $leadSugarFieldsToCreate, //use all matched fields when creating new records in Sugar
                        'Leads',
                        $lead,
                        $ownerAssignedUserIdByEmail
                    );
                }
            }
        }
        /** @var SugarcrmApi $apiHelper */
        $apiHelper = $this->getApiHelper();
        if (!empty($mauticData)) {
            $result = $apiHelper->syncLeadsToSugar($mauticData);
        }

        return $this->processCompositeResponse($result);
    }

    /**
     * Update body to sync.
     *
     * @param array $lead
     * @param array $body
     */
    private function pushDnc(array $lead, array &$body)
    {
        $features = $this->settings->getFeatureSettings();
        // update DNC sync enabled
        if (!empty($features['updateDnc'])) {
            $leadEntity = $this->leadModel->getEntity($lead['id']);
            /** @var \Mautic\LeadBundle\Entity\DoNotContact[] $dncEntries */
            $dncEntries   = $this->doNotContactModel->getDncRepo()->getEntriesByLeadAndChannel($leadEntity, 'email');
            $sugarDncKeys = array_combine(array_values($this->sugarDncKeys), $this->sugarDncKeys);
            foreach ($dncEntries as $dncEntry) {
                if (empty($sugarDncKeys)) {
                    continue;
                }
                // If DNC exists set to 1
                switch ($dncEntry->getReason()) {
                    case 1:
                    case 3:
                        $body[] = ['name' => 'email_opt_out', 'value' => 1];
                        unset($sugarDncKeys['email_opt_out']);
                        break;
                    case 2:
                        $body[] = ['name' => 'invalid_email', 'value' => 1];
                        unset($sugarDncKeys['invalid_email']);
                        break;
                }
            }

            // uncheck
            // If DNC doesn't exist set to 1
            if (!empty($sugarDncKeys)) {
                foreach ($sugarDncKeys as $sugarDncKey) {
                    $body[] = ['name' => $sugarDncKey, 'value' => 0];
                }
            }
        }
    }

    private function getDnc()
    {
        $features = $this->settings->getFeatureSettings();
        if (!empty($features['updateDnc'])) {
        }
    }

    /**
     * @param $checkEmailsInSugar
     * @param $mauticData
     * @param $availableFields
     * @param $contactSugarFields
     * @param $leadSugarFields
     * @param string $object
     *
     * @return mixed
     */
    public function getObjectDataToUpdate($checkEmailsInSugar, &$mauticData, $availableFields, $contactSugarFields, $leadSugarFields, $object = 'Leads')
    {
        $config     = $this->mergeConfigToFeatureSettings([]);
        $queryParam = ($object == 'Leads') ? 'checkemail' : 'checkemail_contacts';

        $sugarLead         = $this->getApiHelper()->getLeads([$queryParam => array_keys($checkEmailsInSugar), 'offset' => 0, 'max_results' => 1000], $object);
        $deletedSugarLeads = $sugarLeadRecords = [];

        if (isset($sugarLead['entry_list'])) {
            //Sugar 6.X
            $sugarLeadRecords = [];
            foreach ($sugarLead['entry_list'] as $k => $record) {
                $sugarLeadRecord                = [];
                $sugarLeadRecord['id']          = $record['id'];
                $sugarLeadRecord['module_name'] = $record['module_name'];
                foreach ($record['name_value_list'] as $item) {
                    $sugarLeadRecord[$item['name']] = $item['value'];
                }
                if (!isset($sugarLeadRecord['email1'])) {
                    foreach ($sugarLead['relationship_list'][$k]['link_list'] as $links) {
                        if ($links['name'] == 'email_addresses') {
                            foreach ($links['records'] as $records) {
                                foreach ($records as $contactEmails) {
                                    foreach ($contactEmails as $anyAddress) {
                                        if ($anyAddress['name'] == 'email_address' && !empty($anyAddress['value'])) {
                                            $sugarLeadRecord['email1'] = $anyAddress['value'];
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $sugarLeadRecords[] = $sugarLeadRecord;
            }
        } elseif (isset($sugarLead['records'])) { //Sugar 7
            $sugarLeadRecords = $sugarLead['records'];
        }

        foreach ($sugarLeadRecords as $sugarLeadRecord) {
            if ((isset($sugarLeadRecord) && $sugarLeadRecord)) {
                $email           = $sugarLeadRecord['email1'];
                $key             = mb_strtolower($email);
                $leadOwnerEmails = [];
                foreach ($checkEmailsInSugar as $emailKey => $mauticRecord) {
                    if ($email == $emailKey) {
                        $isConverted = (isset($sugarLeadRecord['contact_id'])
                            && $sugarLeadRecord['contact_id'] != null
                            && $sugarLeadRecord['contact_id'] != '');

                        $sugarIdMapping[$checkEmailsInSugar[$key]['internal_entity_id']] = ($isConverted) ? $sugarLeadRecord['contact_id'] : $sugarLeadRecord['id'];
                        $lead['owner_email']                                             = $this->getOwnerEmail($mauticRecord);
                        if ($lead['owner_email']) {
                            $leadOwnerEmails[] = $lead['owner_email'];
                        }
                        $ownerAssignedUserIdByEmail = $this->getApiHelper()->getIdBySugarEmail(['emails' => array_unique($leadOwnerEmails)]);
                        if (empty($sugarLeadRecord['deleted']) || $sugarLeadRecord['deleted'] == 0) {
                            $sugarFieldMappings = $this->prepareFieldsForPush($availableFields);

                            if (isset($sugarFieldMappings['Contacts']) && !empty($sugarFieldMappings['Contacts'])) {
                                $contactSugarFields = $this->getBlankFieldsToUpdate($contactSugarFields, $sugarLeadRecord, $sugarFieldMappings['Contacts'], $config);
                            }
                            if (isset($sugarFieldMappings['Leads']) && !empty($sugarFieldMappings['Leads'])) {
                                $leadSugarFields = $this->getBlankFieldsToUpdate($leadSugarFields, $sugarLeadRecord, $sugarFieldMappings['Leads'], $config);
                            }
                            $this->buildCompositeBody(
                                $mauticData,
                                $availableFields,
                                $isConverted || ($object == 'Contacts') ? $contactSugarFields : $leadSugarFields,
                                $isConverted || ($object == 'Contacts') ? 'Contacts' : 'Leads',
                                $checkEmailsInSugar[$key],
                                $ownerAssignedUserIdByEmail,
                                $isConverted ? $sugarLeadRecord['contact_id'] : $sugarLeadRecord['id']
                            );
                        } else {
                            // @todo - Should return also deleted contacts from Sugar
                            $deletedSugarLeads[] = $sugarLeadRecord['id'];
                            if (!empty($sugarLeadRecord['contact_id']) || $sugarLeadRecord['contact_id'] != '') {
                                $deletedSugarLeads[] = $sugarLeadRecord['contact_id'];
                            }
                        }
                        unset($checkEmailsInSugar[$key]);
                    }
                }
            }
        }

        return [$checkEmailsInSugar, $deletedSugarLeads];
    }

    /**
     * @param $lead
     *
     * @return array
     */
    public function getSugarLeadId($lead)
    {
        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        //try searching for lead as this has been changed before in updated done to the plugin
        $result = $integrationEntityRepo->getIntegrationsEntityId('Sugarcrm', null, 'lead', $lead->getId());

        return $result;
    }

    /**
     * @param array $fields
     *
     * @return array
     *
     * @deprecated 2.6.0 to be removed in 3.0
     */
    public function amendToSfFields($fields)
    {
    }

    /**
     * @param array $lead
     */
    protected function getOwnerEmail($lead)
    {
        if (isset($lead['owner_id']) && !empty($lead['owner_id'])) {
            /** @var \Mautic\UserBundle\Model\UserModel $model */
            $model = $this->factory->getModel('user.user');
            /** @var \Mautic\UserBundle\Entity\User $user */
            $user = $model->getEntity($lead['owner_id']);

            return $user->getEmail();
        }

        return null;
    }

    /**
     * @param      $mauticData
     * @param      $availableFields
     * @param      $fieldsToUpdateInSfUpdate
     * @param      $object
     * @param      $lead
     * @param null $objectId
     */
    protected function buildCompositeBody(&$mauticData, $availableFields, $fieldsToUpdateInSugarUpdate, $object, $lead, $onwerAssignedUserIdByEmail = null, $objectId = null)
    {
        $body = [];
        if (isset($lead['email']) && !empty($lead['email'])) {
            //update and create (one query) every 200 records

            foreach ($fieldsToUpdateInSugarUpdate as $sugarField => $mauticField) {
                $required = !empty($availableFields[$object][$sugarField.'__'.$object]['required']);
                if (isset($lead[$mauticField])) {
                    if (strpos($lead[$mauticField], '|') !== false) {
                        // Transform Mautic Multi Select into SugarCRM/SuiteCRM Multi Select format
                        $value = $this->convertMauticToSuiteCrmMultiSelect($lead[$mauticField]);
                    } else {
                        $value = $lead[$mauticField];
                    }
                    $body[] = ['name' => $sugarField, 'value' =>  $value];
                } elseif ($required) {
                    $value  = $this->factory->getTranslator()->trans('mautic.integration.form.lead.unknown');
                    $body[] = ['name' => $sugarField, 'value' => $value];
                }
            }

            if (!empty($body)) {
                $id = $lead['internal_entity_id'].'-'.$object.(!empty($lead['id']) ? '-'.$lead['id'] : '');

                $body[] = ['name' => 'reference_id', 'value' => $id];

                if ($objectId) {
                    $body[] = ['name' => 'id', 'value' => $objectId];
                }
                if (isset($onwerAssignedUserIdByEmail) && isset($lead['owner_email']) && isset($onwerAssignedUserIdByEmail[$lead['owner_email']])) {
                    $body[] = ['name' => 'assigned_user_id', 'value' => $onwerAssignedUserIdByEmail[$lead['owner_email']]];
                }

                // pushd DNC to Sugar CRM
                $this->pushDnc($lead, $body);

                $mauticData[$object][] = $body;
            }
        }
    }

    /**
     * @param array $response
     *
     * @return array
     */
    protected function processCompositeResponse($response)
    {
        $created         = 0;
        $errored         = 0;
        $updated         = 0;
        $object          = 'Lead';
        $persistEntities = [];
        if (is_array($response)) {
            foreach ($response as $item) {
                $contactId = $integrationEntityId = null;
                if (!empty($item['reference_id'])) {
                    $reference = explode('-', $item['reference_id']);
                    if (3 === count($reference)) {
                        list($contactId, $object, $integrationEntityId) = $reference;
                    } else {
                        list($contactId, $object) = $reference;
                    }
                }

                if (isset($item['ko']) && $item['ko']) {
                    $this->logIntegrationError(new \Exception($item['error']));

                    if ($integrationEntityId) {
                        $integrationEntity = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $integrationEntityId);
                        $integrationEntity->setLastSyncDate(new \DateTime());

                        $persistEntities[] = $integrationEntity;
                    } elseif ($contactId) {
                        $integrationEntity = new IntegrationEntity();
                        $integrationEntity->setDateAdded(new \DateTime());
                        $integrationEntity->setLastSyncDate(new \DateTime());
                        $integrationEntity->setIntegration($this->getName());
                        $integrationEntity->setIntegrationEntity($object);
                        $integrationEntity->setInternalEntity('lead-error');
                        $integrationEntity->setInternal(['error' => $item['error']]);
                        $integrationEntity->setInternalEntityId($contactId);

                        $persistEntities[] = $integrationEntity;
                        ++$errored;
                    }
                } elseif (!$item['ko']) {
                    if ($item['new']) {
                        // New object created
                        $integrationEntity = new IntegrationEntity();
                        $integrationEntity->setDateAdded(new \DateTime());
                        $integrationEntity->setLastSyncDate(new \DateTime());
                        $integrationEntity->setIntegration($this->getName());
                        $integrationEntity->setIntegrationEntity($object);
                        $integrationEntity->setIntegrationEntityId($item['id']);
                        $integrationEntity->setInternalEntity('lead');
                        $integrationEntity->setInternalEntityId($contactId);

                        $persistEntities[] = $integrationEntity;
                        ++$created;
                    } else {
                        // Record was updated
                        if ($integrationEntityId) {
                            $integrationEntity = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $integrationEntityId);
                            $integrationEntity->setLastSyncDate(new \DateTime());
                        } else {
                            // Found in Sugarcrm so create a new record for it
                            $integrationEntity = new IntegrationEntity();
                            $integrationEntity->setDateAdded(new \DateTime());
                            $integrationEntity->setLastSyncDate(new \DateTime());
                            $integrationEntity->setIntegration($this->getName());
                            $integrationEntity->setIntegrationEntity($object);
                            $integrationEntity->setIntegrationEntityId($item['id']);
                            $integrationEntity->setInternalEntity('lead');
                            $integrationEntity->setInternalEntityId($contactId);
                        }

                        $persistEntities[] = $integrationEntity;
                        ++$updated;
                    }
                } else {
                    $error = 'Uknown status code '.$item['httpStatusCode'];
                    $this->logIntegrationError(new \Exception($error.' ('.$item['reference_id'].')'));
                }
            }

            if ($persistEntities) {
                $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($persistEntities);
                unset($persistEntities);
                $this->em->clear(IntegrationEntity::class);
            }
        }

        return [$updated, $created, $errored];
    }

    /**
     * @param       $fieldsToUpdate
     * @param array $objects
     *
     * @return array
     */
    protected function cleanPriorityFields($fieldsToUpdate, $objects = null)
    {
        if (null === $objects) {
            $objects = ['Leads', 'Contacts'];
        }

        if (isset($fieldsToUpdate['leadFields'])) {
            // Pass in the whole config
            $fields = $fieldsToUpdate['leadFields'];
        } else {
            $fields = array_flip($fieldsToUpdate);
        }

        $fieldsToUpdate = $this->prepareFieldsForSync($fields, $fieldsToUpdate, $objects);

        return $fieldsToUpdate;
    }

    /**
     * @param        $config
     * @param null   $object
     * @param string $priorityObject
     *
     * @return mixed
     */
    protected function getPriorityFieldsForMautic($config, $object = null, $priorityObject = 'mautic')
    {
        if ($object == 'company') {
            $priority = parent::getPriorityFieldsForMautic($config, $object, 'mautic_company');
            $fields   = array_intersect_key($config['companyFields'], $priority);
        } else {
            $fields = parent::getPriorityFieldsForMautic($config, $object, $priorityObject);
        }

        return ($object && isset($fields[$object])) ? $fields[$object] : $fields;
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

        $contactFields = $this->cleanSugarData($config, array_keys($config['leadFields']), 'Contacts');
        $leadFields    = $this->cleanSugarData($config, array_keys($config['leadFields']), 'Leads');
        if (!empty($contactFields)) {
            foreach ($fields['Contacts'] as $key => $field) {
                if ($field['required']) {
                    $required[$key] = $field;
                }
            }
            $fieldMappings['Contacts']['required'] = [
                'fields' => $required,
            ];
            $fieldMappings['Contacts']['create'] = $contactFields;
        }
        if (!empty($leadFields)) {
            foreach ($fields['Leads'] as $key => $field) {
                if ($field['required']) {
                    $required[$key] = $field;
                }
            }
            $fieldMappings['Leads']['required'] = [
                'fields' => $required,
            ];
            $fieldMappings['Leads']['create'] = $leadFields;
        }

        return $fieldMappings;
    }

    /**
     * Converts Mautic Multi-Select String into the format used to store Multi-Select values used by SuiteCRM / SugarCRM 6.x.
     *
     * @param  string
     *
     * @return string
     */
    public function convertMauticToSuiteCrmMultiSelect($mauticMultiSelectStringToConvert)
    {
        //$mauticMultiSelectStringToConvert = 'test|enhancedapi|dataservices';
        $multiSelectArrayValues             = explode('|', $mauticMultiSelectStringToConvert);
        $convertedSugarCrmMultiSelectString = '';
        foreach ($multiSelectArrayValues as $item) {
            $convertedSugarCrmMultiSelectString = $convertedSugarCrmMultiSelectString.'^'.$item.'^'.',';
        }
        $convertedSugarCrmMultiSelectString = substr($convertedSugarCrmMultiSelectString, 0, -1);

        return $convertedSugarCrmMultiSelectString;
    }

    /**
     * Checks if a string contains SuiteCRM / SugarCRM 6.x Multi-Select values.
     *
     * @param  string
     *
     * @return bool
     */
    public function checkIfSugarCrmMultiSelectString($stringToCheck)
    {
        // Regular Express to check SugarCRM/SuiteCRM Multi-Select format below
        // example format: '^choice1^,^choice2^,^choice_3^'
        $regex = '/(\^)(?:([A-Za-z0-9\-\_]+))(\^)/';
        if (preg_match($regex, $stringToCheck)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Converts a SuiteCRM / SugarCRM 6.x Multi-Select String into the format used to store Multi-Select values used by Mautic.
     *
     * @param  string
     *
     * @return string
     */
    public function convertSuiteCrmToMauticMultiSelect($suiteCrmMultiSelectStringToConvert)
    {
        // Mautic Multi Select format - 'choice1|choice2|choice_3'
        $regexString            = '/(\^)(?:([A-Za-z0-9\-\_]+))(\^)/';
        preg_match_all($regexString, $suiteCrmMultiSelectStringToConvert, $matches, PREG_SET_ORDER, 0);
        $convertedString        = '';
        foreach ($matches as $row => $innerArray) {
            $convertedString     = $convertedString.$innerArray[2].'|';
        }
        $convertedString        = substr($convertedString, 0, -1);

        return $convertedString;
    }
}
