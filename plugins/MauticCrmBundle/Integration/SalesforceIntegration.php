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
            return ($field['type'] !== 'boolean' && empty($field['nillable']) && !in_array($field['name'], ['Status', 'Id']))
                || ($object == 'Lead'
                    && in_array($field['name'], ['Company']));
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
                                    if ($fieldInfo['type'] == 'boolean') {
                                        $type = 'boolean';
                                    } else {
                                        $type = 'string';
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
        $integrationEntityRepo = $this->getIntegrationEntityRepository();
        $updated               = 0;
        $created               = 0;
        $entity                = null;

        if (isset($data['records']) and $object !== 'Activity') {
            foreach ($data['records'] as $record) {
                $this->persistIntegrationEntities = [];
                if (isset($record['attributes']['type']) && $record['attributes']['type'] == 'Account') {
                    $newName = '';
                } else {
                    $newName = '__'.$object;
                }
                foreach ($record as $key => $item) {
                    if ($object !== 'Activity') {
                        $dataObject[$key.$newName] = $item;
                    }
                }

                if (isset($dataObject) && $dataObject) {
                    $entity = false;
                    if ($object == 'Lead' or $object == 'Contact') {
                        // Set owner so that it maps if configured to do so
                        if (!empty($dataObject['Owner__Lead']['Email'])) {
                            $dataObject['owner_email'] = $dataObject['Owner__Lead']['Email'];
                        } elseif (!empty($dataObject['Owner__Contact']['Email'])) {
                            $dataObject['owner_email'] = $dataObject['Owner__Contact']['Email'];
                        }
                        $entity                = $this->getMauticLead($dataObject, true, null, null, $object);
                        $mauticObjectReference = 'lead';
                    } elseif ($object == 'Account') {
                        $entity                = $this->getMauticCompany($dataObject, 'Account');
                        $mauticObjectReference = 'company';
                    } else {
                        $this->logIntegrationError(
                            new \Exception(
                                sprintf('Received an unexpected object without an internalObjectReference "%s"', $object)
                            )
                        );
                    }

                    if (!$entity) {
                        continue;
                    }

                    $integrationId = $integrationEntityRepo->getIntegrationsEntityId(
                        'Salesforce',
                        $object,
                        $mauticObjectReference,
                        $entity->getId()
                    );

                    if (empty($integrationId)) {
                        $this->persistIntegrationEntities[] = $this->createIntegrationEntity(
                            $object,
                            $record['Id'],
                            $mauticObjectReference,
                            $entity->getId(),
                            [],
                            false
                        );
                    } else {
                        $integrationEntity = $integrationEntityRepo->getEntity($integrationId[0]['id']);
                        $integrationEntity->setLastSyncDate($this->getLastSyncDate($entity, $params, false));
                        $this->persistIntegrationEntities[] = $integrationEntity;
                    }

                    if (method_exists($entity, 'isNewlyCreated') && $entity->isNewlyCreated()) {
                        ++$created;
                    } else {
                        ++$updated;
                    }

                    $this->em->detach($entity);
                    unset($entity);
                }

                $integrationEntityRepo->saveEntities($this->persistIntegrationEntities);
                $this->em->clear(IntegrationEntity::class);
            }
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
    public function cleanSalesForceData($fields, $keys, $object)
    {
        $leadFields = [];
        $objects    = (!is_array($object)) ? [$object] : $object;

        if (is_string($object) && 'Account' === $object) {
            return isset($fields['companyFields']) ? $fields['companyFields'] : [];
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
    public function pushLead($lead, $config = [])
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return [];
        }

        $object = 'Lead'; //Salesforce objects, default is Lead

        $fields = array_keys($config['leadFields']);

        $fieldsToUpdateInSf  = $this->getPriorityFieldsForIntegration($config);
        $leadFields          = $this->cleanSalesForceData($config['leadFields'], $fields, $object);
        $leadFields          = array_intersect_key($leadFields, $fieldsToUpdateInSf[$object]);
        $mappedData[$object] = $this->populateLeadData(
            $lead,
            ['leadFields' => $leadFields, 'object' => $object, 'feature_settings' => ['objects' => $config['objects']]]
        );
        if (isset($mappedData[$object]['Id'])) {
            unset($mappedData[$object]['Id']);
        }
        $this->amendLeadDataBeforePush($mappedData[$object]);

        if (isset($config['objects']) && array_search('Contact', $config['objects'])) {
            $contactFields         = $this->cleanSalesForceData($config['leadFields'], $fields, 'Contact');
            $mappedData['Contact'] = $this->populateLeadData(
                $lead,
                ['leadFields' => $contactFields, 'object' => 'Contact', 'feature_settings' => ['objects' => $config['objects']]]
            );
            $this->amendLeadDataBeforePush($mappedData['Contact']);
        }
        if (empty($mappedData)) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $createdLeadData = $this->getApiHelper()->createLead($mappedData);
                if (isset($createdLeadData['id'])) {
                    $object = 'Lead';
                    /** @var IntegrationEntityRepository $integrationEntityRepo */
                    $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
                    $integrationId         = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', $object, 'leads', $lead->getId());

                    if (empty($integrationId)) {
                        $integrationEntity = $this->createIntegrationEntity($object, $createdLeadData['id'], 'lead', $lead->getId(), [], false);
                    } else {
                        $integrationEntity = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $integrationId[0]['id']);
                    }
                    $integrationEntity->setLastSyncDate($this->getLastSyncDate());
                    $integrationEntityRepo->saveEntity($integrationEntity);

                    return $createdLeadData['id'];
                }

                return true;
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
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
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
                $result                          = $this->getApiHelper()->getLeads($query, $object);
                list($justUpdated, $justCreated) = $this->amendLeadDataBeforeMauticPopulate($result, $object, $params);

                $executed[0] += $justUpdated;
                $executed[1] += $justCreated;
                if (isset($result['nextRecordsUrl'])) {
                    $query['nextUrl'] = $result['nextRecordsUrl'];
                    $this->getLeads($params, $query, $executed, $result['records'], $object);
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        $this->logger->debug('SALESFORCE: '.$this->getApiHelper()->getRequestCounter().' API requests made for getLeads: '.$object);

        return $executed;
    }

    /**
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
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
                        foreach ($salesForceIds as $ids) {
                            $leadIds[] = $ids['internal_entity_id'];
                        }

                        // Collect lead activity for this batch
                        $leadActivity = $this->getLeadData(
                            $startDate,
                            $endDate,
                            $leadIds
                        );

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
                            }
                        }

                        if (!empty($salesForceLeadData)) {
                            $apiHelper->createLeadActivity($salesForceLeadData, $object);
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
        $limit                 = (isset($params['limit'])) ? $params['limit'] : 100;
        $config                = $this->mergeConfigToFeatureSettings($params);
        $integrationEntityRepo = $this->getIntegrationEntityRepository();

        $totalUpdated = 0;
        $totalCreated = 0;
        $totalErrors  = 0;
        $totalIgnored = 0;

        list($fieldMapping, $mauticLeadFieldString, $requiredFields, $supportedObjects) = $this->prepareFieldsForPush($config);

        if ($mauticContactLinkField = array_search('mauticContactTimelineLink', $fieldMapping)) {
            $this->pushContactLink = true;
            unset($fieldMapping[$mauticContactLinkField]);
        }

        if (empty($fieldMapping)) {
            return [0, 0, 0, 0];
        }

        $originalLimit = $limit;
        $progress      = false;

        // Get a total number of contacts to be updated and/or created for the progress counter
        $totalToUpdate = array_sum($integrationEntityRepo->findLeadsToUpdate('Salesforce', 'lead', $mauticLeadFieldString, false, $supportedObjects));
        $totalToCreate = (in_array('Lead', $supportedObjects)) ? $integrationEntityRepo->findLeadsToCreate('Salesforce', $mauticLeadFieldString, false) : 0;
        $totalCount    = $totalToCreate + $totalToUpdate;

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
                        $totalCount
                    );
                }

                if ($limit) {
                    // Mainly done for test mocking purposes
                    $limit = $this->getSalesforceSyncLimit($checkEmailsInSF, $limit);
                }
            }

            // If there is still room - grab Mautic leads to create if the Lead object is enabled
            if ('Lead' === $sfObject && (null === $limit || $limit > 0) && !empty($mauticLeadFieldString)) {
                try {
                    $sfEntityRecords = $this->getMauticContactsToCreate(
                        $checkEmailsInSF,
                        $requiredFields,
                        $mauticLeadFieldString,
                        $limit,
                        $totalCount,
                        $progress
                    );
                } catch (ApiErrorException $exception) {
                    $this->cleanupFromSync($leadsToSync, $totalIgnored, $exception);
                }
            } elseif ($checkEmailsInSF) {
                $sfEntityRecords = $this->getSalesforceObjectsByEmails($sfObject, $checkEmailsInSF, $requiredFields[$sfObject]['string']);

                if (!isset($sfEntityRecords['records'])) {
                    // Something is wrong so throw an exception to prevent creating a bunch of new leads
                    $this->cleanupFromSync(
                        $leadsToSync,
                        $totalIgnored,
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
                $requiredFields,
                $fieldMapping,
                $mauticLeadFieldString,
                $sfEntityRecords,
                $progress
            );

            // Only create left over if Lead object is enabled in integration settings
            if ($checkEmailsInSF && isset($requiredFields['Lead'])) {
                $this->prepareMauticContactsToCreate(
                    $mauticData,
                    $checkEmailsInSF,
                    $processedLeads,
                    $requiredFields,
                    $fieldMapping
                );
            }

            // Persist pending changes
            $this->cleanupFromSync($leadsToSync, $totalIgnored);

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
            if (array_search('Contact', $config['objects'])) {
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
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        $campaigns         = [];
        try {
            $campaigns = $this->getApiHelper()->getCampaigns();
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
            if (!$silenceExceptions) {
                throw $e;
            }
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
        $silenceExceptions    = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        $campaignMemberStatus = [];
        try {
            $campaignMemberStatus = $this->getApiHelper()->getCampaignMemberStatus($campaignId);
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $campaignMemberStatus;
    }

    /**
     * @param Lead $lead
     * @param      $integrationCampaignId
     * @param      $status
     *
     * @return array
     */
    public function pushLeadToCampaign(Lead $lead, $integrationCampaignId, $status)
    {
        $mauticData = [];
        $objectId   = null;
        $all        = [];
        $createLead = false;
        $config     = $this->mergeConfigToFeatureSettings([]);
        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        //find campaignMember
        $existingCampaignMember = $integrationEntityRepo->getIntegrationsEntityId(
            'Salesforce',
            'CampaignMember',
            'lead',
            $lead->getId(),
            null,
            null,
            null,
            false,
            0,
            0,
            "'".$integrationCampaignId."'"
        );

        if ($status) {
            $body = [
                'Status' => $status,
            ];
        } else {
            $body = ['Status' => ''];
        }

        $object = 'CampaignMember';
        $url    = '/services/data/v38.0/sobjects/'.$object;
        if ($existingCampaignMember) {
            foreach ($existingCampaignMember as $member) {
                $integrationEntity = $integrationEntityRepo->getEntity($member['id']);
                $referenceId       = $integrationEntity->getId();
                $internalLeadId    = $integrationEntity->getInternalEntityId();
            }
        }

        $queryUrl = $this->getQueryUrl();

        $sfLeadRecords = [];
        $allIds        = [];
        $contactIds    = [];
        $leadIds       = [];

        if (!empty($lead->getEmail())) {
            if (isset($config['objects']) && array_search('Contact', $config['objects'])) {
                $findContact     = 'select Id from Contact where email = \''.$lead->getEmail().'\'';
                $sfRecordContact = $this->getApiHelper()->request('query', ['q' => $findContact], 'GET', false, null, $queryUrl);
                if (!empty($sfRecordContact['records'])) {
                    $sfLeadRecords = $sfRecordContact['records'];
                }
                foreach ($sfRecordContact['records'] as $sfLeadRecord) {
                    $sfLeadId            = $sfLeadRecord['Id'];
                    $allIds[]            = $sfLeadRecord['Id'];
                    $type                = 'ContactId';
                    $existingBody[$type] = $sfLeadId;
                    $all[]               = array_merge($body, $existingBody);
                }
            }

            $findLead     = 'select Id from Lead where email = \''.$lead->getEmail().'\' and ConvertedContactId = NULL';
            $sfRecordLead = $this->getApiHelper()->request('query', ['q' => $findLead], 'GET', false, null, $queryUrl);
            $existingBody = [];
            if (!empty($sfRecordLead['records'])) {
                $sfLeadRecords = array_merge($sfLeadRecords, $sfRecordLead['records']);
                foreach ($sfRecordLead['records'] as $sfLeadRecord) {
                    $sfLeadId            = $sfLeadRecord['Id'];
                    $allIds[]            = $sfLeadRecord['Id'];
                    $type                = 'LeadId';
                    $existingBody[$type] = $sfLeadId;
                    $all[]               = array_merge($body, $existingBody);
                }
            }

            if (!empty($sfLeadRecords)) {
                $findCampaignMembers = "Select Id, ContactId, LeadId from CampaignMember where CampaignId = '".$integrationCampaignId
                    ."' and ContactId in ('".implode("','", $allIds)."')";
                $findCampaignLeadMembers = "Select Id, ContactId, LeadId from CampaignMember where CampaignId = '".$integrationCampaignId
                    ."' and LeadId in ('".implode("','", $allIds)."')";
                $existingCampaignMemberLeads = $this->getApiHelper()->request(
                    'query',
                    ['q' => $findCampaignLeadMembers],
                    'GET',
                    false,
                    null,
                    $queryUrl
                );
                $existingCampaignMember = $this->getApiHelper()->request('query', ['q' => $findCampaignMembers], 'GET', false, null, $queryUrl);
                $existingFoundRecords   = array_merge($existingCampaignMember['records'], $existingCampaignMemberLeads['records']);
                if (!empty($existingFoundRecords)) {
                    foreach ($existingFoundRecords as $campaignMember) {
                        if (!empty($campaignMember['ContactId'])) {
                            $contactIds[$campaignMember['Id']] = $campaignMember['ContactId'];
                        }
                        if (!empty($campaignMember['LeadId'])) {
                            $leadIds[$campaignMember['Id']] = $campaignMember['LeadId'];
                        }
                    }
                }
            } else {
                $createLead = true;
            }
        }

        if ($createLead) {
            $integration_entity_id = $this->pushLead($lead);
            $body['LeadId']        = $integration_entity_id;
            $all[]                 = $body;
        }

        foreach ($all as $key => $b) {
            $campaignMappingId = '-'.$integrationCampaignId;
            if (isset($b['ContactId']) and $memberId = array_search($b['ContactId'], $contactIds)) {
                $id = (!empty($lead->getId()) ? $lead->getId() : '').'-CampaignMember'.$b['ContactId'].(!empty(
                        $referenceId
                        && $internalLeadId == $lead->getId()
                    ) ? '-'.$referenceId : '').$campaignMappingId;
                $patchurl = $url.'/'.$memberId;
                unset($b['ContactId']);
                $mauticData[$id] = [
                    'method'      => 'PATCH',
                    'url'         => $patchurl,
                    'referenceId' => $id,
                    'body'        => $b,
                    'httpHeaders' => [
                        'Sforce-Auto-Assign' => 'FALSE',
                    ],
                ];
            } elseif (isset($b['LeadId']) and $memberId = array_search($b['LeadId'], $leadIds)) {
                $id = (!empty($lead->getId()) ? $lead->getId() : '').'-CampaignMember'.$b['LeadId'].(!empty(
                        $referenceId
                        && $internalLeadId == $lead->getId()
                    ) ? '-'.$referenceId : '').$campaignMappingId;
                $patchurl = $url.'/'.$memberId;
                unset($b['LeadId']);
                $mauticData[$id] = [
                    'method'      => 'PATCH',
                    'url'         => $patchurl,
                    'referenceId' => $id,
                    'body'        => $b,
                    'httpHeaders' => [
                        'Sforce-Auto-Assign' => 'FALSE',
                    ],
                ];
            } else {
                $id              = (!empty($lead->getId()) ? $lead->getId() : '').'-CampaignMemberNew-null'.$campaignMappingId;
                $b               = array_merge($b, ['CampaignId' => $integrationCampaignId]);
                $mauticData[$id] = [
                    'method'      => 'POST',
                    'url'         => $url,
                    'referenceId' => $id,
                    'body'        => $b,
                ];
            }
        }
        $request['allOrNone']        = 'false';
        $request['compositeRequest'] = array_values($mauticData);
        /** @var SalesforceApi $apiHelper */
        $apiHelper = $this->getApiHelper();

        if (!empty($request)) {
            $result = $apiHelper->syncMauticToSalesforce($request);

            return $this->processCompositeResponse($result['compositeResponse']);
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
     * @param   $checkEmailsInSF
     * @param   $mauticLeadFieldString
     * @param   $sfObject
     * @param   $trackedContacts
     * @param   $limit
     * @param   $totalCount
     *
     * @return bool
     */
    protected function getMauticContactsToUpdate(
        &$checkEmailsInSF,
        $mauticLeadFieldString,
        &$sfObject,
        &$trackedContacts,
        $limit,
        &$totalCount
    ) {
        // Fetch them separately so we can determine if Leads are already Contacts
        $toUpdate = $this->getIntegrationEntityRepository()->findLeadsToUpdate(
            'Salesforce',
            'lead',
            $mauticLeadFieldString,
            $limit,
            $sfObject
        )[$sfObject];

        $toUpdateCount = count($toUpdate);
        $totalCount -= $toUpdateCount;

        foreach ($toUpdate as $lead) {
            if (!empty($lead['email'])) {
                if ($this->pushContactLink) {
                    $lead['mauticContactTimelineLink'] = $this->getContactTimelineLink($lead['internal_entity_id']);
                }

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
     * @param      $trackedContacts
     * @param      $requiredFields
     * @param      $mauticLeadFieldString
     * @param      $limit
     * @param      $totalCount
     * @param null $progress
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    protected function getMauticContactsToCreate(
        &$checkEmailsInSF,
        $requiredFields,
        $mauticLeadFieldString,
        $limit,
        &$totalCount,
        $progress = null
    ) {
        $integrationEntityRepo = $this->getIntegrationEntityRepository();
        $leadsToCreate         = $integrationEntityRepo->findLeadsToCreate('Salesforce', $mauticLeadFieldString, $limit);
        $totalCount -= count($leadsToCreate);
        $foundContacts = [];

        foreach ($leadsToCreate as $lead) {
            if ($this->pushContactLink) {
                $lead['mauticContactTimelineLink'] = $this->getContactTimelineLink($lead['internal_entity_id']);
            }
            if (isset($lead['email'])) {
                $this->setContactToSync($checkEmailsInSF, $lead);
            } elseif ($progress) {
                $progress->advance();
            }
        }

        // When creating, we have to check for Contacts first then Lead
        $error           = false;
        $sfEntityRecords = $this->getSalesforceObjectsByEmails('Contact', $checkEmailsInSF, $requiredFields['Contact']['string']);
        if (isset($sfEntityRecords['records'])) {
            foreach ($sfEntityRecords['records'] as $sfContactRecord) {
                $key                 = $this->getSyncKey($sfContactRecord['Email']);
                $foundContacts[$key] = $key;
            }

            // For any Mautic contacts left over, check to see if existing Leads exist
            if ($checkSfLeads = array_diff_key($checkEmailsInSF, $foundContacts)) {
                $sfLeadRecords = $this->getSalesforceObjectsByEmails('Lead', $checkSfLeads, $requiredFields['Lead']['string']);

                if (isset($sfLeadRecords['records'])) {
                    // Merge contact records with these
                    $sfEntityRecords['records']   = array_merge($sfEntityRecords['records'], $sfLeadRecords['records']);
                    $sfEntityRecords['totalSize'] = (int) $sfEntityRecords['totalSize'] + (int) $sfLeadRecords['totalSize'];
                } else {
                    $error = json_encode($sfLeadRecords);
                }
            }
        } else {
            $error = json_encode($sfEntityRecords);
        }

        if ($error) {
            throw new ApiErrorException($error);
        }

        unset($leadsToCreate, $checkSfLeads);

        return $sfEntityRecords;
    }

    /**
     * @param      $mauticData
     * @param      $requiredFields
     * @param      $fieldsToUpdateInSfUpdate
     * @param      $object
     * @param      $lead
     * @param null $objectId
     * @param null $sfRecord
     *
     * @return array
     */
    protected function buildCompositeBody(
        &$mauticData,
        $requiredFields,
        $fieldsToUpdateInSfUpdate,
        $object,
        &$lead,
        $objectId = null,
        $sfRecord = null
    ) {
        $body       = [];
        $updateLead = [];

        if (isset($lead['email']) && !empty($lead['email'])) {
            //use a composite patch here that can update and create (one query) every 200 records
            foreach ($fieldsToUpdateInSfUpdate as $sfField => $mauticField) {
                if (isset($lead[$mauticField])) {
                    $body[$sfField] = $this->cleanPushData($lead[$mauticField]);
                }

                if (array_key_exists($sfField, $requiredFields) && empty($body[$sfField])) {
                    if (isset($sfRecord[$sfField])) {
                        $body[$sfField] = $sfRecord[$sfField];
                        if (empty($lead[$mauticField]) && !empty($sfRecord[$sfField])) {
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
        $requiredFields = $this->cleanSalesForceData($config['leadFields'], array_keys($requiredFields), $object);
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

        $fieldsToUpdateInSf = $this->getPriorityFieldsForIntegration($config);
        $fieldKeys          = array_keys($config['leadFields']);
        $supportedObjects   = [];
        $objectFields       = [];

        // Important to have contacts first!!
        if (false !== array_search('Contact', $config['objects'])) {
            $supportedObjects['Contact'] = 'Contact';
            $fieldsToCreate              = $this->cleanSalesForceData($config['leadFields'], $fieldKeys, 'Contact');
            $objectFields['Contact']     = array_intersect_key($fieldsToCreate, $fieldsToUpdateInSf['Contact']);
        }
        if (false !== array_search('Lead', $config['objects'])) {
            $supportedObjects['Lead'] = 'Lead';
            $fieldsToCreate           = $this->cleanSalesForceData($config['leadFields'], $fieldKeys, 'Lead');
            $objectFields['Lead']     = array_intersect_key($fieldsToCreate, $fieldsToUpdateInSf['Lead']);
        }

        $mauticLeadFieldString = implode(', l.', $leadFields);
        $mauticLeadFieldString = 'l.'.$mauticLeadFieldString;
        $availableFields       = $this->getAvailableLeadFields(['feature_settings' => ['objects' => $supportedObjects]]);

        // Setup required fields
        $requiredFields = [];
        foreach ($supportedObjects as $object) {
            list($requiredFields[$object]['fields'], $requiredFields[$object]['string']) = $this->getRequiredFieldString(
                $config,
                $availableFields,
                $object
            );

            if ('Lead' === $object) {
                // Only Salesforce Leads are created
                $requiredFields[$object]['create_fields'] = $this->cleanSalesForceData($config, null, $object);

                if (isset($requiredFields[$object]['create_fields']['Id'])) {
                    unset($requiredFields[$object]['create_fields']['Id']);
                }
            }
        }

        return [$objectFields, $mauticLeadFieldString, $requiredFields, $supportedObjects];
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
        $requiredFields,
        $objectFields,
        $mauticLeadFieldString,
        $sfEntityRecords,
        $progress = null
    ) {
        foreach ($sfEntityRecords['records'] as $sfKey => $sfEntityRecord) {
            $skipObject = false;
            $syncLead   = false;
            $sfObject   = $sfEntityRecord['attributes']['type'];

            $key = $this->getSyncKey($sfEntityRecord['Email']);
            if (!isset($sfEntityRecord['Id']) || (!isset($checkEmailsInSF[$key]) && !isset($processedLeads[$key]))) {
                // This is a record we don't recognize so continue
                return;
            }

            $leadData  = (isset($processedLeads[$key])) ? $processedLeads[$key] : $checkEmailsInSF[$key];
            $contactId = $leadData['internal_entity_id'];

            if (
                isset($checkEmailsInSF[$key]) && (
                    (
                        'Lead' === $sfObject && !empty($sfEntityRecord['ConvertedContactId'])
                    ) ||
                    (
                        isset($checkEmailsInSF[$key]['integration_entity']) &&
                        'Contact' === $sfObject &&
                        'Lead' === $checkEmailsInSF[$key]['integration_entity']
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
                    $requiredFields[$sfObject]['fields'],
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
                    $syncLead = true;
                }

                // Validate if we have a company for this Mautic contact
                if (!empty($sfEntityRecord['Company'])) {
                    $company = IdentifyCompanyHelper::identifyLeadsCompany(
                        ['company' => $sfEntityRecord['Company']],
                        null,
                        $this->companyModel
                    );

                    if (!empty($company[2])) {
                        $this->companyModel->addLeadToCompany($company[2], $leadEntity);
                        $syncLead = true;
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
     * @param $leadSfFieldsToCreate
     * @param $requiredFields
     * @param $objectFields
     */
    protected function prepareMauticContactsToCreate(
        &$mauticData,
        &$checkEmailsInSF,
        &$processedLeads,
        $fieldMappings,
        $objectFields
    ) {
        foreach ($checkEmailsInSF as $key => $lead) {
            if (!empty($lead['integration_entity_id'])) {
                if ($this->buildCompositeBody(
                    $mauticData,
                    $fieldMappings[$lead['integration_entity']]['fields'],
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
                    $fieldMappings['Lead']['fields'],
                    $fieldMappings['Lead']['create_fields'], //use all matched fields when creating new records in SF
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

        $fieldsToUpdate = $this->cleanSalesForceData($fields, $fieldsToUpdate, $objects);
        unset($fieldsToUpdate['Contact']['Id'], $fieldsToUpdate['Lead']['Id']);

        return $fieldsToUpdate;
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
}
