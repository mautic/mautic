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
use Mautic\FormBundle\Model\SubmissionModel;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\IdentifyCompanyHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\SalesforceApi;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class SalesforceIntegration.
 *
 * @method SalesforceApi getApiHelper
 */
class SalesforceIntegration extends CrmAbstractIntegration
{
    private $objects = [
        'Lead',
        'Contact',
        'Account',
    ];

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Salesforce';
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
     * Get the array key for the auth token.
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'access_token';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'client_id'     => 'mautic.integration.keyfield.consumerid',
            'client_secret' => 'mautic.integration.keyfield.consumersecret',
        ];
    }

    /**
     * Get the keys for the refresh token and expiry.
     *
     * @return array
     */
    public function getRefreshTokenKeys()
    {
        return ['refresh_token', ''];
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'get_leads', 'push_leads'];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        $config = $this->mergeConfigToFeatureSettings([]);

        if (isset($config['sandbox'][0]) and $config['sandbox'][0] === 'sandbox') {
            return 'https://test.salesforce.com/services/oauth2/token';
        }

        return 'https://login.salesforce.com/services/oauth2/token';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        $config = $this->mergeConfigToFeatureSettings([]);

        if (isset($config['sandbox'][0]) and $config['sandbox'][0] === 'sandbox') {
            return 'https://test.salesforce.com/services/oauth2/authorize';
        }

        return 'https://login.salesforce.com/services/oauth2/authorize';
    }

    /**
     * @return string
     */
    public function getAuthScope()
    {
        return 'api refresh_token';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return sprintf('%s/services/data/v34.0/sobjects', $this->keys['instance_url']);
    }

    /**
     * @return string
     */
    public function getQueryUrl()
    {
        return sprintf('%s/services/data/v34.0', $this->keys['instance_url']);
    }

    /**
     * @return string
     */
    public function getCompositeUrl()
    {
        return sprintf('%s/services/data/v38.0', $this->keys['instance_url']);
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $inAuthorization
     */
    public function getBearerToken($inAuthorization = false)
    {
        if (!$inAuthorization && isset($this->keys[$this->getAuthTokenKey()])) {
            return $this->keys[$this->getAuthTokenKey()];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
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
        $leadFields    = $this->getFormFieldsByObject('Lead', $settings);
        $contactFields = $this->getFormFieldsByObject('Contact', $settings);

        return array_merge($leadFields, $contactFields);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     *
     * @throws \Exception
     */
    public function getAvailableLeadFields($settings = [])
    {
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        $salesForceObjects = [];

        if (isset($settings['feature_settings']['objects'])) {
            $salesForceObjects = $settings['feature_settings']['objects'];
        } else {
            $salesForceObjects[] = 'Lead';
        }

        $isRequired = function (array $field, $object) {
            return
                ($field['type'] !== 'boolean' && empty($field['nillable']) && !in_array($field['name'], ['Status', 'Id'])) ||
                ($object == 'Lead' && in_array($field['name'], ['Company'])) ||
                (in_array($object, ['Lead', 'Contact']) && 'Email' === $field['name']);
        };

        $salesFields = [];
        try {
            if (!empty($salesForceObjects) and is_array($salesForceObjects)) {
                foreach ($salesForceObjects as $key => $sfObject) {
                    if ('Account' === $sfObject) {
                        // Match SF object to Mautic's
                        $sfObject = 'company';
                    }

                    if (isset($sfObject) and $sfObject == 'Activity') {
                        continue;
                    }

                    $sfObject = trim($sfObject);

                    // Check the cache first
                    $settings['cache_suffix'] = $cacheSuffix = '.'.$sfObject;
                    if ($fields = parent::getAvailableLeadFields($settings)) {
                        if (('company' === $sfObject && isset($fields['Id'])) || isset($fields['Id__'.$sfObject])) {
                            $salesFields[$sfObject] = $fields;

                            continue;
                        }
                    }

                    if ($this->isAuthorized()) {
                        if (!isset($salesFields[$sfObject])) {
                            $fields = $this->getApiHelper()->getLeadFields($sfObject);
                            if (!empty($fields['fields'])) {
                                foreach ($fields['fields'] as $fieldInfo) {
                                    if ((!$fieldInfo['updateable'] && (!$fieldInfo['calculated'] && $fieldInfo['name'] != 'Id'))
                                        || !isset($fieldInfo['name'])
                                        || in_array(
                                            $fieldInfo['type'],
                                            ['reference']
                                        )
                                    ) {
                                        continue;
                                    }
                                    switch ($fieldInfo['type']) {
                                        case 'boolean': $type = 'boolean';
                                                        break;
                                        case 'datetime': $type = 'datetime';
                                            break;
                                        case 'date': $type = 'date';
                                            break;
                                        default: $type = 'string';
                                    }
                                    if ($sfObject !== 'company') {
                                        $salesFields[$sfObject][$fieldInfo['name'].'__'.$sfObject] = [
                                            'type'        => $type,
                                            'label'       => $sfObject.'-'.$fieldInfo['label'],
                                            'required'    => $isRequired($fieldInfo, $sfObject),
                                            'group'       => $sfObject,
                                            'optionLabel' => $fieldInfo['label'],
                                        ];
                                    } else {
                                        $salesFields[$sfObject][$fieldInfo['name']] = [
                                            'type'     => $type,
                                            'label'    => $fieldInfo['label'],
                                            'required' => $isRequired($fieldInfo, $sfObject),
                                        ];
                                    }
                                }

                                $this->cache->set('leadFields'.$cacheSuffix, $salesFields[$sfObject]);
                            }
                        }

                        asort($salesFields[$sfObject]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $salesFields;
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return array
     */
    public function getFormNotes($section)
    {
        if ($section == 'authorization') {
            return ['mautic.salesforce.form.oauth_requirements', 'warning'];
        }

        return parent::getFormNotes($section);
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
     * @param       $data
     * @param       $object
     * @param array $params
     *
     * @return array
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object, $params = [])
    {
        $updated               = 0;
        $created               = 0;
        $counter               = 0;
        $entity                = null;
        $detachClass           = null;
        $mauticObjectReference = null;
        $integrationMapping    = [];

        if (isset($data['records']) and $object !== 'Activity') {
            foreach ($data['records'] as $record) {
                $this->logger->debug('SALESFORCE: amendLeadDataBeforeMauticPopulate record '.var_export($record, true));
                if (isset($params['progress'])) {
                    $params['progress']->advance();
                }

                $dataObject = [];
                if (isset($record['attributes']['type']) && $record['attributes']['type'] == 'Account') {
                    $newName = '';
                } else {
                    $newName = '__'.$object;
                }

                foreach ($record as $key => $item) {
                    $dataObject[$key.$newName] = $item;
                }

                if (isset($dataObject) && $dataObject) {
                    $entity = false;
                    switch ($object) {
                        case 'Lead':
                        case 'Contact':
                            // Set owner so that it maps if configured to do so
                            if (!empty($dataObject['Owner__Lead']['Email'])) {
                                $dataObject['owner_email'] = $dataObject['Owner__Lead']['Email'];
                            } elseif (!empty($dataObject['Owner__Contact']['Email'])) {
                                $dataObject['owner_email'] = $dataObject['Owner__Contact']['Email'];
                            }
                            $entity                = $this->getMauticLead($dataObject, true, null, null, $object);
                            $mauticObjectReference = 'lead';
                            $detachClass           = Lead::class;

                            break;
                        case 'Account':
                            $entity                = $this->getMauticCompany($dataObject, 'Account');
                            $mauticObjectReference = 'company';
                            $detachClass           = Company::class;

                            break;
                        default:
                            $this->logIntegrationError(
                                new \Exception(
                                    sprintf('Received an unexpected object without an internalObjectReference "%s"', $object)
                                )
                            );
                            break;
                    }

                    if (!$entity) {
                        continue;
                    }

                    $integrationMapping[$entity->getId()] = [
                        'entity'                => $entity,
                        'integration_entity_id' => $record['Id'],
                    ];

                    if (method_exists($entity, 'isNewlyCreated') && $entity->isNewlyCreated()) {
                        ++$created;
                    } else {
                        ++$updated;
                    }

                    ++$counter;

                    if ($counter >= 100) {
                        // Persist integration entities
                        $this->buildIntegrationEntities($integrationMapping, $object, $mauticObjectReference, $params);
                        $counter = 0;
                        $this->em->clear($detachClass);
                        $integrationMapping = [];
                    }
                }
            }

            if (count($integrationMapping)) {
                // Persist integration entities
                $this->buildIntegrationEntities($integrationMapping, $object, $mauticObjectReference, $params);
                $this->em->clear($detachClass);
            }

            unset($data['records']);
            $this->logger->debug('SALESFORCE: amendLeadDataBeforeMauticPopulate response '.var_export($data, true));

            unset($data);
            $this->persistIntegrationEntities = [];
            unset($dataObject);
        }

        return [$updated, $created];
    }

    /**
     * @param FormBuilder $builder
     * @param array       $data
     * @param string      $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            $builder->add(
                'sandbox',
                'choice',
                [
                    'choices' => [
                        'sandbox' => 'mautic.salesforce.sandbox',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.salesforce.form.sandbox',
                    'label_attr'  => ['class' => 'control-label'],
                    'empty_value' => false,
                    'required'    => false,
                    'attr'        => [
                        'onclick' => 'Mautic.postForm(mQuery(\'form[name="integration_details"]\'),\'\');',
                    ],
                ]
            );

            $builder->add(
                'updateOwner',
                'choice',
                [
                    'choices' => [
                        'updateOwner' => 'mautic.salesforce.updateOwner',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.salesforce.form.updateOwner',
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
                        'Lead'     => 'mautic.salesforce.object.lead',
                        'Contact'  => 'mautic.salesforce.object.contact',
                        'company'  => 'mautic.salesforce.object.company',
                        'Activity' => 'mautic.salesforce.object.activity',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.salesforce.form.objects_to_pull_from',
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );

            $builder->add(
                'namespace',
                'text',
                [
                    'label'      => 'mautic.salesforce.form.namespace_prefix',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            );
        }
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
        if (is_string($object) && 'Account' === $object) {
            return isset($fields['companyFields']) ? $fields['companyFields'] : $fields;
        }

        if (isset($fields['leadFields'])) {
            $fields = $fields['leadFields'];
            $keys   = array_keys($fields);
        }

        foreach ($keys as $key) {
            foreach ($objects as $obj) {
                if (!isset($leadFields[$obj])) {
                    $leadFields[$obj] = [];
                }

                if (strpos($key, '__'.$obj)) {
                    $newKey                    = str_replace('__'.$obj, '', $key);
                    $leadFields[$obj][$newKey] = $fields[$key];
                }
            }
        }

        return (is_array($object)) ? $leadFields : $leadFields[$object];
    }

    /**
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @param array                          $config
     *
     * @return array|bool
     */
    public function pushLead($lead,  $config = [])
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return [];
        }

        $mappedData = $this->mapContactDataForPush($lead, $config);

        // No fields are mapped so bail
        if (empty($mappedData)) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $existingPersons = $this->getApiHelper()->getPerson(
                    [
                        'Lead'    => $mappedData['Lead']['create'],
                        'Contact' => $mappedData['Contact']['create'],
                    ]
                );

                $personFound = false;
                $people      = [
                    'Contact' => [],
                    'Lead'    => [],
                ];

                foreach (['Contact', 'Lead'] as $object) {
                    if (!empty($existingPersons[$object])) {
                        $fieldsToUpdate = $mappedData[$object]['update'];
                        $fieldsToUpdate = $this->getBlankFieldsToUpdate($fieldsToUpdate, $existingPersons[$object], $mappedData, $config);
                        $personFound    = true;
                        if (!empty($fieldsToUpdate)) {
                            foreach ($existingPersons[$object] as $person) {
                                $personData                     = $this->getApiHelper()->updateObject($fieldsToUpdate, $object, $person['Id']);
                                $people[$object][$person['Id']] = $person['Id'];
                            }
                        }
                    }

                    if ('Lead' === $object && !$personFound) {
                        $personData                         = $this->getApiHelper()->createLead($mappedData[$object]['create']);
                        $people[$object][$personData['Id']] = $personData['Id'];
                        $personFound                        = true;
                    }

                    if (isset($personData['Id'])) {
                        /** @var IntegrationEntityRepository $integrationEntityRepo */
                        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
                        $integrationId         = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', $object, 'lead', $lead->getId());

                        $integrationEntity = (empty($integrationId))
                            ? $this->createIntegrationEntity($object, $personData['Id'], 'lead', $lead->getId(), [], false)
                            :
                            $this->em->getReference('MauticPluginBundle:IntegrationEntity', $integrationId[0]['id']);

                        $integrationEntity->setLastSyncDate($this->getLastSyncDate());
                        $integrationEntityRepo->saveEntity($integrationEntity);
                    }
                }

                // Return success if any Contact or Lead was updated or created
                return ($personFound) ? $people : false;
            }
        } catch (\Exception $e) {
            if ($e instanceof ApiErrorException) {
                $e->setContact($lead);
            }

            $this->logIntegrationError($e);
        }

        return false;
    }

    /**
     * @param array  $params
     * @param null   $query
     * @param null   $executed
     * @param array  $result
     * @param string $object
     *
     * @return array|null
     */
    public function getLeads($params = [], $query = null, &$executed = null, $result = [], $object = 'Lead')
    {
        if (!$query) {
            $query = $this->getFetchQuery($params);
        }

        if (!is_array($executed)) {
            $executed = [
                0 => 0,
                1 => 0,
            ];
        }

        try {
            if ($this->isAuthorized()) {
                $total     = null;
                $progress  = null;
                $processed = 0;
                $retry     = 0;
                while (true) {
                    $result = $this->getApiHelper()->getLeads($query, $object);
                    if (!isset($result['records'])) {
                        throw new ApiErrorException(var_export($result, true));
                    }

                    if (null === $total) {
                        $total = $result['totalSize'];

                        if (isset($params['output'])) {
                            $progress = new ProgressBar($params['output'], $total);
                            $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% ('.$object.')');

                            $params['progress'] = $progress;
                        }
                    }

                    list($justUpdated, $justCreated) = $this->amendLeadDataBeforeMauticPopulate($result, $object, $params);

                    $executed[0] += $justUpdated;
                    $executed[1] += $justCreated;
                    $processed += count($result['records']);

                    if (isset($result['nextRecordsUrl'])) {
                        $query['nextUrl'] = $result['nextRecordsUrl'];
                        $retry            = 0;
                    } else {
                        if ($processed < $total) {
                            // Something has gone wrong so try a few more times before giving up
                            if ($retry <= 5) {
                                $this->logger->debug("SALESFORCE: Processed less than total but didn't get a nextRecordsUrl in the response for getLeads ($object): ".var_export($result, true));

                                usleep(500);
                                ++$retry;

                                continue;
                            } else {
                                // Throw an exception cause something isn't right
                                throw new ApiErrorException("Expected to process $total but only processed $processed: ".var_export($result, true));
                            }
                        }
                        break;
                    }
                }

                if ($progress) {
                    $progress->finish();
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        $this->logger->debug('SALESFORCE: '.$this->getApiHelper()->getRequestCounter().' API requests made for getLeads: '.$object);

        return $executed;
    }

    /**
     * @param array $params
     * @param null  $query
     * @param null  $executed
     *
     * @return array|null
     */
    public function getCompanies($params = [], $query = null, $executed = null)
    {
        return $this->getLeads($params, $query, $executed, [], 'Account');
    }

    /**
     * @param array $params
     *
     * @return int|null
     *
     * @throws \Exception
     */
    public function pushLeadActivity($params = [])
    {
        $executed = null;

        $query  = $this->getFetchQuery($params);
        $config = $this->mergeConfigToFeatureSettings([]);

        /** @var SalesforceApi $apiHelper */
        $apiHelper = $this->getApiHelper();

        $salesForceObjects[] = 'Lead';
        if (isset($config['objects']) && !empty($config['objects'])) {
            $salesForceObjects = $config['objects'];
        }

        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $startDate             = new \DateTime($query['start']);
        $endDate               = new \DateTime($query['end']);
        $limit                 = 100;

        foreach ($salesForceObjects as $object) {
            try {
                if ($this->isAuthorized()) {
                    // Get first batch
                    $start         = 0;
                    $salesForceIds = $integrationEntityRepo->getIntegrationsEntityId(
                        'Salesforce',
                        $object,
                        'lead',
                        null,
                        $startDate->format('Y-m-d H:m:s'),
                        $endDate->format('Y-m-d H:m:s'),
                        true,
                        $start,
                        $limit
                    );

                    while (!empty($salesForceIds)) {
                        $executed += count($salesForceIds);

                        // Extract a list of lead Ids
                        $leadIds = [];
                        $sfIds   = [];
                        foreach ($salesForceIds as $ids) {
                            $leadIds[] = $ids['internal_entity_id'];
                            $sfIds[]   = $ids['integration_entity_id'];
                        }

                        // Collect lead activity for this batch
                        $leadActivity = $this->getLeadData(
                            $startDate,
                            $endDate,
                            $leadIds
                        );

                        $this->logger->debug('SALESFORCE: Syncing activity for '.count($leadActivity).' contacts ('.implode(', ', array_keys($leadActivity)).')');
                        $this->logger->debug('SALESFORCE: Syncing activity for '.var_export($sfIds, true));

                        $salesForceLeadData = [];
                        foreach ($salesForceIds as $ids) {
                            $leadId = $ids['internal_entity_id'];
                            if (isset($leadActivity[$leadId])) {
                                $sfId                                 = $ids['integration_entity_id'];
                                $salesForceLeadData[$sfId]            = $leadActivity[$leadId];
                                $salesForceLeadData[$sfId]['id']      = $ids['integration_entity_id'];
                                $salesForceLeadData[$sfId]['leadId']  = $ids['internal_entity_id'];
                                $salesForceLeadData[$sfId]['leadUrl'] = $this->router->generate(
                                    'mautic_plugin_timeline_view',
                                    ['integration' => 'Salesforce', 'leadId' => $leadId],
                                    UrlGeneratorInterface::ABSOLUTE_URL
                                );
                            } else {
                                $this->logger->debug('SALESFORCE: No activity found for contact ID '.$leadId);
                            }
                        }

                        if (!empty($salesForceLeadData)) {
                            $apiHelper->createLeadActivity($salesForceLeadData, $object);
                        } else {
                            $this->logger->debug('SALESFORCE: No contact activity to sync');
                        }

                        // Get the next batch
                        $start += $limit;
                        $salesForceIds = $integrationEntityRepo->getIntegrationsEntityId(
                            'Salesforce',
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
        $options      = [
            'leadIds'      => $leadIds,
            'basic_select' => true,
            'fromDate'     => $startDate,
            'toDate'       => $endDate,
        ];

        /** @var LeadModel $leadModel */
        $leadModel      = $this->leadModel;
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
        $emailModel            = $this->factory->getModel('email');
        $emailRepo             = $emailModel->getStatRepository();
        $emailOptions          = $options;
        $emailOptions['state'] = 'read';
        $results               = $emailRepo->getLeadStats(null, $emailOptions);
        $emailStats            = [];
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
                        "mautic.salesforce.activity.points_{$subject}",
                        $row['delta'],
                        ['%points%' => $row['delta']]
                    );
                    $activity[$i]['eventType']   = 'point';
                    $activity[$i]['name']        = $translator->trans('mautic.salesforce.activity.point')." ($pointsString)";
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
                    $activity[$i]['name']        = $translator->trans('mautic.salesforce.activity.email').": $name";
                    $activity[$i]['description'] = $translator->trans('mautic.email.sent').": $name";
                    $activity[$i]['dateAdded']   = $row['dateSent'];
                    $activity[$i]['id']          = 'emailStat'.$row['id'];
                    ++$i;
                }
            }

            if (isset($formSubmissions[$leadId])) {
                foreach ($formSubmissions[$leadId] as $row) {
                    $activity[$i]['eventType']   = 'form';
                    $activity[$i]['name']        = $this->getTranslator()->trans('mautic.salesforce.activity.form').': '.$row['name'];
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
     * @param array $params
     *
     * @return mixed
     */
    public function pushLeads($params = [])
    {
        $limit                   = (isset($params['limit'])) ? $params['limit'] : 100;
        list($fromDate, $toDate) = $this->getSyncTimeframeDates($params);
        $config                  = $this->mergeConfigToFeatureSettings($params);
        $integrationEntityRepo   = $this->getIntegrationEntityRepository();

        $totalUpdated = 0;
        $totalCreated = 0;
        $totalErrors  = 0;

        list($fieldMapping, $mauticLeadFieldString, $supportedObjects) = $this->prepareFieldsForPush($config);

        if (empty($fieldMapping)) {
            return [0, 0, 0, 0];
        }

        $originalLimit = $limit;
        $progress      = false;

        // Get a total number of contacts to be updated and/or created for the progress counter
        $totalToUpdate = array_sum(
            $integrationEntityRepo->findLeadsToUpdate(
                'Salesforce',
                'lead',
                $mauticLeadFieldString,
                false,
                $fromDate,
                $toDate,
                $supportedObjects,
                []
            )
        );
        $totalToCreate = (in_array('Lead', $supportedObjects)) ? $integrationEntityRepo->findLeadsToCreate(
            'Salesforce',
            $mauticLeadFieldString,
            false,
            $fromDate,
            $toDate
        ) : 0;
        $totalCount = $totalToProcess = $totalToCreate + $totalToUpdate;

        if (defined('IN_MAUTIC_CONSOLE')) {
            // start with update
            if ($totalToUpdate + $totalToCreate) {
                $output = new ConsoleOutput();
                $output->writeln("About $totalToUpdate to update and about $totalToCreate to create/update");
                $progress = new ProgressBar($output, $totalCount);
            }
        }

        // Start with contacts so we know who is a contact when we go to process converted leads
        if (count($supportedObjects) > 1) {
            $sfObject = 'Contact';
        } else {
            // Only Lead or Contact is enabled so start with which ever that is
            reset($supportedObjects);
            $sfObject = key($supportedObjects);
        }
        $noMoreUpdates   = false;
        $trackedContacts = [
            'Contact' => [],
            'Lead'    => [],
        ];

        // Loop to maximize composite that may include updating contacts, updating leads, and creating leads
        while ($totalCount > 0) {
            $limit           = $originalLimit;
            $mauticData      = [];
            $checkEmailsInSF = [];
            $leadsToSync     = [];
            $processedLeads  = [];

            // Process the updates
            if (!$noMoreUpdates) {
                $noMoreUpdates = $this->getMauticContactsToUpdate(
                    $checkEmailsInSF,
                    $mauticLeadFieldString,
                    $sfObject,
                    $trackedContacts,
                    $limit,
                    $fromDate,
                    $toDate,
                    $totalCount
                );

                if ($noMoreUpdates && 'Contact' === $sfObject && isset($supportedObjects['Lead'])) {
                    // Try Leads
                    $sfObject      = 'Lead';
                    $noMoreUpdates = $this->getMauticContactsToUpdate(
                        $checkEmailsInSF,
                        $mauticLeadFieldString,
                        $sfObject,
                        $trackedContacts,
                        $limit,
                        $fromDate,
                        $toDate,
                        $totalCount
                    );
                }

                if ($limit) {
                    // Mainly done for test mocking purposes
                    $limit = $this->getSalesforceSyncLimit($checkEmailsInSF, $limit);
                }
            }

            // If there is still room - grab Mautic leads to create if the Lead object is enabled
            $sfEntityRecords = [];
            if ('Lead' === $sfObject && (null === $limit || $limit > 0) && !empty($mauticLeadFieldString)) {
                try {
                    $sfEntityRecords = $this->getMauticContactsToCreate(
                        $checkEmailsInSF,
                        $fieldMapping,
                        $mauticLeadFieldString,
                        $limit,
                        $fromDate,
                        $toDate,
                        $totalCount,
                        $progress
                    );
                } catch (ApiErrorException $exception) {
                    $this->cleanupFromSync($leadsToSync, $exception);
                }
            } elseif ($checkEmailsInSF) {
                $sfEntityRecords = $this->getSalesforceObjectsByEmails($sfObject, $checkEmailsInSF, implode(',', array_keys($fieldMapping[$sfObject]['create'])));

                if (!isset($sfEntityRecords['records'])) {
                    // Something is wrong so throw an exception to prevent creating a bunch of new leads
                    $this->cleanupFromSync(
                        $leadsToSync,
                        json_encode($sfEntityRecords)
                    );
                }
            }

            // We're done
            if (!$checkEmailsInSF) {
                break;
            }

            $this->prepareMauticContactsToUpdate(
                $mauticData,
                $checkEmailsInSF,
                $processedLeads,
                $trackedContacts,
                $leadsToSync,
                $fieldMapping,
                $mauticLeadFieldString,
                $sfEntityRecords,
                $progress
            );

            // Only create left over if Lead object is enabled in integration settings
            if ($checkEmailsInSF && isset($fieldMapping['Lead'])) {
                $this->prepareMauticContactsToCreate(
                    $mauticData,
                    $checkEmailsInSF,
                    $processedLeads,
                    $fieldMapping
                );
            }

            // Persist pending changes
            $this->cleanupFromSync($leadsToSync);
            // Make the request
            $this->makeCompositeRequest($mauticData, $totalUpdated, $totalCreated, $totalErrors);

            // Stop gap - if 100% let's kill the script
            if ($progress && $progress->getProgressPercent() >= 1) {
                break;
            }
        }

        if ($progress) {
            $progress->finish();
            $output->writeln('');
        }

        $this->logger->debug('SALESFORCE: '.$this->getApiHelper()->getRequestCounter().' API requests made for pushLeads');

        // Assume that those not touched are ignored due to not having matching fields, duplicates, etc
        $totalIgnored = $totalToProcess - ($totalUpdated + $totalCreated + $totalErrors);

        return [$totalUpdated, $totalCreated, $totalErrors, $totalIgnored];
    }

    /**
     * @param $lead
     *
     * @return array
     */
    public function getSalesforceLeadId($lead)
    {
        $config                = $this->mergeConfigToFeatureSettings([]);
        $integrationEntityRepo = $this->getIntegrationEntityRepository();

        if (isset($config['objects'])) {
            //try searching for lead as this has been changed before in updated done to the plugin
            if (false !== array_search('Contact', $config['objects'])) {
                $resultContact = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', 'Contact', 'lead', $lead->getId());

                if ($resultContact) {
                    return $resultContact;
                }
            }
        }
        $resultLead = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', 'Lead', 'lead', $lead->getId());

        return $resultLead;
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    public function getCampaigns()
    {
        $campaigns = [];
        try {
            $campaigns = $this->getApiHelper()->getCampaigns();
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $campaigns;
    }

    /**
     * @param $campaignId
     * @param $settings
     *
     * @throws \Exception
     */
    public function getCampaignMembers($campaignId, $settings)
    {
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        $persistEntities   = $contactList   = $leadList   = $existingLeads   = $existingContacts   = [];

        try {
            $campaignsMembersResults = $this->getApiHelper()->getCampaignMembers($campaignId);
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
            if (!$silenceExceptions) {
                throw $e;
            }
        }

        //prepare contacts to import to mautic contacts to delete from mautic
        if (isset($campaignsMembersResults['records']) && !empty($campaignsMembersResults['records'])) {
            foreach ($campaignsMembersResults['records'] as $campaignMember) {
                $contactType = !empty($campaignMember['LeadId']) ? 'Lead' : 'Contact';
                $contactId   = !empty($campaignMember['LeadId']) ? $campaignMember['LeadId'] : $campaignMember['ContactId'];
                $isDeleted   = ($campaignMember['IsDeleted']) ? true : false;
                if ($contactType == 'Lead') {
                    $leadList[$contactId] = [
                        'type'       => $contactType,
                        'id'         => $contactId,
                        'campaignId' => $campaignMember['CampaignId'],
                        'isDeleted'  => $isDeleted,
                    ];
                }
                if ($contactType == 'Contact') {
                    $contactList[$contactId] = [
                        'type'       => $contactType,
                        'id'         => $contactId,
                        'campaignId' => $campaignMember['CampaignId'],
                        'isDeleted'  => $isDeleted,
                    ];
                }
            }

            /** @var IntegrationEntityRepository $integrationEntityRepo */
            $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
            //update lead/contact records
            $listOfLeads = implode('", "', array_keys($leadList));
            $listOfLeads = '"'.$listOfLeads.'"';
            $leads       = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', 'Lead', 'lead', null, null, null, false, 0, 0, $listOfLeads);

            $listOfContacts = implode('", "', array_keys($contactList));
            $listOfContacts = '"'.$listOfContacts.'"';
            $contacts       = $integrationEntityRepo->getIntegrationsEntityId(
                'Salesforce',
                'Contact',
                'lead',
                null,
                null,
                null,
                false,
                0,
                0,
                $listOfContacts
            );

            if (!empty($leads)) {
                $existingLeads = array_map(
                    function ($lead) {
                        if (($lead['integration_entity'] == 'Lead')) {
                            return $lead['integration_entity_id'];
                        }
                    },
                    $leads
                );
            }
            if (!empty($contacts)) {
                $existingContacts = array_map(
                    function ($lead) {
                        return ($lead['integration_entity'] == 'Contact') ? $lead['integration_entity_id'] : [];
                    },
                    $contacts
                );
            }
            //record campaigns in integration entity for segment to process
            $allCampaignMembers = array_merge(array_values($existingLeads), array_values($existingContacts));
            //Leads
            $leadsToFetch = array_diff_key($leadList, $existingLeads);
            $mixedFields  = $this->getIntegrationSettings()->getFeatureSettings();
            $executed     = 0;
            if (!empty($leadsToFetch)) {
                $listOfLeadsToFetch = implode("','", array_keys($leadsToFetch));
                $listOfLeadsToFetch = "'".$listOfLeadsToFetch."'";

                $fields    = $this->getMixedLeadFields($mixedFields, 'Lead');
                $fields[]  = 'Id';
                $fields    = implode(', ', array_unique($fields));
                $leadQuery = 'SELECT '.$fields.' from Lead where Id in ('.$listOfLeadsToFetch.') and ConvertedContactId = NULL';

                $this->getLeads([], $leadQuery, $executed, [], 'Lead');

                $allCampaignMembers = array_merge($allCampaignMembers, array_keys($leadsToFetch));
            }
            //Contacts
            $contactsToFetch = array_diff_key($contactList, $existingContacts);
            if (!empty($contactsToFetch)) {
                $listOfContactsToFetch = implode("','", array_keys($contactsToFetch));
                $listOfContactsToFetch = "'".$listOfContactsToFetch."'";
                $fields                = $this->getMixedLeadFields($mixedFields, 'Contact');
                $fields[]              = 'Id';
                $fields                = implode(', ', array_unique($fields));
                $contactQuery          = 'SELECT '.$fields.' from Contact where Id in ('.$listOfContactsToFetch.')';
                $this->getLeads([], $contactQuery, $executed, [], 'Contact');
                $allCampaignMembers = array_merge($allCampaignMembers, array_keys($contactsToFetch));
            }
            if (!empty($allCampaignMembers)) {
                $internalLeadIds = implode('", "', $allCampaignMembers);
                $internalLeadIds = '"'.$internalLeadIds.'"';
                $leads           = $integrationEntityRepo->getIntegrationsEntityId(
                    'Salesforce',
                    null,
                    'lead',
                    null,
                    null,
                    null,
                    false,
                    0,
                    0,
                    $internalLeadIds
                );
                //first find existing campaign members.
                foreach ($leads as $campaignMember) {
                    $existingCampaignMember = $integrationEntityRepo->getIntegrationsEntityId(
                        'Salesforce',
                        'CampaignMember',
                        'lead',
                        $campaignMember['internal_entity_id']
                    );
                    if (empty($existingCampaignMember)) {
                        $persistEntities[] = $this->createIntegrationEntity(
                            'CampaignMember',
                            $campaignId,
                            'lead',
                            $campaignMember['internal_entity_id'],
                            [],
                            false
                        );
                    }
                }

                if ($persistEntities) {
                    $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($persistEntities);
                    unset($persistEntities);
                    $this->em->clear(IntegrationEntity::class);
                }
            }
        }
    }

    /**
     * @param $fields
     * @param $object
     *
     * @return array
     */
    public function getMixedLeadFields($fields, $object)
    {
        $mixedFields = array_filter($fields['leadFields']);
        $fields      = [];
        foreach ($mixedFields as $sfField => $mField) {
            if (strpos($sfField, '__'.$object) !== false) {
                $fields[] = str_replace('__'.$object, '', $sfField);
            }
            if (strpos($sfField, '-'.$object) !== false) {
                $fields[] = str_replace('-'.$object, '', $sfField);
            }
        }

        return $fields;
    }

    /**
     * @param $campaignId
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getCampaignMemberStatus($campaignId)
    {
        $campaignMemberStatus = [];
        try {
            $campaignMemberStatus = $this->getApiHelper()->getCampaignMemberStatus($campaignId);
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $campaignMemberStatus;
    }

    /**
     * @param Lead $lead
     * @param      $campaignId
     * @param      $status
     *
     * @return array
     */
    public function pushLeadToCampaign(Lead $lead, $campaignId, $status = '', $personIds = null)
    {
        if (empty($personIds)) {
            // personIds should have been generated by pushLead()

            return false;
        }

        $mauticData = [];
        $objectId   = null;

        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');

        $body = [
            'Status' => $status,
        ];
        $object = 'CampaignMember';
        $url    = '/services/data/v38.0/sobjects/'.$object;

        if (!empty($lead->getEmail())) {
            $pushPeople = [];
            $pushObject = null;
            if (!empty($personIds)) {
                // Give precendence to Contact CampaignMembers
                if (!empty($personIds['Contact'])) {
                    $pushObject      = 'Contact';
                    $campaignMembers = $this->getApiHelper()->checkCampaignMembership($campaignId, $pushObject, $personIds[$pushObject]);
                    $pushPeople      = $personIds[$pushObject];
                }

                if (empty($campaignMembers) && !empty($personIds['Lead'])) {
                    $pushObject      = 'Lead';
                    $campaignMembers = $this->getApiHelper()->checkCampaignMembership($campaignId, $pushObject, $personIds[$pushObject]);
                    $pushPeople      = $personIds[$pushObject];
                }
            } // pushLead should have handled this

            foreach ($pushPeople as $memberId) {
                $campaignMappingId = '-'.$campaignId;

                if (isset($campaignMembers[$memberId])) {
                    $existingCampaignMember = $integrationEntityRepo->getIntegrationsEntityId(
                        'Salesforce',
                        'CampaignMember',
                        'lead',
                        null,
                        null,
                        null,
                        false,
                        0,
                        0,
                        "'".$campaignMembers[$memberId]."'"
                    );
                    if ($existingCampaignMember) {
                        foreach ($existingCampaignMember as $member) {
                            $integrationEntity = $integrationEntityRepo->getEntity($member['id']);
                            $referenceId       = $integrationEntity->getId();
                            $internalLeadId    = $integrationEntity->getInternalEntityId();
                        }
                    }
                    $id = !empty($lead->getId()) ? $lead->getId() : '';
                    $id .= '-CampaignMember'.$campaignMembers[$memberId];
                    $id .= !empty($referenceId) ? '-'.$referenceId : '';
                    $id .= $campaignMappingId;
                    $patchurl        = $url.'/'.$campaignMembers[$memberId];
                    $mauticData[$id] = [
                        'method'      => 'PATCH',
                        'url'         => $patchurl,
                        'referenceId' => $id,
                        'body'        => $body,
                        'httpHeaders' => [
                            'Sforce-Auto-Assign' => 'FALSE',
                        ],
                    ];
                } else {
                    $id              = (!empty($lead->getId()) ? $lead->getId() : '').'-CampaignMemberNew-null'.$campaignMappingId;
                    $mauticData[$id] = [
                        'method'      => 'POST',
                        'url'         => $url,
                        'referenceId' => $id,
                        'body'        => array_merge(
                            $body,
                            [
                                'CampaignId'      => $campaignId,
                                "{$pushObject}Id" => $memberId,
                            ]
                        ),
                    ];
                }
            }

            $request['allOrNone']        = 'false';
            $request['compositeRequest'] = array_values($mauticData);

            $this->logger->debug('SALESFORCE: pushLeadToCampaign '.var_export($request, true));

            if (!empty($request)) {
                $result = $this->getApiHelper()->syncMauticToSalesforce($request);

                return (bool) array_sum($this->processCompositeResponse($result['compositeResponse']));
            }
        }

        return false;
    }

    /**
     * @param $email
     *
     * @return mixed|string
     */
    protected function getSyncKey($email)
    {
        return mb_strtolower($this->cleanPushData($email));
    }

    /**
     * @param $checkEmailsInSF
     * @param $mauticLeadFieldString
     * @param $sfObject
     * @param $trackedContacts
     * @param $limit
     * @param $fromDate
     * @param $toDate
     * @param $totalCount
     *
     * @return bool
     */
    protected function getMauticContactsToUpdate(
        &$checkEmailsInSF,
        $mauticLeadFieldString,
        &$sfObject,
        &$trackedContacts,
        $limit,
        $fromDate,
        $toDate,
        &$totalCount
    ) {
        // Fetch them separately so we can determine if Leads are already Contacts
        $toUpdate = $this->getIntegrationEntityRepository()->findLeadsToUpdate(
            'Salesforce',
            'lead',
            $mauticLeadFieldString,
            $limit,
            $fromDate,
            $toDate,
            $sfObject
        )[$sfObject];

        $toUpdateCount = count($toUpdate);
        $totalCount -= $toUpdateCount;

        foreach ($toUpdate as $lead) {
            if (!empty($lead['email'])) {
                $lead                                               = $this->getCompoundMauticFields($lead);
                $key                                                = $this->getSyncKey($lead['email']);
                $trackedContacts[$lead['integration_entity']][$key] = $lead['id'];

                if ('Contact' == $sfObject) {
                    $this->setContactToSync($checkEmailsInSF, $lead);
                } elseif (isset($trackedContacts['Contact'][$key])) {
                    // We already know this is a converted contact so just ignore it
                    $integrationEntity = $this->em->getReference(
                        'MauticPluginBundle:IntegrationEntity',
                        $lead['id']
                    );
                    $this->deleteIntegrationEntities[] = $integrationEntity;
                    $this->logger->debug('SALESFORCE: Converted lead '.$lead['email']);
                } else {
                    $this->setContactToSync($checkEmailsInSF, $lead);
                }
            }
        }

        return 0 === $toUpdateCount;
    }

    /**
     * @param      $checkEmailsInSF
     * @param      $fieldMapping
     * @param      $mauticLeadFieldString
     * @param      $limit
     * @param      $fromDate
     * @param      $toDate
     * @param      $totalCount
     * @param null $progress
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    protected function getMauticContactsToCreate(
        &$checkEmailsInSF,
        $fieldMapping,
        $mauticLeadFieldString,
        $limit,
        $fromDate,
        $toDate,
        &$totalCount,
        $progress = null
    ) {
        $integrationEntityRepo = $this->getIntegrationEntityRepository();
        $leadsToCreate         = $integrationEntityRepo->findLeadsToCreate(
            'Salesforce',
            $mauticLeadFieldString,
            $limit,
            $fromDate,
            $toDate
        );
        $totalCount -= count($leadsToCreate);
        $foundContacts   = [];
        $sfEntityRecords = [
            'totalSize' => 0,
            'records'   => [],
        ];
        $error = false;

        foreach ($leadsToCreate as $lead) {
            $lead = $this->getCompoundMauticFields($lead);

            if (isset($lead['email'])) {
                $this->setContactToSync($checkEmailsInSF, $lead);
            } elseif ($progress) {
                $progress->advance();
            }
        }

        // When creating, we have to check for Contacts first then Lead
        if (isset($fieldMapping['Contact'])) {
            $sfEntityRecords = $this->getSalesforceObjectsByEmails('Contact', $checkEmailsInSF, implode(',', array_keys($fieldMapping['Contact']['create'])));
            if (isset($sfEntityRecords['records'])) {
                foreach ($sfEntityRecords['records'] as $sfContactRecord) {
                    if (!isset($sfContactRecord['Email'])) {
                        continue;
                    }
                    $key                 = $this->getSyncKey($sfContactRecord['Email']);
                    $foundContacts[$key] = $key;
                }
            } else {
                $error = json_encode($sfEntityRecords);
            }
        }

        // For any Mautic contacts left over, check to see if existing Leads exist
        if (isset($fieldMapping['Lead']) && $checkSfLeads = array_diff_key($checkEmailsInSF, $foundContacts)) {
            $sfLeadRecords = $this->getSalesforceObjectsByEmails('Lead', $checkSfLeads, implode(',', array_keys($fieldMapping['Lead']['create'])));

            if (isset($sfLeadRecords['records'])) {
                // Merge contact records with these
                $sfEntityRecords['records']   = array_merge($sfEntityRecords['records'], $sfLeadRecords['records']);
                $sfEntityRecords['totalSize'] = (int) $sfEntityRecords['totalSize'] + (int) $sfLeadRecords['totalSize'];
            } else {
                $error = json_encode($sfLeadRecords);
            }
        }

        if ($error) {
            throw new ApiErrorException($error);
        }

        unset($leadsToCreate, $checkSfLeads);

        return $sfEntityRecords;
    }

    /**
     * @param      $mauticData
     * @param      $objectFields
     * @param      $object
     * @param      $lead
     * @param null $objectId
     * @param null $sfRecord
     *
     * @return array
     */
    protected function buildCompositeBody(
        &$mauticData,
        $objectFields,
        $object,
        &$lead,
        $objectId = null,
        $sfRecord = null
    ) {
        $body       = [];
        $updateLead = [];
        $config     = $this->mergeConfigToFeatureSettings([]);

        if (isset($lead['email']) && !empty($lead['email'])) {
            //use a composite patch here that can update and create (one query) every 200 records
            if (isset($objectFields['update'])) {
                $fields = ($objectId) ? $objectFields['update'] : $objectFields['create'];
                $fields = $this->getBlankFieldsToUpdate($fields, $sfRecord, $objectFields, $config);
            } else {
                $fields = $objectFields;
            }

            foreach ($fields as $sfField => $mauticField) {
                if (isset($lead[$mauticField])) {
                    $fieldType      = (isset($objectFields['types']) && isset($objectFields['types'][$sfField])) ? $objectFields['types'][$sfField] : 'string';
                    $body[$sfField] = $this->cleanPushData($lead[$mauticField], $fieldType);
                }
                if (array_key_exists($sfField, $objectFields['required']['fields']) && empty($body[$sfField])) {
                    if (isset($sfRecord[$sfField])) {
                        $body[$sfField] = $sfRecord[$sfField];
                        if (empty($lead[$mauticField]) && !empty($sfRecord[$sfField])
                            && $sfRecord[$sfField] !== $this->translator->trans(
                                'mautic.integration.form.lead.unknown'
                            )
                        ) {
                            $updateLead[$mauticField] = $sfRecord[$sfField];
                        }
                    } else {
                        $body[$sfField] = $this->translator->trans('mautic.integration.form.lead.unknown');
                    }
                }
            }

            if (!empty($body)) {
                $url = '/services/data/v38.0/sobjects/'.$object;
                if ($objectId) {
                    $url .= '/'.$objectId;
                }
                $id              = $lead['internal_entity_id'].'-'.$object.(!empty($lead['id']) ? '-'.$lead['id'] : '');
                $method          = ($objectId) ? 'PATCH' : 'POST';
                $mauticData[$id] = [
                    'method'      => $method,
                    'url'         => $url,
                    'referenceId' => $id,
                    'body'        => $body,
                    'httpHeaders' => [
                        'Sforce-Auto-Assign' => ($objectId) ? 'FALSE' : 'TRUE',
                    ],
                ];
                $this->logger->debug('SALESFORCE: Composite '.$method.' subrequest: '.$lead['email']);
            }
        }

        return $updateLead;
    }

    /**
     * @param array $config
     * @param array $availableFields
     * @param       $object
     *
     * @return array
     */
    protected function getRequiredFieldString(array $config, array $availableFields, $object)
    {
        $requiredFields = $this->getRequiredFields($availableFields[$object]);
        $requiredFields = $this->prepareFieldsForSync($config['leadFields'], array_keys($requiredFields), $object);
        $requiredString = implode(',', array_keys($requiredFields));

        return [$requiredFields, $requiredString];
    }

    /**
     * @param $config
     *
     * @return array
     */
    protected function prepareFieldsForPush($config)
    {
        $leadFields = array_unique(array_values($config['leadFields']));
        $leadFields = array_combine($leadFields, $leadFields);
        unset($leadFields['mauticContactTimelineLink']);
        unset($leadFields['mauticContactIsContactableByEmail']);

        $fieldsToUpdateInSf = $this->getPriorityFieldsForIntegration($config);
        $fieldKeys          = array_keys($config['leadFields']);
        $supportedObjects   = [];
        $objectFields       = [];

        // Important to have contacts first!!
        if (false !== array_search('Contact', $config['objects'])) {
            $supportedObjects['Contact'] = 'Contact';
            $fieldsToCreate              = $this->prepareFieldsForSync($config['leadFields'], $fieldKeys, 'Contact');
            $objectFields['Contact']     = [
                'update' => isset($fieldsToUpdateInSf['Contact']) ? array_intersect_key($fieldsToCreate, $fieldsToUpdateInSf['Contact']) : [],
                'create' => $fieldsToCreate,
            ];
        }
        if (false !== array_search('Lead', $config['objects'])) {
            $supportedObjects['Lead'] = 'Lead';
            $fieldsToCreate           = $this->prepareFieldsForSync($config['leadFields'], $fieldKeys, 'Lead');
            $objectFields['Lead']     = [
                'update' => isset($fieldsToUpdateInSf['Lead']) ? array_intersect_key($fieldsToCreate, $fieldsToUpdateInSf['Lead']) : [],
                'create' => $fieldsToCreate,
            ];
        }

        $mauticLeadFieldString = implode(', l.', $leadFields);
        $mauticLeadFieldString = 'l.'.$mauticLeadFieldString;
        $availableFields       = $this->getAvailableLeadFields(['feature_settings' => ['objects' => $supportedObjects]]);

        // Setup required fields and field types
        foreach ($supportedObjects as $object) {
            $objectFields[$object]['types'] = [];
            if (isset($availableFields[$object])) {
                $fieldData = $this->prepareFieldsForSync($availableFields[$object], array_keys($availableFields[$object]), $object);
                foreach ($fieldData as $fieldName => $field) {
                    $objectFields[$object]['types'][$fieldName] = (isset($field['type'])) ? $field['type'] : 'string';
                }
            }

            list($fields, $string) = $this->getRequiredFieldString(
                $config,
                $availableFields,
                $object
            );

            $objectFields[$object]['required'] = [
                'fields' => $fields,
                'string' => $string,
            ];
        }

        return [$objectFields, $mauticLeadFieldString, $supportedObjects];
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
        $fields = parent::getPriorityFieldsForMautic($config, $object, $priorityObject);

        return ($object && isset($fields[$object])) ? $fields[$object] : $fields;
    }

    /**
     * @param        $config
     * @param null   $object
     * @param string $priorityObject
     *
     * @return mixed
     */
    protected function getPriorityFieldsForIntegration($config, $object = null, $priorityObject = 'mautic')
    {
        $fields = parent::getPriorityFieldsForIntegration($config, $object, $priorityObject);
        unset($fields['Contact']['Id'], $fields['Lead']['Id']);

        return ($object && isset($fields[$object])) ? $fields[$object] : $fields;
    }

    /**
     * @param     $response
     * @param int $totalUpdated
     * @param int $totalCreated
     * @param int $totalErrored
     *
     * @return array
     */
    protected function processCompositeResponse($response, &$totalUpdated = 0, &$totalCreated = 0, &$totalErrored = 0)
    {
        if (is_array($response)) {
            foreach ($response as $item) {
                $contactId = $integrationEntityId = $campaignId = null;
                $object    = 'Lead';
                if (!empty($item['referenceId'])) {
                    $reference = explode('-', $item['referenceId']);
                    if (3 === count($reference)) {
                        list($contactId, $object, $integrationEntityId) = $reference;
                    } elseif (4 === count($reference)) {
                        list($contactId, $object, $integrationEntityId, $campaignId) = $reference;
                    } else {
                        list($contactId, $object) = $reference;
                    }
                }
                if (strstr($object, 'CampaignMember')) {
                    $object = 'CampaignMember';
                }
                if (isset($item['body'][0]['errorCode'])) {
                    $exception = new ApiErrorException($item['body'][0]['message']);
                    if ($object == 'Contact' || $object = 'Lead') {
                        $exception->setContactId($contactId);
                    }
                    $this->logIntegrationError($exception);
                    $integrationEntity = null;
                    if ($integrationEntityId && $object !== 'CampaignMember') {
                        $integrationEntity = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $integrationEntityId);
                        $integrationEntity->setLastSyncDate(new \DateTime());
                    } elseif (isset($campaignId) && $campaignId != null) {
                        $integrationEntity = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $campaignId);
                        $integrationEntity->setLastSyncDate($this->getLastSyncDate());
                    } elseif ($contactId) {
                        $integrationEntity = $this->createIntegrationEntity(
                            $object,
                            null,
                            'lead-error',
                            $contactId,
                            null,
                            false
                        );
                    }

                    if ($integrationEntity) {
                        $integrationEntity->setInternalEntity('ENTITY_IS_DELETED' === $item['body'][0]['errorCode'] ? 'lead-deleted' : 'lead-error')
                            ->setInternal(['error' => $item['body'][0]['message']]);
                        $this->persistIntegrationEntities[] = $integrationEntity;
                    }
                    ++$totalErrored;
                } elseif (!empty($item['body']['success'])) {
                    if (201 === $item['httpStatusCode']) {
                        // New object created
                        if ($object === 'CampaignMember') {
                            $internal = ['Id' => $item['body']['id']];
                        } else {
                            $internal = [];
                        }
                        $this->salesforceIdMapping[$contactId] = $item['body']['id'];
                        $this->persistIntegrationEntities[]    = $this->createIntegrationEntity(
                            $object,
                            $this->salesforceIdMapping[$contactId],
                            'lead',
                            $contactId,
                            $internal,
                            false
                        );
                    }
                    ++$totalCreated;
                } elseif (204 === $item['httpStatusCode']) {
                    // Record was updated
                    if ($integrationEntityId) {
                        /** @var IntegrationEntity $integrationEntity */
                        $integrationEntity = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $integrationEntityId);

                        $integrationEntity->setLastSyncDate($this->getLastSyncDate());
                        if (isset($this->salesforceIdMapping[$contactId])) {
                            $integrationEntity->setIntegrationEntityId($this->salesforceIdMapping[$contactId]);
                        }

                        $this->persistIntegrationEntities[] = $integrationEntity;
                    } elseif (!empty($this->salesforceIdMapping[$contactId])) {
                        // Found in Salesforce so create a new record for it
                        $this->persistIntegrationEntities[] = $this->createIntegrationEntity(
                            $object,
                            $this->salesforceIdMapping[$contactId],
                            'lead',
                            $contactId,
                            [],
                            false
                        );
                    }

                    ++$totalUpdated;
                } else {
                    $error = 'http status code '.$item['httpStatusCode'];
                    switch (true) {
                        case !empty($item['body'][0]['message']['message']):
                            $error = $item['body'][0]['message']['message'];
                            break;
                        case !empty($item['body']['message']):
                            $error = $item['body']['message'];
                            break;
                    }

                    $exception = new ApiErrorException($error);
                    if (!empty($item['referenceId']) && ($object == 'Contact' || $object = 'Lead')) {
                        $exception->setContactId($item['referenceId']);
                    }
                    $this->logIntegrationError($exception);
                    ++$totalErrored;

                    if ($integrationEntityId) {
                        /** @var IntegrationEntity $integrationEntity */
                        $integrationEntity = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $integrationEntityId);

                        $integrationEntity->setLastSyncDate($this->getLastSyncDate());
                        if (isset($this->salesforceIdMapping[$contactId])) {
                            $integrationEntity->setIntegrationEntityId($this->salesforceIdMapping[$contactId]);
                        }

                        $this->persistIntegrationEntities[] = $integrationEntity;
                    } elseif (!empty($this->salesforceIdMapping[$contactId])) {
                        // Found in Salesforce so create a new record for it
                        $this->persistIntegrationEntities[] = $this->createIntegrationEntity(
                            $object,
                            $this->salesforceIdMapping[$contactId],
                            'lead',
                            $contactId,
                            [],
                            false
                        );
                    }
                }
            }
        }

        $this->cleanupFromSync();

        return [$totalUpdated, $totalCreated];
    }

    /**
     * @param $sfObject
     * @param $checkEmailsInSF
     * @param $requiredFieldString
     *
     * @return array
     */
    protected function getSalesforceObjectsByEmails($sfObject, $checkEmailsInSF, $requiredFieldString)
    {
        // Salesforce craps out with double quotes and unescaped single quotes
        $findEmailsInSF = array_map(
            function ($lead) {
                return str_replace("'", "\'", $this->cleanPushData($lead['email']));
            },
            $checkEmailsInSF
        );

        $fieldString = "'".implode("','", $findEmailsInSF)."'";
        $queryUrl    = $this->getQueryUrl();
        $findQuery   = ('Lead' === $sfObject)
            ?
            'select Id, '.$requiredFieldString.', ConvertedContactId from Lead where isDeleted = false and Email in ('.$fieldString.')'
            :
            'select Id, '.$requiredFieldString.' from Contact where isDeleted = false and Email in ('.$fieldString.')';

        return $this->getApiHelper()->request('query', ['q' => $findQuery], 'GET', false, null, $queryUrl);
    }

    /**
     * @param      $mauticData
     * @param      $checkEmailsInSF
     * @param      $processedLeads
     * @param      $trackedContacts
     * @param      $leadsToSync
     * @param      $requiredFields
     * @param      $objectFields
     * @param      $mauticLeadFieldString
     * @param      $sfEntityRecords
     * @param null $progress
     */
    protected function prepareMauticContactsToUpdate(
        &$mauticData,
        &$checkEmailsInSF,
        &$processedLeads,
        &$trackedContacts,
        &$leadsToSync,
        $objectFields,
        $mauticLeadFieldString,
        $sfEntityRecords,
        $progress = null
    ) {
        foreach ($sfEntityRecords['records'] as $sfKey => $sfEntityRecord) {
            $skipObject = false;
            $syncLead   = false;
            $sfObject   = $sfEntityRecord['attributes']['type'];
            if (!isset($sfEntityRecord['Email'])) {
                // This is a record we don't recognize so continue
                return;
            }
            $key = $this->getSyncKey($sfEntityRecord['Email']);
            if (!isset($sfEntityRecord['Id']) || (!isset($checkEmailsInSF[$key]) && !isset($processedLeads[$key]))) {
                // This is a record we don't recognize so continue
                return;
            }

            $leadData  = (isset($processedLeads[$key])) ? $processedLeads[$key] : $checkEmailsInSF[$key];
            $contactId = $leadData['internal_entity_id'];

            if (
                isset($checkEmailsInSF[$key])
                && (
                    (
                        'Lead' === $sfObject && !empty($sfEntityRecord['ConvertedContactId'])
                    )
                    || (
                        isset($checkEmailsInSF[$key]['integration_entity']) && 'Contact' === $sfObject
                        && 'Lead' === $checkEmailsInSF[$key]['integration_entity']
                    )
                )
            ) {
                $deleted = false;
                // This is a converted lead so remove the Lead entity leaving the Contact entity
                if (!empty($trackedContacts['Lead'][$key])) {
                    $this->deleteIntegrationEntities[] = $this->em->getReference(
                        'MauticPluginBundle:IntegrationEntity',
                        $trackedContacts['Lead'][$key]
                    );
                    $deleted = true;
                    unset($trackedContacts['Lead'][$key]);
                }

                if ($contactEntity = $this->checkLeadIsContact($trackedContacts['Contact'], $key, $contactId, $mauticLeadFieldString)) {
                    // This Lead is already a Contact but was not updated for whatever reason
                    if (!$deleted) {
                        $this->deleteIntegrationEntities[] = $this->em->getReference(
                            'MauticPluginBundle:IntegrationEntity',
                            $checkEmailsInSF[$key]['id']
                        );
                    }

                    // Update the Contact record instead
                    $checkEmailsInSF[$key]            = $contactEntity;
                    $trackedContacts['Contact'][$key] = $contactEntity['id'];
                } else {
                    $id = (!empty($sfEntityRecord['ConvertedContactId'])) ? $sfEntityRecord['ConvertedContactId'] : $sfEntityRecord['Id'];
                    // This contact does not have a Contact record
                    $integrationEntity = $this->createIntegrationEntity(
                        'Contact',
                        $id,
                        'lead',
                        $contactId
                    );

                    $checkEmailsInSF[$key]['integration_entity']    = 'Contact';
                    $checkEmailsInSF[$key]['integration_entity_id'] = $id;
                    $checkEmailsInSF[$key]['id']                    = $integrationEntity;
                }

                $this->logger->debug('SALESFORCE: Converted lead '.$sfEntityRecord['Email']);

                // skip if this is a Lead object since it'll be handled with the Contact entry
                if ('Lead' === $sfObject) {
                    unset($checkEmailsInSF[$key]);
                    unset($sfEntityRecords['records'][$sfKey]);
                    $skipObject = true;
                }
            }

            if (!$skipObject) {
                // Only progress if we have a unique Lead and not updating a Salesforce entry duplicate
                if (!isset($processedLeads[$key])) {
                    if ($progress) {
                        $progress->advance();
                    }

                    // Mark that this lead has been processed
                    $leadData = $processedLeads[$key] = $checkEmailsInSF[$key];
                }

                // Keep track of Mautic ID to Salesforce ID for the integration table
                $this->salesforceIdMapping[$contactId] = (!empty($sfEntityRecord['ConvertedContactId'])) ? $sfEntityRecord['ConvertedContactId']
                    : $sfEntityRecord['Id'];

                $leadEntity = $this->em->getReference('MauticLeadBundle:Lead', $leadData['internal_entity_id']);
                if ($updateLead = $this->buildCompositeBody(
                    $mauticData,
                    $objectFields[$sfObject],
                    $sfObject,
                    $leadData,
                    $sfEntityRecord['Id'],
                    $sfEntityRecord
                )
                ) {
                    // Get the lead entity
                    /* @var Lead $leadEntity */
                    foreach ($updateLead as $mauticField => $sfValue) {
                        $leadEntity->addUpdatedField($mauticField, $sfValue);
                    }

                    $syncLead = !empty($leadEntity->getChanges(true));
                }

                // Validate if we have a company for this Mautic contact
                if (!empty($sfEntityRecord['Company'])
                    && $sfEntityRecord['Company'] !== $this->translator->trans(
                        'mautic.integration.form.lead.unknown'
                    )
                ) {
                    $company = IdentifyCompanyHelper::identifyLeadsCompany(
                        ['company' => $sfEntityRecord['Company']],
                        null,
                        $this->companyModel
                    );

                    if (!empty($company[2])) {
                        $syncLead = $this->companyModel->addLeadToCompany($company[2], $leadEntity);
                        $this->em->detach($company[2]);
                    }
                }

                if ($syncLead) {
                    $leadsToSync[] = $leadEntity;
                } else {
                    $this->em->detach($leadEntity);
                }
            }

            unset($checkEmailsInSF[$key]);
        }
    }

    /**
     * @param $mauticData
     * @param $checkEmailsInSF
     * @param $processedLeads
     * @param $objectFields
     */
    protected function prepareMauticContactsToCreate(
        &$mauticData,
        &$checkEmailsInSF,
        &$processedLeads,
        $objectFields
    ) {
        foreach ($checkEmailsInSF as $key => $lead) {
            if (!empty($lead['integration_entity_id'])) {
                if ($this->buildCompositeBody(
                    $mauticData,
                    $objectFields[$lead['integration_entity']],
                    $lead['integration_entity'],
                    $lead,
                    $lead['integration_entity_id']
                )
                ) {
                    $this->logger->debug('SALESFORCE: Contact has existing ID so updating '.$lead['email']);
                }
            } else {
                $this->buildCompositeBody(
                    $mauticData,
                    $objectFields['Lead'],
                    'Lead',
                    $lead
                );
            }

            $processedLeads[$key] = $checkEmailsInSF[$key];
            unset($checkEmailsInSF[$key]);
        }
    }

    /**
     * @param     $mauticData
     * @param int $totalUpdated
     * @param int $totalCreated
     * @param int $totalErrored
     */
    protected function makeCompositeRequest($mauticData, &$totalUpdated = 0, &$totalCreated = 0, &$totalErrored = 0)
    {
        if (empty($mauticData)) {
            return;
        }

        /** @var SalesforceApi $apiHelper */
        $apiHelper = $this->getApiHelper();

        // We can only send 25 at a time
        $request              = [];
        $request['allOrNone'] = 'false';
        $chunked              = array_chunk($mauticData, 25);

        foreach ($chunked as $chunk) {
            // We can only submit 25 at a time
            if ($chunk) {
                $request['compositeRequest'] = $chunk;
                $result                      = $apiHelper->syncMauticToSalesforce($request);
                $this->logger->debug('SALESFORCE: Sync Composite  '.var_export($request, true));
                $this->processCompositeResponse($result['compositeResponse'], $totalUpdated, $totalCreated, $totalErrored);
            }
        }
    }

    /**
     * @param $checkEmailsInSF
     * @param $lead
     *
     * @return bool|mixed|string
     */
    protected function setContactToSync(&$checkEmailsInSF, $lead)
    {
        $key = $this->getSyncKey($lead['email']);
        if (isset($checkEmailsInSF[$key])) {
            // this is a duplicate in Mautic
            $this->mauticDuplicates[$lead['internal_entity_id']] = 'lead-duplicate';

            return false;
        }

        $checkEmailsInSF[$key] = $lead;

        return $key;
    }

    /**
     * @param $currentContactList
     * @param $limit
     *
     * @return int
     */
    protected function getSalesforceSyncLimit($currentContactList, $limit)
    {
        $limit -= count($currentContactList);

        return $limit;
    }

    /**
     * @param $trackedContacts
     * @param $email
     * @param $contactId
     * @param $leadFields
     *
     * @return array|bool
     */
    protected function checkLeadIsContact(&$trackedContacts, $email, $contactId, $leadFields)
    {
        if (empty($trackedContacts[$email])) {
            // Check if there's an existing entry
            return $this->getIntegrationEntityRepository()->getIntegrationEntity(
                $this->getName(),
                'Contact',
                'lead',
                $contactId,
                $leadFields
            );
        }

        return false;
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
            $objects = ['Lead', 'Contact'];
        }

        if (isset($fieldsToUpdate['leadFields'])) {
            // Pass in the whole config
            $fields = $fieldsToUpdate;
        } else {
            $fields = array_flip($fieldsToUpdate);
        }

        $fieldsToUpdate = $this->prepareFieldsForSync($fields, $fieldsToUpdate, $objects);

        return $fieldsToUpdate;
    }

    /**
     * @param Lead $lead
     * @param      $config
     *
     * @return array
     */
    protected function mapContactDataForPush(Lead $lead, $config)
    {
        $fields             = array_keys($config['leadFields']);
        $fieldsToUpdateInSf = $this->getPriorityFieldsForIntegration($config);
        $fieldMapping       = [
            'Lead'    => [],
            'Contact' => [],
        ];
        $mappedData = [
            'Lead'    => [],
            'Contact' => [],
        ];

        foreach (['Lead', 'Contact'] as $object) {
            if (isset($config['objects']) && false !== array_search($object, $config['objects'])) {
                $fieldMapping[$object]['create'] = $this->prepareFieldsForSync($config['leadFields'], $fields, $object);
                $fieldMapping[$object]['update'] = isset($fieldsToUpdateInSf[$object]) ? array_intersect_key(
                    $fieldMapping[$object]['create'],
                    $fieldsToUpdateInSf[$object]
                ) : [];

                // Create an update and
                $mappedData[$object]['create'] = $this->populateLeadData(
                    $lead,
                    [
                        'leadFields'       => $fieldMapping[$object]['create'], // map with all fields available
                        'object'           => $object,
                        'feature_settings' => [
                            'objects' => $config['objects'],
                        ],
                    ]
                );

                if (isset($mappedData[$object]['create']['Id'])) {
                    unset($mappedData[$object]['create']['Id']);
                }

                $this->amendLeadDataBeforePush($mappedData[$object]['create']);

                // Set the update fields
                $mappedData[$object]['update'] = array_intersect_key($mappedData[$object]['create'], $fieldMapping[$object]['update']);
            }
        }

        return $mappedData;
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
     * @param string $object
     *
     * @return array
     */
    public function getFieldsForQuery($object)
    {
        $fields = $this->getIntegrationSettings()->getFeatureSettings();
        switch ($object) {
            case 'company':
            case 'Account':
                $fields = array_keys(array_filter($fields['companyFields']));
                break;
            default:
                $mixedFields = array_filter($fields['leadFields']);
                $fields      = [];
                foreach ($mixedFields as $sfField => $mField) {
                    if (strpos($sfField, '__'.$object) !== false) {
                        $fields[] = str_replace('__'.$object, '', $sfField);
                    }
                    if (strpos($sfField, '-'.$object) !== false) {
                        $fields[] = str_replace('-'.$object, '', $sfField);
                    }
                }
        }

        return $fields;
    }
}
