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

use Mautic\EmailBundle\Model\EmailModel;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\UserBundle\Entity\User;
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
    private $authorzationError;

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
        if (isset($this->keys['version']) && $this->keys['version'] == '6') {
            return parent::getSupportedFeatures();
        }
        //Only push_lead is currently supported for version 7
        return ['push_lead'];
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
        return $this->factory->getRouter()->generate('mautic_integration_auth_callback', ['integration' => $this->getName()]);
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
     * @param array $settings
     *
     * @return array|mixed
     *
     * @throws \Exception
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
     * @return array
     *
     * @throws \Exception
     */
    public function getAvailableLeadFields($settings = [])
    {
        $sugarFields       = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        $sugarObjects      = [];

        if (isset($settings['feature_settings']['objects'])) {
            $sugarObjects = $settings['feature_settings']['objects'];
        } else {
            $sugarObjects[] = 'Leads';
        }

        $isRequired = function (array $field, $object) {
            return !empty($field['required']);
        };

        try {
            if (!empty($sugarObjects) and is_array($sugarObjects)) {
                foreach ($sugarObjects as $key => $sObject) {
                    if ('Accounts' === $sObject) {
                        // Match Sugar object to Mautic's
                        $sObject = 'company';
                    }
                    if ($this->isAuthorized()) {
                        $sObject = trim($sObject);
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$sObject;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $sugarFields[$sObject] = $fields;
                            continue;
                        }
                        if (!isset($sugarFields[$sObject])) {
                            $fields = $this->getApiHelper()->getLeadFields($sObject);

                            if ($fields != null && !empty($fields)) {
                                if (isset($fields['module_fields']) && !empty($fields['module_fields'])) {
                                    //6.x/community
                                    foreach ($fields['module_fields'] as $fieldInfo) {
                                        if (isset($fieldInfo['name']) && !in_array($fieldInfo['type'], ['id', 'assigned_user_name', 'bool', 'link', 'relate'])) {
                                            $type      = 'string';
                                            $fieldName = (strpos($fieldInfo['name'], 'webtolead_email') === false) ? $fieldInfo['name'] : str_replace('webtolead_', '', $fieldInfo['name']);

                                            if ($sObject !== 'company') {
                                                $sugarFields[$sObject][$fieldName.'__'.$sObject] = [
                                                    'type'        => $type,
                                                    'label'       => $sObject.'-'.$fieldInfo['label'],
                                                    'required'    => $isRequired($fieldInfo, $sObject),
                                                    'group'       => $sObject,
                                                    'optionLabel' => $fieldInfo['label'],
                                                ];
                                            } else {
                                                $sugarFields[$sObject][$fieldName] = [
                                                    'type'     => $type,
                                                    'label'    => $fieldInfo['label'],
                                                    'required' => $isRequired($fieldInfo, $sObject),
                                                ];
                                            }
                                        }
                                    }
                                    $this->cache->set('leadFields'.$cacheSuffix, $sugarFields[$sObject]);
                                } elseif (isset($leadObject['fields'])) {

                                    //7.x
                                    foreach ($leadObject['fields'] as $fieldInfo) {
                                        if (isset($fieldInfo['name']) && empty($fieldInfo['readonly']) && !empty($fieldInfo['comment']) && !in_array($fieldInfo['type'], ['id', 'team_list', 'bool', 'link', 'relate'])) {
                                            $fieldName = (strpos($fieldInfo['name'], 'webtolead_email') === false) ? $fieldInfo['name'] : str_replace('webtolead_', '', $fieldInfo['name']);

                                            $sugarFields[$fieldName] = [
                                                'type'     => 'string',
                                                'label'    => $fieldInfo['comment'],
                                                'required' => !empty($fieldInfo['required']),
                                            ];
                                        }
                                    }
                                    $this->cache->set('leadFields', $sugarFields);
                                }
                            }
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
    public function getCompanies($params = [], $query = null)
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
                $count            = $result['total_count'];
                $executed += $this->amendLeadDataBeforeMauticPopulate($result, $sugarObject);
                if ($count > $params['offset']) {
                    $executed += $this->getCompanies($params);
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
        if (isset($config['objects'])) {
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
     * @param \DateTime|null $startDate
     * @param \DateTime|null $endDate
     * @param                $leadId
     *
     * @return array
     */
    public function getLeadData(\DateTime $startDate = null, \DateTime $endDate = null, $leadId)
    {
        $leadIds = (!is_array($leadId)) ? [$leadId] : $leadId;

        $leadActivity = [];
        $options      = ['leadIds' => $leadIds, 'basic_select' => true, 'fromDate' => $startDate, 'toDate' => $endDate];

        /** @var LeadModel $leadModel */
        $leadModel      = $this->factory->getModel('lead');
        $pointsRepo     = $leadModel->getPointLogRepository();
        $results        = $pointsRepo->getLeadTimelineEvents(null, $options);
        $pointChangeLog = [];
        foreach ($results as $result) {
            if (!isset($pointChangeLog[$result['lead_id']])) {
                $pointChangeLog[$result['lead_id']] = [];
            }
            $pointChangeLog[$result['lead_id']][] = $result;
        }
        unset($results);

        /** @var EmailModel $emailModel */
        $emailModel = $this->factory->getModel('email');
        $emailRepo  = $emailModel->getStatRepository();
        $results    = $emailRepo->getLeadStats(null, $options);
        $emailStats = [];
        foreach ($results as $result) {
            if (!isset($emailStats[$result['lead_id']])) {
                $emailStats[$result['lead_id']] = [];
            }
            $emailStats[$result['lead_id']][] = $result;
        }
        unset($results);

        /** @var SubmissionModel $formSubmissionModel */
        $formSubmissionModel = $this->factory->getModel('form.submission');
        $submissionRepo      = $formSubmissionModel->getRepository();
        $results             = $submissionRepo->getSubmissions($options);
        $formSubmissions     = [];
        foreach ($results as $result) {
            if (!isset($formSubmissions[$result['lead_id']])) {
                $formSubmissions[$result['lead_id']] = [];
            }
            $formSubmissions[$result['lead_id']][] = $result;
        }
        unset($results);

        $translator = $this->getTranslator();
        foreach ($leadIds as $leadId) {
            $i        = 0;
            $activity = [];

            if (isset($pointChangeLog[$leadId])) {
                foreach ($pointChangeLog[$leadId] as $row) {
                    $typeString = "mautic.{$row['type']}.{$row['type']}";
                    $typeString = ($translator->hasId($typeString)) ? $translator->trans($typeString) : ucfirst($row['type']);
                    if ((int) $row['delta'] > 0) {
                        $subject = 'added';
                    } else {
                        $subject = 'subtracted';
                        $row['delta'] *= -1;
                    }
                    $pointsString = $translator->transChoice(
                        "mautic.sugarcrm.activity.points_{$subject}",
                        $row['delta'],
                        ['%points%' => $row['delta']]
                    );
                    $activity[$i]['eventType']   = 'point';
                    $activity[$i]['name']        = $translator->trans('mautic.sugarcrm.activity.point')." ($pointsString)";
                    $activity[$i]['description'] = "$typeString: {$row['eventName']} / {$row['actionName']}";
                    $activity[$i]['dateAdded']   = $row['dateAdded'];
                    $activity[$i]['id']          = 'pointChange'.$row['id'];
                    ++$i;
                }
            }

            if (isset($emailStats[$leadId])) {
                foreach ($emailStats[$leadId] as $row) {
                    switch (true) {
                        case !empty($row['storedSubject']):
                            $name = $row['storedSubject'];
                            break;
                        case !empty($row['subject']):
                            $name = $row['subject'];
                            break;
                        case !empty($row['email_name']):
                            $name = $row['email_name'];
                            break;
                        default:
                            $name = $translator->trans('mautic.email.timeline.event.custom_email');
                    }

                    $activity[$i]['eventType']   = 'email';
                    $activity[$i]['name']        = $translator->trans('mautic.sugarcrm.activity.email').": $name";
                    $activity[$i]['description'] = $translator->trans('mautic.email.sent').": $name";
                    $activity[$i]['dateAdded']   = $row['dateSent'];
                    $activity[$i]['id']          = 'emailStat'.$row['id'];
                    ++$i;
                }
            }

            if (isset($formSubmissions[$leadId])) {
                foreach ($formSubmissions[$leadId] as $row) {
                    $activity[$i]['eventType']   = 'form';
                    $activity[$i]['name']        = $this->getTranslator()->trans('mautic.sugarcrm.activity.form').': '.$row['name'];
                    $activity[$i]['description'] = $translator->trans('mautic.form.event.submitted').': '.$row['name'];
                    $activity[$i]['dateAdded']   = $row['dateSubmitted'];
                    $activity[$i]['id']          = 'formSubmission'.$row['id'];
                    ++$i;
                }
            }

            $leadActivity[$leadId] = [
                'records' => $activity,
            ];

            unset($activity);
        }

        unset($pointChangeLog, $emailStats, $formSubmissions);

        return $leadActivity;
    }

    /**
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
     */
    public function getLeads($params = [], $query = null)
    {
        $executed              = null;
        $params['max_results'] = 100;

        $config = $this->mergeConfigToFeatureSettings([]);

        $sugarObjects[] = 'Leads';

        if (isset($params['object'])) {
            //Not the first call
            $sugarObjects = [$params['object']];
        } else {
            if (isset($config['objects'])) {
                $sugarObjects = $config['objects'];
            }
            $params['offset'] = 0;
        }

        $query = $params;

        try {
            if ($this->isAuthorized()) {
                foreach ($sugarObjects as $object) {
                    if ($object !== 'Activity' and $object !== 'company') {
                        $result           = $this->getApiHelper()->getLeads($query, $object);
                        $params['offset'] = $result['next_offset'];
                        $count            = $result['total_count'];
                        $executed += $this->amendLeadDataBeforeMauticPopulate($result, $object);
                        if ($count > $params['offset']) {
                            $params['object'] = $object;
                            $executed += $this->getLeads($params);
                        }
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
                return $this->factory->getTranslator()->trans('mautic.integration.error.genericerror', [], 'flashes');
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
        if ($authType == 'oauth2' && empty($settings['authorize_session'])) {

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

            $settings['auth_type'] = 'rest';
            $response              = $this->makeRequest($this->getAccessTokenUrl(), $parameters, 'GET', $settings);

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

        if (isset($data['entry_list']) and $object !== 'Activity') {

        //Get assigned user ids
            $assignedUserIds            = [];
            $onwerEmailByAssignedUserId = [];
            if ($object == 'Leads' || $object == 'Contacts' || $object == 'Accounts') {
                foreach ($data['entry_list'] as $key => $record) {
                    foreach ($record['name_value_list'] as $item) {
                        if ($item['name'] == 'assigned_user_id' && $item['value'] && $item['value'] != '') {
                            $assignedUserIds[] = $item['value'];
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
                foreach ($data['entry_list'] as $key => $record) {
                    foreach ($record['name_value_list'] as $item) {
                        if ($item['name'] == 'email1' && $item['value'] && $item['value'] != '') {
                            $checkEmailsInSugar[] = $item['value'];
                        }
                    }
                }
            }
            if (!empty($checkEmailsInSugar)) {
                $sugarLeads = $this->getApiHelper()->getLeads(['checkemail_contacts' => $checkEmailsInSugar, 'offset' => 0, 'max_results' => 1000], 'Contacts');
                if (isset($sugarLeads['entry_list'])) {
                    foreach ($sugarLeads['entry_list'] as $k => $record) {
                        $sugarLeadRecord = [];
                        foreach ($record['name_value_list'] as $item) {
                            if ($item['name'] == 'email1' && $item['value'] && $item['value'] != '') {
                                $sugarRejectedLeads[] = $item['value'];
                            }
                        }
                    }
                }
            }

            foreach ($data['entry_list'] as $key => $record) {
                $integrationEntities = [];
                $dataObject          = [];
                if (isset($record['module_name']) && $record['module_name'] == 'Accounts') {
                    $newName = '';
                } else {
                    $newName = '__'.$object;
                }
                foreach ($record['name_value_list'] as $item) {
                    if ($object !== 'Activity') {
                        $dataObject[$item['name'].$newName] = $item['value'];

                        if ($item['name'] == 'date_entered') {
                            $itemDateEntered = new \DateTime($item['value']);
                        }
                        if ($item['name'] == 'date_modified') {
                            $itemDateModified = new \DateTime($item['value']);
                        }
                    }
                }
                if ($object == 'Leads' && isset($dataObject['email1__Leads']) && $dataObject['email1__Leads'] != null
                && $dataObject['email1__Leads'] != '' && in_array($dataObject['email1__Leads'], $sugarRejectedLeads)) {
                    continue; //Lead email is already in Sugar Contacts. Do not carry on
                }
                $itemLastDate                                       = $itemDateModified;
                if ($itemDateEntered > $itemLastDate) {
                    $itemLastDate = $itemDateEntered;
                }

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
                        $entity                = $this->getMauticLead($dataObject, true, null, null);

                        $company = null;
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
                            }
                        }
                    } elseif ($object == 'Accounts') {
                        $entity                = $this->getMauticCompany($dataObject, true, null);
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
                    } else {
                        continue;
                    }
                    ++$count;
                }

                $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($integrationEntities);
                $this->em->clear('Mautic\PluginBundle\Entity\IntegrationEntity');

                unset($data);
            }
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
            if (isset($this->keys['version']) && $this->keys['version'] == '6') {
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
            }
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
     * This is a test function.
     *
     * @param $lead
     */
    public function getModules($params = [])
    {
        $executed = null;

        try {
            if ($this->isAuthorized()) {
                return $this->getApiHelper()->getModules();

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
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
            $integrationId                      = $integrationEntityRepo->getIntegrationsEntityId('Sugarcrm', 'Contacts', 'lead', $lead->getId());
            if (!empty($integrationId)) {
                $object = 'Contacts';
            }
        }
        if (!empty($integrationId)) {
            $integrationEntity                                = $integrationEntityRepo->getEntity($integrationId[0]['id']);
            $lastSyncDate                                     = $integrationEntity->getLastSyncDate();
            $addedSyncDate                                    = $integrationEntity->getDateAdded();
            if ($addedSyncDate > $lastSyncDate) {
                $lastSyncDate = $addedSyncDate;
            }

            $leadDateModified                                     = $lead->getDateModified();
            $leadDateAdded                                        = $lead->getDateAdded();
            $leadLastDate                                         = $leadDateModified;
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
                $createdLeadData = $this->getApiHelper()->createLead($mappedData[$object], $lead);
                if (isset($createdLeadData['id'])) {
                    if (empty($integrationId)) {
                        $integrationEntity = new IntegrationEntity();
                        $integrationEntity->setDateAdded(new \DateTime());
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
/*
 * @param array $params
 * @return mixed
*/
    public function pushLeads($params = [])
    {
        $limit                 = $params['limit'];
        $config                = $this->mergeConfigToFeatureSettings();
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $mauticData            = [];
        $fieldsToUpdateInSugar = isset($config['update_mautic']) ? array_keys($config['update_mautic'], 1) : [];
        $fields                = implode(', l.', $config['leadFields']);
        $fields                = 'l.owner_id,l.'.$fields;
        $result                = 0;
        $checkEmailsInSugar    = [];

        $leadSugarFields    = $this->cleanSugarData($config, array_keys($config['leadFields']), 'Leads');
        $leadSugarFields    = array_diff_key($leadSugarFields, array_flip($fieldsToUpdateInSugar));
        $contactSugarFields = $this->cleanSugarData($config, array_keys($config['leadFields']), 'Contacts');

        $contactSugarFields = array_diff_key($contactSugarFields, array_flip($fieldsToUpdateInSugar));
        $availableFields    = $this->getAvailableLeadFields(['feature_settings' => ['objects' => ['Leads', 'Contacts']]]);

        //update lead/contact records
        $leadsToUpdate   = $integrationEntityRepo->findLeadsToUpdate('Sugarcrm', 'lead', $fields, $limit);
        $leadOwnerEmails = [];
        foreach ($leadsToUpdate as $lead) {
            if (isset($lead['email']) && !empty($lead['email'])) {
                $lead['owner_email']                               = $this->getOwnerEmail($lead);
                if ($lead['owner_email']) {
                    $leadOwnerEmails[]        = $lead['owner_email'];
                }
                $checkEmailsInSugar[mb_strtolower($lead['email'])] = $lead;
            }
        }

        // Only get the max limit
        if ($limit) {
            $limit -= count($leadsToUpdate);
        }

        //create lead records
        if (null === $limit || $limit) {
            $leadsToCreate = $integrationEntityRepo->findLeadsToCreate('Sugarcrm', $fields, $limit);
            foreach ($leadsToCreate as $lead) {
                $lead['owner_email']                               = $this->getOwnerEmail($lead);
                if ($lead['owner_email']) {
                    $leadOwnerEmails[]        = $lead['owner_email'];
                }
                $checkEmailsInSugar[mb_strtolower($lead['email'])] = $lead;
            }
        }
        $onwerAssignedUserIdByEmail = $this->getApiHelper()->getIdBySugarEmail(['emails' => array_unique($leadOwnerEmails)]);
        $deletedSugarLeads          = [];
        $sugarIdMapping             = [];
        if ($checkEmailsInSugar) {
            $sugarLead = $this->getApiHelper()->getLeads(['checkemail' => array_keys($checkEmailsInSugar), 'offset' => 0, 'max_results' => 1000], 'Leads');

            if (isset($sugarLead['entry_list'])) {
                foreach ($sugarLead['entry_list'] as $k => $record) {
                    $sugarLeadRecord = [];
                    foreach ($record['name_value_list'] as $item) {
                        $sugarLeadRecord[$item['name']] = $item['value'];
                    }

                    if (isset($sugarLeadRecord) && $sugarLeadRecord) {
                        $email = $sugarLeadRecord['email1'];
                        $key   = mb_strtolower($email);

                        if (isset($checkEmailsInSugar[$key])) {
                            $isConverted = (isset($sugarLeadRecord['contact_id'])
                                && $sugarLeadRecord['contact_id'] != null && $sugarLeadRecord['contact_id'] != '');
                            $sugarIdMapping[$checkEmailsInSugar[$key]['internal_entity_id']] = ($isConverted) ? $sugarLeadRecord['contact_id']
                                : $sugarLeadRecord['id'];

                            if (empty($sugarLeadRecord['deleted']) || $sugarLeadRecord['deleted'] == 0) {
                                $this->buildCompositeBody(
                                    $mauticData,
                                    $availableFields,
                                    $isConverted ? $contactSugarFields : $leadSugarFields,
                                    $isConverted ? 'Contacts' : 'Leads',
                                    $checkEmailsInSugar[$key],
                                    $onwerAssignedUserIdByEmail,
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
                        } // Otherwise a duplicate in Sugar and has already been processed
                    }
                }
            }
        }

        // If there are any deleted, mark it as so to prevent them from being queried over and over or recreated
        if ($deletedSugarLeads) {
            $integrationEntityRepo->markAsDeleted($deletedSugarLeads, $this->getName(), 'lead');
        }

        // Create any left over
        if ($checkEmailsInSugar) {
            foreach ($checkEmailsInSugar as $lead) {
                $this->buildCompositeBody(
                    $mauticData,
                    $availableFields,
                    $leadSugarFields,
                    'Leads',
                    $lead,
                    $onwerAssignedUserIdByEmail
                );
            }
        }

        $request['allOrNone']        = 'false';
        $request['compositeRequest'] = array_values($mauticData);

        /** @var SugarcrmApi $apiHelper */
        $apiHelper = $this->getApiHelper();
        if (!empty($mauticData)) {
            $result = $apiHelper->syncLeadsToSugar($mauticData);
        }

        return $this->processCompositeResponse($result, $sugarIdMapping);
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
        $result                = $integrationEntityRepo->getIntegrationsEntityId('Sugarcrm', null, 'Lead', $lead->getId());
        if (empty($result)) {
            //try searching for lead as this has been changed before in updated done to the plugin
            $result = $integrationEntityRepo->getIntegrationsEntityId('Sugarcrm', null, 'lead', $lead->getId());
        }

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
                    $body[] = ['name' => $sugarField, 'value' => $lead[$mauticField]];
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
                $mauticData[$object][] = $body;
            }
        }
    }

    /**
     * @param       $response
     * @param array $sugarcrmIdMapping
     *
     * @return array
     */
    protected function processCompositeResponse($response, array $sugarcrmIdMapping = [])
    {
        $created = 0;
        $updated = 0;

        if (is_array($response)) {
            $persistEntities = [];
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
                        $integrationEntity->setIntegrationEntityId($sugarcrmIdMapping[$contactId]);
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

        return [$updated, $created];
    }
}
