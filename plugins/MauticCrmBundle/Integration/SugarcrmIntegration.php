<?php

namespace MauticPlugin\MauticCrmBundle\Integration;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\Type\ButtonGroupType;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Mautic\UserBundle\Model\UserModel;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class SugarcrmIntegration extends CrmAbstractIntegration
{
    /**
     * @var string[]
     */
    private array $objects = [
        'Leads',
        'Contacts',
        'Accounts',
    ];

    /**
     * @var string[]
     */
    private array $sugarDncKeys = ['email_opt_out', 'invalid_email'];

    private $authorizationError;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        CacheStorageHelper $cacheStorageHelper,
        EntityManager $entityManager,
        Session $session,
        RequestStack $requestStack,
        Router $router,
        TranslatorInterface $translator,
        Logger $logger,
        EncryptionHelper $encryptionHelper,
        LeadModel $leadModel,
        CompanyModel $companyModel,
        PathsHelper $pathsHelper,
        NotificationModel $notificationModel,
        FieldModel $fieldModel,
        IntegrationEntityModel $integrationEntityModel,
        protected DoNotContact $doNotContactModel,
        private UserModel $userModel
    ) {
        parent::__construct(
            $eventDispatcher,
            $cacheStorageHelper,
            $entityManager,
            $session,
            $requestStack,
            $router,
            $translator,
            $logger,
            $encryptionHelper,
            $leadModel,
            $companyModel,
            $pathsHelper,
            $notificationModel,
            $fieldModel,
            $integrationEntityModel,
            $doNotContactModel
        );
    }

    /**
     * Returns the name of the social integration that must match the name of the file.
     */
    public function getName(): string
    {
        return 'Sugarcrm';
    }

    public function getSupportedFeatures(): array
    {
        // Only push_lead is currently supported for version 7
        return ['push_lead', 'get_leads', 'push_leads'];
    }

    /**
     * Get the array key for clientId.
     */
    public function getClientIdKey(): string
    {
        return 'client_id';
    }

    /**
     * Get the array key for client secret.
     */
    public function getClientSecretKey(): string
    {
        return 'client_secret';
    }

    public function getSecretKeys(): array
    {
        return [
            'client_secret',
            'password',
        ];
    }

    /**
     * Get the array key for the auth token.
     */
    public function getAuthTokenKey(): string
    {
        return (isset($this->keys['version']) && '6' == $this->keys['version']) ? 'id' : 'access_token';
    }

    /**
     * SugarCRM 7 refresh tokens.
     */
    public function getRefreshTokenKeys(): array
    {
        return [
            'refresh_token',
            'expires',
        ];
    }

    public function getAccessTokenUrl(): string
    {
        $apiUrl = ('6' == $this->keys['version']) ? 'service/v4_1/rest.php' : 'rest/v10/oauth2/token';

        return sprintf('%s/%s', $this->keys['sugarcrm_url'], $apiUrl);
    }

    public function getAuthLoginUrl(): string
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
        if (isset($this->keys['version']) && '6' == $this->keys['version']) {
            $success = $this->isAuthorized();
            if (!$success) {
                return $this->authorizationError;
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
     * @return array<string, string>
     */
    public function getRequiredKeyFields(): array
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
     */
    public function getFormLeadFields($settings = []): array
    {
        if (!$this->isAuthorized()) {
            return [];
        }

        if (isset($settings['feature_settings']['objects'])) {
            // combine keys with values
            $settings['feature_settings']['objects'] = array_combine(
                array_values($settings['feature_settings']['objects']),
                $settings['feature_settings']['objects']
            );
        }

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
     * @throws \Exception
     */
    public function getAvailableLeadFields($settings = []): array
    {
        $sugarFields       = [];
        $silenceExceptions = $settings['silence_exceptions'] ?? true;
        $sugarObjects      = [];

        if (!empty($settings['feature_settings']['objects'])) {
            $sugarObjects = $settings['feature_settings']['objects'];
        } else {
            $sugarObjects['Leads']                   = 'Leads';
            $sugarObjects['Contacts']                = 'Contacts';
            $settings['feature_settings']['objects'] = $sugarObjects;
        }

        $isRequired = fn (array $field, $object) => match (true) {
            'Leads' === $object && ('webtolead_email1' === $field['name'] || 'email1' === $field['name']), 'Contacts' === $object && 'email1' === $field['name'], 'id' !== $field['name'] && !empty($field['required']) => true,
            default => false,
        };

        try {
            if (!empty($sugarObjects) and is_array($sugarObjects)) {
                foreach ($sugarObjects as $sObject) {
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

                            if (null != $fields && !empty($fields)) {
                                if (isset($fields['module_fields']) && !empty($fields['module_fields'])) {
                                    // 6.x/community

                                    foreach ($fields['module_fields'] as $fieldInfo) {
                                        if (isset($fieldInfo['name']) && (!in_array($fieldInfo['type'], ['id', 'assigned_user_name', 'link', 'relate']) || ('id' == $fieldInfo['type'] && 'id' == $fieldInfo['name'])
                                        )
                                        ) {
                                            $type      = 'string';
                                            $fieldName = (!str_contains($fieldInfo['name'],
                                                'webtolead_email')) ? $fieldInfo['name'] : str_replace('webtolead_',
                                                    '', $fieldInfo['name']);
                                            // make these congruent as some come in with colons and some do not
                                            $label = str_replace(':', '', $fieldInfo['label']);
                                            if ('company' !== $sObject) {
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
                                    // 7.x
                                    foreach ($fields['fields'] as $fieldInfo) {
                                        if (isset($fieldInfo['name']) && empty($fieldInfo['readonly'])
                                            && (!in_array(
                                                $fieldInfo['type'],
                                                ['id', 'team_list', 'link', 'relate']
                                            )
                                                || ('id' == $fieldInfo['type'] && 'id' == $fieldInfo['name'])
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

                                            $fieldName = (!str_contains($fieldInfo['name'], 'webtolead_email'))
                                                ? $fieldInfo['name']
                                                : str_replace(
                                                    'webtolead_',
                                                    '',
                                                    $fieldInfo['name']
                                                );

                                            $type = 'string';
                                            if ('company' !== $sObject) {
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
                        throw new ApiErrorException($this->authorizationError);
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
     * @return mixed
     */
    public function getFetchQuery($params)
    {
        return $params;
    }

    /**
     * @param array      $params
     * @param array|null $query
     *
     * @return int|null
     */
    public function getCompanies($params = [], $query = null, $executed = null)
    {
        $executed = null;

        $sugarObject           = 'Accounts';
        $params['max_results'] = 100;
        if (!isset($params['offset'])) {
            // First call
            $params['offset'] = 0;
        }

        $query = $params;

        try {
            if ($this->isAuthorized()) {
                $result           = $this->getApiHelper()->getLeads($query, $sugarObject);
                $params['offset'] = $result['next_offset'];
                $executed += $this->amendLeadDataBeforeMauticPopulate($result, $sugarObject);
                if (
                    (isset($result['total_count']) && $result['total_count'] > $params['offset'])   // Sugar 6
                    || (!isset($result['total_count']) && $params['offset'] > -1)) {            // Sugar 7
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
        $integrationEntityRepo = $this->em->getRepository(IntegrationEntity::class);
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
                        $startDate->format('Y-m-d H:i:s'),
                        $endDate->format('Y-m-d H:i:s'),
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

                        $sugarLeadData = [];
                        foreach ($sugarIds as $ids) {
                            $leadId = $ids['internal_entity_id'];
                            if (isset($leadActivity[$leadId])) {
                                $sugarId                            = $ids['integration_entity_id'];
                                $sugarLeadData[$sugarId]            = $leadActivity[$leadId];
                                $sugarLeadData[$sugarId]['id']      = $ids['integration_entity_id'];
                                $sugarLeadData[$sugarId]['leadId']  = $ids['internal_entity_id'];
                                $sugarLeadData[$sugarId]['leadUrl'] = $this->router->generate(
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
                            $startDate->format('Y-m-d H:i:s'),
                            $endDate->format('Y-m-d H:i:s'),
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
     * @param array|null $query
     *
     * @return int|null
     */
    public function getLeads($params = [], $query = null, &$executed = null, $result = [], $object = 'Leads')
    {
        $params['max_results'] = 100;

        if (!isset($params['offset'])) {
            // First call
            $params['offset'] = 0;
        }
        $query = $params;

        try {
            if ($this->isAuthorized()) {
                if ('Activity' !== $object and 'company' !== $object) {
                    $result           = $this->getApiHelper()->getLeads($query, $object);
                    $params['offset'] = $result['next_offset'];
                    $executed += $this->amendLeadDataBeforeMauticPopulate($result, $object);
                    if (
                        (isset($result['total_count']) && $result['total_count'] > $params['offset'])   // Sugar 6
                        || (!isset($result['total_count']) && $params['offset'] > -1)) {            // Sugar 7
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
     * @return string
     */
    public function getErrorsFromResponse($response)
    {
        if ('6' == $this->keys['version']) {
            if (!empty($response['name'])) {
                return $response['description'];
            } else {
                return $this->translator->trans('mautic.integration.error.genericerror', [], 'flashes');
            }
        } else {
            return parent::getErrorsFromResponse($response);
        }
    }

    public function getAuthenticationType(): string
    {
        return (isset($this->keys['version']) && '6' == $this->keys['version']) ? 'rest' : 'oauth2';
    }

    public function getDataPriority(): bool
    {
        return true;
    }

    /**
     * @return array
     */
    public function prepareRequest($url, $parameters, $method, $settings, $authType)
    {
        if ('oauth2' == $authType && empty($settings['authorize_session']) && isset($this->keys['access_token'])) {
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

        if ('6' == $this->keys['version']) {
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

            $this->authorizationError = $error;

            return empty($error);
        } else {
            // SugarCRM 7 uses password grant type so login each time to ensure session is valid
            $this->authCallback();

            return parent::isAuthorized();
        }
    }

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
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object): int
    {
        $settings['feature_settings']['objects'][] = $object;
        $fields                                    = array_keys($this->getAvailableLeadFields($settings));
        $params['fields']                          = implode(',', $fields);

        $count  = 0;
        $entity = null;

        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository(IntegrationEntity::class);
        $companyRepo           = $this->em->getRepository(Company::class);

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

        if (isset($data[$RECORDS_LIST_NAME]) and 'Activity' !== $object) {
            // Get assigned user ids
            $assignedUserIds            = [];
            $onwerEmailByAssignedUserId = [];
            if ('Leads' == $object || 'Contacts' == $object || 'Accounts' == $object) {
                foreach ($data[$RECORDS_LIST_NAME] as $record) {
                    if ('6' == $SUGAR_VERSION) {
                        foreach ($record['name_value_list'] as $item) {
                            if ('assigned_user_id' == $item['name'] && $item['value'] && '' != $item['value']) {
                                $assignedUserIds[] = $item['value'];
                            }
                        }
                    } else {
                        if (isset($record['assigned_user_id']) && '' != $record['assigned_user_id']) {
                            $assignedUserIds[] = $record['assigned_user_id'];
                        }
                    }
                }
            }
            if (!empty($assignedUserIds)) {
                $assignedUserIds            = array_unique($assignedUserIds);
                $onwerEmailByAssignedUserId = $this->getApiHelper()->getEmailBySugarUserId(['ids' => $assignedUserIds]);
            }

            // Get all leads emails
            $checkEmailsInSugar = [];
            if ('Leads' == $object) {
                if ('6' == $SUGAR_VERSION) {
                    foreach ($data[$RECORDS_LIST_NAME] as $record) {
                        foreach ($record['name_value_list'] as $item) {
                            if ('email1' == $item['name'] && $item['value'] && '' != $item['value']) {
                                $checkEmailsInSugar[] = $item['value'];
                            }
                        }
                    }
                } else {
                    if (isset($record['email1']) && '' != $record['email1']) {
                        $checkEmailsInSugar[] = $record['email1'];
                    }
                }
            }
            if (!empty($checkEmailsInSugar)) {
                $sugarLeads = $this->getApiHelper()->getLeads(['checkemail_contacts' => $checkEmailsInSugar, 'offset' => 0, 'max_results' => 1000], 'Contacts');
                if (isset($sugarLeads[$RECORDS_LIST_NAME])) {
                    foreach ($sugarLeads[$RECORDS_LIST_NAME] as $record) {
                        $sugarLeadRecord = [];
                        if ('6' == $SUGAR_VERSION) {
                            foreach ($record['name_value_list'] as $item) {
                                if ('email1' == $item['name'] && $item['value'] && '' != $item['value']) {
                                    $sugarRejectedLeads[] = $item['value'];
                                }
                            }
                        } else {
                            if (isset($record['email1']) && '' != $record['email1']) {
                                $sugarRejectedLeads[] = $record['email1'];
                            }
                        }
                    }
                }
            }

            foreach ($data[$RECORDS_LIST_NAME] as $record) {
                $integrationEntities = [];
                $dataObject          = [];
                if (isset($record[$MODULE_FIELD_NAME]) && 'Accounts' == $record[$MODULE_FIELD_NAME]) {
                    $newName = '';
                } else {
                    $newName = '__'.$object;
                }
                if ('6' == $SUGAR_VERSION) {
                    foreach ($record['name_value_list'] as $item) {
                        if ('Activity' !== $object) {
                            if ($this->checkIfSugarCrmMultiSelectString($item['value'])) {
                                $convertedMultiSelectString         = $this->convertSuiteCrmToMauticMultiSelect($item['value']);
                                $dataObject[$item['name'].$newName] = $convertedMultiSelectString;
                            } else {
                                $dataObject[$item['name'].$newName] = $item['value'];
                            }
                            if ('date_entered' == $item['name']) {
                                $itemDateEntered = new \DateTime($item['value']);
                            }
                            if ('date_modified' == $item['name']) {
                                $itemDateModified = new \DateTime($item['value']);
                            }
                        }
                    }
                } else {
                    if ('Activity' !== $object) {
                        if (isset($record['date_entered']) && '' != $record['date_entered']) {
                            $itemDateEntered = new \DateTime($record['date_entered']);
                        }
                        if (isset($record['date_modified']) && '' != $record['date_modified']) {
                            $itemDateEntered = new \DateTime($record['date_modified']);
                        }
                        foreach ($record as $k => $item) {
                            $dataObject[$k.$newName] = $item;
                        }
                    }
                }
                if ('Leads' == $object && isset($dataObject['email1__Leads']) && null != $dataObject['email1__Leads']
                    && '' != $dataObject['email1__Leads'] && in_array($dataObject['email1__Leads'], $sugarRejectedLeads)) {
                    continue; // Lead email is already in Sugar Contacts. Do not carry on
                }

                if (!empty($dataObject)) {
                    if ('Leads' == $object or 'Contacts' == $object) {
                        if (isset($dataObject['assigned_user_id__'.$object])) {
                            $auid = $dataObject['assigned_user_id__'.$object];
                            if (isset($onwerEmailByAssignedUserId[$auid])) {
                                $dataObject['owner_email'] = $onwerEmailByAssignedUserId[$auid];
                            }
                        }
                        $mauticObjectReference = 'lead';
                        $entity                = $this->getMauticLead($dataObject, true, null, null, $object);
                        $detachClass           = Lead::class;
                        $company               = null;
                        $this->fetchDncToMautic($entity, $data);
                        if ($entity && isset($dataObject['account_id'.$newName]) && '' != trim($dataObject['account_id'.$newName])) {
                            $integrationCompanyEntity = $integrationEntityRepo->findOneBy(
                                [
                                    'integration'         => 'Sugarcrm',
                                    'integrationEntity'   => 'Accounts',
                                    'internalEntity'      => 'company',
                                    'integrationEntityId' => $dataObject['account_id'.$newName],
                                ]
                            );
                            if (isset($integrationCompanyEntity)) {
                                $companyId = $integrationCompanyEntity->getInternalEntityId();
                                $company   = $companyRepo->find($companyId);

                                $this->companyModel->addLeadToCompany($company, $entity);
                                $companyRepo->detachEntity($company);
                                $this->em->detach($entity);
                            }
                        }
                    } elseif ('Accounts' === $object) {
                        $entity                = $this->getMauticCompany($dataObject, $object);
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

                        if (null == $integrationId) {
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
                        $entityRepository = $this->em->getRepository($detachClass);
                        $entityRepository->detachEntity($entity);
                        unset($entity);
                    } else {
                        continue;
                    }
                    ++$count;
                }

                $this->em->getRepository(IntegrationEntity::class)->saveEntities($integrationEntities);
                $this->integrationEntityModel->getRepository()->detachEntities($integrationEntities);
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
    public function appendToForm(&$builder, $data, $formArea): void
    {
        if ('keys' == $formArea) {
            $builder->add('version', ButtonGroupType::class, [
                'choices' => [
                    '6.x/community' => '6',
                    '7.x'           => '7',
                ],
                'label'             => 'mautic.sugarcrm.form.version',
                'constraints'       => [
                    new NotBlank([
                        'message' => 'mautic.core.value.required',
                    ]),
                ],
                'required' => true,
            ]);
        }
        if ('features' == $formArea) {
            $builder->add(
                'updateOwner',
                ChoiceType::class,
                [
                    'choices' => [
                        'mautic.sugarcrm.updateOwner' => 'updateOwner',
                    ],
                    'expanded'          => true,
                    'multiple'          => true,
                    'label'             => 'mautic.sugarcrm.form.updateOwner',
                    'label_attr'        => ['class' => 'control-label'],
                    'placeholder'       => false,
                    'required'          => false,
                    'attr'              => [
                        'onclick' => 'Mautic.postForm(mQuery(\'form[name="integration_details"]\'),\'\');',
                    ],
                ]
            );

            $builder->add(
                'updateDnc',
                ChoiceType::class,
                [
                    'choices' => [
                        'mautic.sugarcrm.updateDnc' => 'updateDnc',
                    ],
                    'expanded'          => true,
                    'multiple'          => true,
                    'label'             => 'mautic.sugarcrm.form.updateDnc',
                    'label_attr'        => ['class' => 'control-label'],
                    'placeholder'       => false,
                    'required'          => false,
                    'attr'              => [
                        'onclick' => 'Mautic.postForm(mQuery(\'form[name="integration_details"]\'),\'\');',
                    ],
                ]
            );

            $builder->add(
                'updateBlanks',
                ChoiceType::class,
                [
                    'choices' => [
                        'mautic.integrations.blanks' => 'updateBlanks',
                    ],
                    'expanded'          => true,
                    'multiple'          => true,
                    'label'             => 'mautic.integrations.form.blanks',
                    'label_attr'        => ['class' => 'control-label'],
                    'placeholder'       => false,
                    'required'          => false,
                ]
            );

            $builder->add(
                'objects',
                ChoiceType::class,
                [
                    'choices' => [
                        'mautic.sugarcrm.object.lead'    => 'Leads',
                        'mautic.sugarcrm.object.contact' => 'Contacts',
                        'mautic.sugarcrm.object.company' => 'company',
                    ],
                    'expanded'          => true,
                    'multiple'          => true,
                    'label'             => 'mautic.sugarcrm.form.objects_to_pull_from',
                    'label_attr'        => ['class' => ''],
                    'placeholder'       => false,
                    'required'          => false,
                ]
            );

            $builder->add(
                'activityEvents',
                ChoiceType::class,
                [
                    'choices'           => array_flip($this->leadModel->getEngagementTypes()), // Choice type expects labels as keys
                    'label'             => 'mautic.salesforce.form.activity_included_events',
                    'label_attr'        => [
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
     * @param Lead  $lead
     * @param array $config
     *
     * @return array|bool
     */
    public function pushLead($lead, $config = [])
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return [];
        }

        $object = 'Leads'; // Sugar objects, default is Leads

        // Check if lead has alredy been synched
        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository(IntegrationEntity::class);
        // Check if it is a sugar CRM alredy synched lead
        $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Sugarcrm', $object, 'lead', $lead->getId());
        if (empty($integrationId)) {
            // Check if it is a sugar CRM alredy synched lead
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
            } // Do not push lead if it was already synched
        }

        $fieldsToUpdateInSugar      = isset($config['update_mautic']) ? array_keys($config['update_mautic'], 0) : [];
        $leadSugarFieldsToCreate    = $this->cleanSugarData($config, array_keys($config['leadFields']), $object);
        $fieldsToUpdateInLeadsSugar = $this->cleanSugarData($config, $fieldsToUpdateInSugar, $object);
        $leadFields                 = array_intersect_key($leadSugarFieldsToCreate, $fieldsToUpdateInLeadsSugar);

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
     */
    public function convertLeadFieldKey(string $key, $field): string
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
     */
    public function cleanSugarData($fields, $keys, $object): array
    {
        $leadFields = [];

        foreach ($keys as $key) {
            if (strstr($key, '__'.$object)) {
                $newKey = str_replace('__'.$object, '', $key);
                // $leadFields[$object][$newKey] = $fields['leadFields'][$key];
                $leadFields[$newKey] = $fields['leadFields'][$key];
            }
        }

        return $leadFields;
    }

    /**
     * @param array $params
     *
     * @return mixed[]
     */
    public function pushLeads($params = []): array
    {
        [$fromDate, $toDate]     = $this->getSyncTimeframeDates($params);
        $limit                   = $params['limit'];
        $config                  = $this->mergeConfigToFeatureSettings();
        $integrationEntityRepo   = $this->em->getRepository(IntegrationEntity::class);
        $mauticData              = $leadsToUpdate              = $fields              = [];
        $fieldsToUpdateInSugar   = isset($config['update_mautic']) ? array_keys($config['update_mautic'], 0) : [];
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

            // Leads fields
            $leadSugarFieldsToCreate    = $this->cleanSugarData($config, array_keys($config['leadFields']), 'Leads');
            $fieldsToUpdateInLeadsSugar = $this->cleanSugarData($config, $fieldsToUpdateInSugar, 'Leads');
            $leadSugarFields            = array_intersect_key($leadSugarFieldsToCreate, $fieldsToUpdateInLeadsSugar);

            // Contacts fields
            $contactSugarFields            = $this->cleanSugarData($config, array_keys($config['leadFields']), 'Contacts');
            $fieldsToUpdateInContactsSugar = $this->cleanSugarData($config, $fieldsToUpdateInSugar, 'Contacts');
            $contactSugarFields            = array_intersect_key($contactSugarFields, $fieldsToUpdateInContactsSugar);

            $availableFields = $this->getAvailableLeadFields(['feature_settings' => ['objects' => ['Leads', 'Contacts']]]);

            // update lead/contact records
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

        // create lead records
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
            [$checkEmailsUpdatedInSugar, $deletedRedords] = $this->getObjectDataToUpdate($checkObjectEmailsInSugar, $mauticData, $availableFields, $contactSugarFields, $leadSugarFields, $object);
            // recheck synced records that might have been deleted in Sugar (deleted records don't come back in the query)
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
        if ($checkEmailsInSugar && isset($checkEmailsInSugar['Leads'])) {
            [$checkEmailsInSugar, $deletedSugarLeads]     = $this->getObjectDataToUpdate($checkEmailsInSugar['Leads'], $mauticData, $availableFields, $contactSugarFields, $leadSugarFields, 'Leads');
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
                        $leadSugarFieldsToCreate, // use all matched fields when creating new records in Sugar
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
     */
    private function pushDncToSugar(array $lead, array &$body): void
    {
        $features = $this->settings->getFeatureSettings();
        // update DNC sync disabled
        if (empty($features['updateDnc'])) {
            return;
        }
        $leadEntity = $this->leadModel->getEntity($lead['internal_entity_id']);
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
        foreach ($sugarDncKeys as $sugarDncKey) {
            $body[] = ['name' => $sugarDncKey, 'value' => 0];
        }
    }

    private function fetchDncToMautic(Lead $lead = null, array $data): void
    {
        if (is_null($lead)) {
            return;
        }

        $features = $this->settings->getFeatureSettings();
        if (empty($features['updateDnc'])) {
            return;
        }

        // try find opt_out value for lead
        $isContactable = true;
        foreach ($data['relationship_list'] as $relationshipList) {
            foreach ($relationshipList['link_list'] as $links) {
                if ('email_addresses' == $links['name']) {
                    foreach ($links['records'] as $records) {
                        if (!empty($records['link_value']['email_address']['value']) && $records['link_value']['email_address']['value'] == $lead->getEmail() && !empty($records['link_value']['opt_out']['value'])) {
                            $isContactable = false;
                            break 3;
                        }
                    }
                }
            }
        }

        $reason = \Mautic\LeadBundle\Entity\DoNotContact::UNSUBSCRIBED;
        if (!$isContactable) {
            $this->doNotContactModel->addDncForContact($lead->getId(), 'email', $reason, $this->getName());
        } else {
            $this->doNotContactModel->removeDncForContact($lead->getId(), 'email', true, $reason);
        }
    }

    /**
     * @param string $object
     *
     * @return array The first element is made up of records that exist in Mautic, but which no longer have a match in CRM.
     *               We therefore assume that they've been deleted in CRM and will mark them as deleted in the pushLeads function (~line 1320).
     *               The second element contains Ids of records that were explicitly marked as deleted in CRM. ATM, nothing is done with this data.
     */
    public function getObjectDataToUpdate($checkEmailsInSugar, &$mauticData, $availableFields, $contactSugarFields, $leadSugarFields, $object = 'Leads'): array
    {
        $config     = $this->mergeConfigToFeatureSettings([]);
        $queryParam = ('Leads' == $object) ? 'checkemail' : 'checkemail_contacts';

        $sugarLead         = $this->getApiHelper()->getLeads([$queryParam => array_keys($checkEmailsInSugar), 'offset' => 0, 'max_results' => 1000], $object);
        $deletedSugarLeads = $sugarLeadRecords = [];

        if (isset($sugarLead['entry_list'])) {
            // Sugar 6.X
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
                        if ('email_addresses' == $links['name']) {
                            foreach ($links['records'] as $records) {
                                foreach ($records as $contactEmails) {
                                    foreach ($contactEmails as $anyAddress) {
                                        if ('email_address' == $anyAddress['name'] && !empty($anyAddress['value'])) {
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
        } elseif (isset($sugarLead['records'])) { // Sugar 7
            $sugarLeadRecords = $sugarLead['records'];
        }

        foreach ($sugarLeadRecords as $sugarLeadRecord) {
            if (isset($sugarLeadRecord) && $sugarLeadRecord) {
                $email           = $sugarLeadRecord['email1'];
                $key             = mb_strtolower($email);
                $leadOwnerEmails = [];
                if (!empty($checkEmailsInSugar)) {
                    foreach ($checkEmailsInSugar as $emailKey => $mauticRecord) {
                        if ($key == $emailKey) {
                            $isConverted = (isset($sugarLeadRecord['contact_id'])
                                && null != $sugarLeadRecord['contact_id']
                                && '' != $sugarLeadRecord['contact_id']);

                            $sugarIdMapping[$checkEmailsInSugar[$key]['internal_entity_id']] = ($isConverted) ? $sugarLeadRecord['contact_id'] : $sugarLeadRecord['id'];
                            $lead['owner_email']                                             = $this->getOwnerEmail($mauticRecord);
                            if ($lead['owner_email']) {
                                $leadOwnerEmails[] = $lead['owner_email'];
                            }
                            $ownerAssignedUserIdByEmail = $this->getApiHelper()->getIdBySugarEmail(['emails' => array_unique($leadOwnerEmails)]);
                            if (empty($sugarLeadRecord['deleted']) || 0 == $sugarLeadRecord['deleted']) {
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
                                    $isConverted || ('Contacts' == $object) ? $contactSugarFields : $leadSugarFields,
                                    $isConverted || ('Contacts' == $object) ? 'Contacts' : 'Leads',
                                    $checkEmailsInSugar[$key],
                                    $ownerAssignedUserIdByEmail,
                                    $isConverted ? $sugarLeadRecord['contact_id'] : $sugarLeadRecord['id']
                                );
                            } else {
                                // @todo - Should return also deleted contacts from Sugar
                                $deletedSugarLeads[] = $sugarLeadRecord['id'];
                                if (!empty($sugarLeadRecord['contact_id']) || '' != $sugarLeadRecord['contact_id']) {
                                    $deletedSugarLeads[] = $sugarLeadRecord['contact_id'];
                                }
                            }
                            unset($checkEmailsInSugar[$key]);
                        }
                    }
                }
            }
        }

        return [$checkEmailsInSugar, $deletedSugarLeads];
    }

    /**
     * @return array
     */
    public function getSugarLeadId($lead)
    {
        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository(IntegrationEntity::class);
        // try searching for lead as this has been changed before in updated done to the plugin
        $result = $integrationEntityRepo->getIntegrationsEntityId('Sugarcrm', null, 'lead', $lead->getId());

        return $result;
    }

    /**
     * @param array $lead
     */
    protected function getOwnerEmail($lead)
    {
        if (isset($lead['owner_id']) && !empty($lead['owner_id'])) {
            /** @var \Mautic\UserBundle\Entity\User $user */
            $user = $this->userModel->getEntity($lead['owner_id']);

            return $user->getEmail();
        }

        return null;
    }

    protected function buildCompositeBody(&$mauticData, $availableFields, $fieldsToUpdateInSugarUpdate, $object, $lead, $onwerAssignedUserIdByEmail = null, $objectId = null)
    {
        $body = [];
        if (isset($lead['email']) && !empty($lead['email'])) {
            // update and create (one query) every 200 records

            foreach ($fieldsToUpdateInSugarUpdate as $sugarField => $mauticField) {
                $required = !empty($availableFields[$object][$sugarField.'__'.$object]['required']);
                if (isset($lead[$mauticField])) {
                    if (str_contains($lead[$mauticField], '|')) {
                        // Transform Mautic Multi Select into SugarCRM/SuiteCRM Multi Select format
                        $value = $this->convertMauticToSuiteCrmMultiSelect($lead[$mauticField]);
                    } else {
                        $value = $lead[$mauticField];
                    }
                    $body[] = ['name' => $sugarField, 'value' =>  $value];
                } elseif ($required) {
                    $value  = $this->translator->trans('mautic.integration.form.lead.unknown');
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
                $this->pushDncToSugar($lead, $body);

                $mauticData[$object][] = $body;
            }
        }
    }

    /**
     * @param array $response
     */
    protected function processCompositeResponse($response): array
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
                        [$contactId, $object, $integrationEntityId] = $reference;
                    } else {
                        [$contactId, $object] = $reference;
                    }
                }

                if (isset($item['ko']) && $item['ko']) {
                    $this->logIntegrationError(new \Exception($item['error']));

                    if ($integrationEntityId) {
                        $integrationEntity = $this->em->getReference(IntegrationEntity::class, $integrationEntityId);
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
                            $integrationEntity = $this->em->getReference(IntegrationEntity::class, $integrationEntityId);
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
                    $error = 'Unknown status code '.$item['httpStatusCode'];
                    $this->logIntegrationError(new \Exception($error.' ('.$item['reference_id'].')'));
                }
            }

            if ($persistEntities) {
                $this->em->getRepository(IntegrationEntity::class)->saveEntities($persistEntities);
                $this->integrationEntityModel->getRepository()->detachEntities($persistEntities);
                unset($persistEntities);
            }
        }

        return [$updated, $created, $errored];
    }

    /**
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
            $fields = $fieldsToUpdate;
        } else {
            $fields = array_flip($fieldsToUpdate);
        }

        return $this->prepareFieldsForSync($fields, $fieldsToUpdate, $objects);
    }

    /**
     * @param array $fields
     * @param array $keys
     * @param mixed $object
     *
     * @return array
     */
    public function prepareFieldsForSync($fields, $keys, $object = null)
    {
        $leadFields = [];
        if (null === $object) {
            $object = 'Lead';
        }

        $objects = (!is_array($object)) ? [$object] : $object;
        if (is_string($object) && 'Accounts' === $object) {
            return $fields['companyFields'] ?? $fields;
        }

        if (isset($fields['leadFields'])) {
            $fields = $fields['leadFields'];
            $keys   = array_keys($fields);
        }

        foreach ($objects as $obj) {
            if (!isset($leadFields[$obj])) {
                $leadFields[$obj] = [];
            }

            foreach ($keys as $key) {
                if (strpos($key, '__'.$obj)) {
                    $newKey = str_replace('__'.$obj, '', $key);
                    if ('Id' === $newKey) {
                        // Don't map Id for push
                        continue;
                    }

                    $leadFields[$obj][$newKey] = $fields[$key];
                }
            }
        }

        return (is_array($object)) ? $leadFields : $leadFields[$object];
    }

    /**
     * @param string $priorityObject
     *
     * @return mixed
     */
    protected function getPriorityFieldsForMautic($config, $object = null, $priorityObject = 'mautic')
    {
        $fields = parent::getPriorityFieldsForMautic($config, $object, $priorityObject);

        return ($object && isset($fields[$object])) ? $fields[$object] : $fields;
    }

    protected function prepareFieldsForPush($fields): array
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
     */
    public function convertMauticToSuiteCrmMultiSelect($mauticMultiSelectStringToConvert): string
    {
        // $mauticMultiSelectStringToConvert = 'test|enhancedapi|dataservices';
        $multiSelectArrayValues             = explode('|', $mauticMultiSelectStringToConvert);
        $convertedSugarCrmMultiSelectString = '';
        foreach ($multiSelectArrayValues as $item) {
            $convertedSugarCrmMultiSelectString = $convertedSugarCrmMultiSelectString.'^'.$item.'^,';
        }

        return substr($convertedSugarCrmMultiSelectString, 0, -1);
    }

    /**
     * Checks if a string contains SuiteCRM / SugarCRM 6.x Multi-Select values.
     *
     * @param string $stringToCheck
     */
    public function checkIfSugarCrmMultiSelectString($stringToCheck): bool
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
     */
    public function convertSuiteCrmToMauticMultiSelect($suiteCrmMultiSelectStringToConvert): string
    {
        // Mautic Multi Select format - 'choice1|choice2|choice_3'
        $regexString            = '/(\^)(?:([A-Za-z0-9\-\_]+))(\^)/';
        preg_match_all($regexString, $suiteCrmMultiSelectStringToConvert, $matches, PREG_SET_ORDER, 0);
        $convertedString        = '';
        foreach ($matches as $innerArray) {
            $convertedString     = $convertedString.$innerArray[2].'|';
        }

        return substr($convertedString, 0, -1);
    }
}
