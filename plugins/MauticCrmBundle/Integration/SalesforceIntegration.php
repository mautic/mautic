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
        if (!$inAuthorization) {
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
     * Amend mapped lead data before creating to Mautic.
     *
     * @param array  $data
     * @param string $object
     *
     * @return int
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object)
    {
        $integrationEntityRepo                     = $this->getIntegrationEntityRepository();
        $settings['feature_settings']['objects'][] = $object;
        $fields                                    = array_keys($this->getAvailableLeadFields($settings));
        $params['fields']                          = implode(',', $fields);

        $updated = 0;
        $created = 0;
        $entity  = null;

        if (isset($data['records']) and $object !== 'Activity') {
            foreach ($data['records'] as $record) {
                $integrationEntities = [];
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
                        $entity                = $this->getMauticLead($dataObject, true, null, null);
                        $mauticObjectReference = 'lead';
                    } elseif ($object == 'Account') {
                        $entity                = $this->getMauticCompany($dataObject);
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

                    if ($integrationId == null) {
                        $integrationEntities[] = $this->createIntegrationEntity(
                            $object,
                            $record['Id'],
                            $mauticObjectReference,
                            $entity->getId(),
                            [],
                            false
                        );
                    } else {
                        $integrationEntity = $integrationEntityRepo->getEntity($integrationId[0]['id']);
                        $integrationEntity->setLastSyncDate($this->getLastSyncDate());
                        $integrationEntities[] = $integrationEntity;
                    }
                    if ($entity->isNewlyCreated()) {
                        ++$created;
                    } else {
                        ++$updated;
                    }

                    $this->em->detach($entity);
                    unset($entity);
                }

                $integrationEntityRepo->saveEntities($integrationEntities);
                $this->em->clear(IntegrationEntity::class);
            }
            unset($data);
            unset($integrationEntities);
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
                    'choices'     => [
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
                    'choices'     => [
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
                    'choices'     => [
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
     * @param array  $fields
     * @param array  $keys
     * @param string $object
     *
     * @return array
     */
    public function cleanSalesForceData($fields, $keys, $object)
    {
        $leadFields = [];

        foreach ($keys as $key) {
            if (strstr($key, '__'.$object)) {
                $newKey              = str_replace('__'.$object, '', $key);
                $leadFields[$newKey] = $fields['leadFields'][$key];
            }
        }

        return $leadFields;
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

        $leadFields          = $this->cleanSalesForceData($config, $fields, $object);
        $fieldsToUpdateInSf  = isset($config['update_mautic']) ? array_keys($config['update_mautic'], 1) : [];
        $leadFields          = array_diff_key($leadFields, array_flip($fieldsToUpdateInSf));
        $mappedData[$object] = $this->populateLeadData(
            $lead,
            ['leadFields' => $leadFields, 'object' => $object, 'feature_settings' => ['objects' => $config['objects']]]
        );
        if (isset($mappedData[$object]['Id'])) {
            unset($mappedData[$object]['Id']);
        }
        $this->amendLeadDataBeforePush($mappedData[$object]);

        if (isset($config['objects']) && array_search('Contact', $config['objects'])) {
            $contactFields         = $this->cleanSalesForceData($config, $fields, 'Contact');
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
                1 => 0
            ];
        }

        try {
            if ($this->isAuthorized()) {
                $result = $this->getApiHelper()->getLeads($query, $object);
                list($justUpdated, $justCreated) = $this->amendLeadDataBeforeMauticPopulate($result, $object);

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
                        $start         += $limit;
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
            'toDate'       => $endDate
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
                        $subject      = 'subtracted';
                        $row['delta'] *= -1;
                    }
                    $pointsString                = $translator->transChoice(
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

        $totalUpdated = $totalCreated = $totalErrors = $totalIgnored = 0;
        list($fieldMapping, $mauticLeadFieldString, $requiredFields, $supportedObjects) = $this->prepareFieldsForPush($config);

        if ($mauticContactLinkField = array_search('mauticContactTimelineLink', $fieldMapping)) {
            $this->pushContactLink = true;
            unset($fieldMapping[$mauticContactLinkField]);
        }

        if (empty($fieldMapping)) {
            return [0, 0, 0, 0];
        }

        // Setup the fields used for the process
        $salesforceIdMapping = [];

        $originalLimit = $limit;
        $progress      = false;

        // Get a total number of contacts to be updated and/or created for the progress counter
        $totalToUpdate = array_sum($integrationEntityRepo->findLeadsToUpdate('Salesforce', 'lead', $mauticLeadFieldString, false, $supportedObjects));
        $totalToCreate = $integrationEntityRepo->findLeadsToCreate('Salesforce', $mauticLeadFieldString, false);
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
        $isContact       = [];
        $trackedContacts = [
            'Contact' => [],
            'Lead'    => [],
        ];

        // Loop to maximize composite that may include updating contacts, updating leads, and creating leads
        while ($totalCount > 0) {
            $integrationEntities = [];
            $deletedSFLeads      = [];
            $limit               = $originalLimit;
            $mauticData          = [];
            $checkEmailsInSF     = [];
            $mauticDuplicates    = [];
            $leadsToSync         = [];

            // Process the updates
            if (!$noMoreUpdates) {
                $noMoreUpdates = $this->getMauticContactsToUpdate(
                    $checkEmailsInSF,
                    $mauticDuplicates,
                    $isContact,
                    $mauticLeadFieldString,
                    $supportedObjects,
                    $sfObject,
                    $salesforceIdMapping,
                    $trackedContacts,
                    $limit,
                    $totalCount,
                    $progress
                );
            }

            // If there is still room - grab Mautic leads to create if the Lead object is enabled
            if ('Lead' === $sfObject && (null === $limit || $limit > 0) && !empty($mauticLeadFieldString)) {
                try {
                    $sfEntityRecords = $this->getMauticContactsToCreate(
                        $checkEmailsInSF,
                        $mauticDuplicates,
                        $trackedContacts,
                        $requiredFields,
                        $mauticLeadFieldString,
                        $limit,
                        $totalCount,
                        $progress
                    );
                } catch (ApiErrorException $exception) {
                    $this->cleanupFromSync($leadsToSync, $integrationEntities, $deletedSFLeads, $mauticDuplicates, $totalIgnored, $exception);
                }
            } elseif ($checkEmailsInSF) {
                $sfEntityRecords = $this->getSalesforceObjectsByEmails($sfObject, $checkEmailsInSF, $requiredFields[$sfObject]['string']);

                if (!isset($sfEntityRecords['records'])) {
                    // Something is wrong so throw an exception to prevent creating a bunch of new leads
                    $this->cleanupFromSync(
                        $leadsToSync,
                        $integrationEntities,
                        $deletedSFLeads,
                        $mauticDuplicates,
                        $totalIgnored,
                        json_encode($sfEntityRecords)
                    );
                }
            }

            // We're done
            if (!$checkEmailsInSF) {
                break;
            }

            $processedLeads = [];

            $this->prepareMauticContactsToUpdate(
                $mauticData,
                $checkEmailsInSF,
                $processedLeads,
                $integrationEntities,
                $salesforceIdMapping,
                $trackedContacts,
                $requiredFields[$sfObject]['fields'],
                $fieldMapping[$sfObject],
                $sfObject,
                $sfEntityRecords,
                $progress
            );

            // Only create left over if Lead object is enabled in integration settings
            if ($checkEmailsInSF && isset($requiredFields['Lead'])) {
                $this->prepareMauticContactsToCreate(
                    $mauticData,
                    $checkEmailsInSF,
                    $processedLeads,
                    $requiredFields['Lead']['create_fields'],
                    $requiredFields['Lead']['fields'],
                    $fieldMapping['Lead']
                );
            }

            // Persist pending changes
            $this->cleanupFromSync($leadsToSync, $integrationEntities, $deletedSFLeads, $mauticDuplicates, $totalIgnored);

            // Make the request
            $this->makeCompositeRequest($mauticData, $salesforceIdMapping, $totalUpdated, $totalCreated, $totalErrors);

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
        $persistEntities   = $contactList = $leadList = $existingLeads = $existingContacts = [];

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
        $mauticData = $salesforceIdMapping = [];
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
                $integrationEntity      = $integrationEntityRepo->getEntity($member['id']);
                $referenceId            = $integrationEntity->getId();
                $campaignMemberInternal = $integrationEntity->getInternal();
                $internalLeadId         = $integrationEntity->getInternalEntityId();
            }
        }

        $queryUrl = $this->getQueryUrl();

        $sfLeadRecords = [];
        $allIds        = [];
        $contactIds    = [];
        $leadIds       = [];

        if (!empty($lead->getEmail())) {
            if (isset($config['objects']) && array_search('Contact', $config['objects'])) {
                $sfObject        = 'Contact';
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
                $findCampaignMembers         = "Select Id, ContactId, LeadId from CampaignMember where CampaignId = '".$integrationCampaignId
                    ."' and ContactId in ('".implode("','", $allIds)."')";
                $findCampaignLeadMembers     = "Select Id, ContactId, LeadId from CampaignMember where CampaignId = '".$integrationCampaignId
                    ."' and LeadId in ('".implode("','", $allIds)."')";
                $existingCampaignMemberLeads = $this->getApiHelper()->request(
                    'query',
                    ['q' => $findCampaignLeadMembers],
                    'GET',
                    false,
                    null,
                    $queryUrl
                );
                $existingCampaignMember      = $this->getApiHelper()->request('query', ['q' => $findCampaignMembers], 'GET', false, null, $queryUrl);
                $existingFoundRecords        = array_merge($existingCampaignMember['records'], $existingCampaignMemberLeads['records']);
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
                $id                  = (!empty($lead->getId()) ? $lead->getId() : '').'-CampaignMember'.$b['ContactId'].(!empty(
                        $referenceId
                        && $internalLeadId == $lead->getId()
                    ) ? '-'.$referenceId : '').$campaignMappingId;
                $salesforceIdMapping = [$integrationCampaignId];
                $patchurl            = $url.'/'.$memberId;
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
                $id                  = (!empty($lead->getId()) ? $lead->getId() : '').'-CampaignMember'.$b['LeadId'].(!empty(
                        $referenceId
                        && $internalLeadId == $lead->getId()
                    ) ? '-'.$referenceId : '').$campaignMappingId;
                $salesforceIdMapping = [$integrationCampaignId];
                $patchurl            = $url.'/'.$memberId;
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
                $id                  = (!empty($lead->getId()) ? $lead->getId() : '').'-CampaignMemberNew-null'.$campaignMappingId;
                $salesforceIdMapping = [];
                $b                   = array_merge($b, ['CampaignId' => $integrationCampaignId]);
                $mauticData[$id]     = [
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
        }

        return $this->processCompositeResponse($result['compositeResponse'], $salesforceIdMapping);
    }

    /**
     * @return bool|\DateTime
     */
    protected function getLastSyncDate()
    {
        return (defined('MAUTIC_DATE_MODIFIED_OVERRIDE')) ? \DateTime::createFromFormat('U', MAUTIC_DATE_MODIFIED_OVERRIDE)
            : new \DateTime();
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
     * @param      $checkEmailsInSF
     * @param      $mauticDuplicates
     * @param      $isContact
     * @param      $mauticLeadFieldString
     * @param      $sfObject
     * @param      $salesforceIdMapping
     * @param      $trackedContacts
     * @param      $limit
     * @param      $totalCount
     * @param null $progress
     *
     * @return bool
     */
    protected function getMauticContactsToUpdate(
        &$checkEmailsInSF,
        &$mauticDuplicates,
        &$isContact,
        $mauticLeadFieldString,
        $supportedObjects,
        &$sfObject,
        &$salesforceIdMapping,
        &$trackedContacts,
        &$limit,
        &$totalCount,
        $progress = null
    ) {
        $noMoreUpdates         = false;
        $integrationEntityRepo = $this->getIntegrationEntityRepository();

        // Fetch them separately so we can determine if Leads are already Contacts
        $toUpdate = $integrationEntityRepo->findLeadsToUpdate(
            'Salesforce',
            'lead',
            $mauticLeadFieldString,
            $limit,
            $sfObject,
            $salesforceIdMapping
        )[$sfObject];

        if (empty($toUpdate)) {
            if ('Lead' === $sfObject || !isset($supportedObjects['Contact'])) {
                $noMoreUpdates = true;
            } elseif (isset($supportedObjects['Lead'])) {
                // Switch to Lead
                $sfObject = 'Lead';
                $toUpdate = $integrationEntityRepo->findLeadsToUpdate(
                    'Salesforce',
                    'lead',
                    $mauticLeadFieldString,
                    $limit,
                    $sfObject,
                    $salesforceIdMapping
                )[$sfObject];
            }
        }

        $totalCount -= count($toUpdate);

        foreach ($toUpdate as $lead) {
            if (!empty($lead['email'])) {
                if ($this->pushContactLink) {
                    $lead['mauticContactTimelineLink'] = $this->getContactTimelineLink($lead['internal_entity_id']);
                }

                $key                                                = $this->getSyncKey($lead['email']);
                $trackedContacts[$lead['integration_entity']][$key] = $lead['id'];

                if ($progress) {
                    $progress->advance();
                }

                if ('Contact' == $sfObject) {
                    $isContact[$key] = $lead['id'];
                    $this->setContactToSync($checkEmailsInSF, $mauticDuplicates, $lead);
                } elseif (isset($isContact[$key])) {
                    // We already know this is a converted contact so just ignore it
                    $integrationEntity     = $this->em->getReference(
                        'MauticPluginBundle:IntegrationEntity',
                        $lead['id']
                    );
                    $integrationEntities[] = $integrationEntity->setInternalEntity('lead-converted');
                    $this->logger->debug('SALESFORCE: Converted lead '.$lead['email']);
                } else {
                    $this->setContactToSync($checkEmailsInSF, $mauticDuplicates, $lead);
                }
            }
        }

        // Only get the max limit
        if ($limit) {
            $limit -= count($checkEmailsInSF);
        }

        return $noMoreUpdates;
    }

    /**
     * @param      $checkEmailsInSF
     * @param      $mauticDuplicates
     * @param      $trackedContacts
     * @param      $requiredFields
     * @param      $mauticLeadFieldString
     * @param      $limit
     * @param      $totalCount
     * @param null $progress
     *
     * @return array
     * @throws ApiErrorException
     */
    protected function getMauticContactsToCreate(
        &$checkEmailsInSF,
        &$mauticDuplicates,
        &$trackedContacts,
        $requiredFields,
        $mauticLeadFieldString,
        $limit,
        &$totalCount,
        $progress = null
    ) {
        $integrationEntityRepo = $this->getIntegrationEntityRepository();
        $leadsToCreate         = $integrationEntityRepo->findLeadsToCreate('Salesforce', $mauticLeadFieldString, $limit);
        $totalCount            -= count($leadsToCreate);

        foreach ($leadsToCreate as $lead) {
            if ($this->pushContactLink) {
                $lead['mauticContactTimelineLink'] = $this->getContactTimelineLink($lead['internal_entity_id']);
            }
            if (isset($lead['email'])) {
                if ($key = $this->setContactToSync($checkEmailsInSF, $mauticDuplicates, $lead)) {
                    $checkEmailsToCreateInSF[$key] = $lead;
                }
            } elseif ($progress) {
                $progress->advance();
            }
        }

        // When creating, we have to check for Contacts first then Lead
        $error           = false;
        $sfEntityRecords = $this->getSalesforceObjectsByEmails('Contact', $checkEmailsInSF, $requiredFields['Contact']['string']);
        if (isset($sfEntityRecords['records'])) {
            // Loop over the records and remove those from the $checkEmailsToCreateInSF array to check against Leads
            foreach ($sfEntityRecords['records'] as $sfContactRecord) {
                $key = $this->getSyncKey($sfContactRecord['Email']);

                if (isset($checkEmailsToCreateInSF[$key])) {
                    // Create a Contact record for this lead
                    $integrationEntity = $this->createIntegrationEntity(
                        'Contact',
                        $sfContactRecord['Id'],
                        'lead',
                        $checkEmailsToCreateInSF[$key]['internal_entity_id']
                    );
                    $trackedContacts['Contact'][$key] = $integrationEntity->getId();
                    unset($checkEmailsToCreateInSF[$key]);
                }
            }

            // For any Mautic contacts left over, check to see if existing Leads exist
            if ($checkEmailsToCreateInSF) {
                $sfLeadRecords = $this->getSalesforceObjectsByEmails('Lead', $checkEmailsToCreateInSF, $requiredFields['Lead']['string']);
                if (isset($sfLeadRecords['records'])) {
                    // Merge contact records with these
                    $sfEntityRecords['records'] = array_merge($sfEntityRecords['records'], $sfLeadRecords['records']);
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

        unset($leadsToCreate, $checkEmailsToCreateInSF);

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
                            $updateLead[$mauticField] = $sfRecord[$sfField];;
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
        $requiredFields = $this->cleanSalesForceData($config, array_keys($requiredFields), $object);
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

        $fieldsToUpdateInSf = isset($config['update_mautic']) ? array_keys($config['update_mautic'], 1) : [];
        if (isset($fieldsToUpdateInSf['Id'])) {
            unset($fieldsToUpdateInSf['Id']);
        }

        $supportedObjects = [];
        $objectFields     = [];
        // Important to have contacts first!!
        if (false !== array_search('Contact', $config['objects'])) {
            $supportedObjects['Contact'] = 'Contact';
            $fieldsToCreate              = $this->cleanSalesForceData($config, array_keys($config['leadFields']), 'Contact');
            $objectFields['Contact']     = array_diff_key($fieldsToCreate, array_flip($fieldsToUpdateInSf));
        }
        if (false !== array_search('Lead', $config['objects'])) {
            $supportedObjects['Lead'] = 'Lead';
            $fieldsToCreate           = $this->cleanSalesForceData($config, array_keys($config['leadFields']), 'Lead');
            $objectFields['Lead']     = array_diff_key($fieldsToCreate, array_flip($fieldsToUpdateInSf));
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
                $requiredFields[$object]['create_fields'] = $this->cleanSalesForceData($config, array_keys($config['leadFields']), $object);

                if (isset($requiredFields[$object]['create_fields']['Id'])) {
                    unset($requiredFields[$object]['create_fields']['Id']);
                }
            }
        }

        return [$objectFields, $mauticLeadFieldString, $requiredFields, $supportedObjects];
    }

    /**
     * @param       $response
     * @param array $salesforceIdMapping
     * @param int   $totalUpdated
     * @param int   $totalCreated
     * @param int   $totalErrored
     *
     * @return array
     */
    protected function processCompositeResponse(
        $response,
        array &$salesforceIdMapping = [],
        &$totalUpdated = 0,
        &$totalCreated = 0,
        &$totalErrored = 0
    ) {
        if (is_array($response)) {
            $persistEntities = [];
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

                    if ($integrationEntityId && $object !== 'CampaignMember') {
                        $integrationEntity = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $integrationEntityId);
                        $integrationEntity->setLastSyncDate(new \DateTime());
                        $persistEntities[] = $integrationEntity;
                    } elseif (isset($campaignId) && $campaignId != null) {
                        $integrationEntity = $this->em->getReference('MauticPluginBundle:IntegrationEntity', $campaignId);
                        $persistEntities[] = $integrationEntity->setLastSyncDate($this->getLastSyncDate());
                    } elseif ($contactId) {
                        $persistEntities[] = $this->createIntegrationEntity(
                            $object,
                            null,
                            'lead-error',
                            $contactId,
                            ['error' => $item['body'][0]['message']],
                            false
                        );
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
                        $salesforceIdMapping[$contactId] = $item['body']['id'];
                        $persistEntities[]               = $this->createIntegrationEntity(
                            $object,
                            $salesforceIdMapping[$contactId],
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
                        if (isset($salesforceIdMapping[$contactId])) {
                            $integrationEntity->setIntegrationEntityId($salesforceIdMapping[$contactId]);
                        }

                        $persistEntities[] = $integrationEntity;
                    } elseif (!empty($salesforceIdMapping[$contactId])) {
                        // Found in Salesforce so create a new record for it
                        $persistEntities[] = $this->createIntegrationEntity($object, $salesforceIdMapping[$contactId], 'lead', $contactId, [], false);
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
                }
            }

            if ($persistEntities) {
                $this->getIntegrationEntityRepository()->saveEntities($persistEntities);
                unset($persistEntities);
                $this->em->clear(IntegrationEntity::class);
            }
        }

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
     * @param                 $leadsToSync
     * @param                 $integrationEntities
     * @param                 $deletedSFLeads
     * @param                 $mauticDuplicates
     * @param                 $totalIgnored
     * @param bool|\Exception $error
     *
     * @throws ApiErrorException
     */
    protected function cleanupFromSync(&$leadsToSync, &$integrationEntities, &$deletedSFLeads, &$mauticDuplicates, &$totalIgnored, $error = false)
    {
        if ($mauticDuplicates) {
            // Create integration entities for these to be ignored until they are updated
            foreach ($mauticDuplicates as $id => $dup) {
                $integrationEntities[] = $this->createIntegrationEntity('Lead', null, $dup, $id, [], false);
                ++$totalIgnored;
            }

            $mauticDuplicates = [];
        }

        $integrationEntityRepo = $this->getIntegrationEntityRepository();
        if (!empty($leadsToSync)) {
            $this->leadModel->saveEntities($leadsToSync);
            $this->em->clear(Lead::class);
            $leadsToSync = [];
        }

        if ($integrationEntities) {
            // Persist updated entities if applicable
            $integrationEntityRepo->saveEntities($integrationEntities);
            $this->em->clear(IntegrationEntity::class);
            $integrationEntities = [];
        }

        // If there are any deleted, mark it as so to prevent them from being queried over and over or recreated
        if ($deletedSFLeads) {
            $integrationEntityRepo->markAsDeleted($deletedSFLeads, $this->getName(), 'lead');
            $deletedSFLeads = [];
        }

        if ($error) {
            if ($error instanceof \Exception) {
                throw $error;
            }

            throw new ApiErrorException($error);
        }
    }

    /**
     * @param      $mauticData
     * @param      $checkEmailsInSF
     * @param      $processedLeads
     * @param      $integrationEntities
     * @param      $salesforceIdMapping
     * @param      $trackedContacts
     * @param      $requiredFields
     * @param      $objectFields
     * @param      $sfObject
     * @param      $sfEntityRecords
     * @param null $progress
     */
    protected function prepareMauticContactsToUpdate(
        &$mauticData,
        &$checkEmailsInSF,
        &$processedLeads,
        &$integrationEntities,
        &$salesforceIdMapping,
        &$trackedContacts,
        $requiredFields,
        $objectFields,
        $sfObject,
        $sfEntityRecords,
        $progress = null
    ) {
        foreach ($sfEntityRecords['records'] as $sfEntityRecord) {
            $key = $this->getSyncKey($sfEntityRecord['Email']);
            if (!isset($sfEntityRecord['Id']) || (!isset($checkEmailsInSF[$key]) && !isset($processedLeads[$key]))) {
                // This is a record we don't recognize so continue
                return;
            }

            $leadData  = (isset($processedLeads[$key])) ? $processedLeads[$key] : $checkEmailsInSF[$key];
            $contactId = $leadData['internal_entity_id'];

            // Only progress if we have a unique Lead and not updating a Salesforce entry duplicate
            if (!isset($processedLeads[$key])) {
                if ($progress) {
                    $progress->advance();
                }

                // Mark that this lead has been processed
                $processedLeads[$key] = $checkEmailsInSF[$key];
            }

            if ('Lead' === $sfObject && $sfEntityRecord['ConvertedContactId']) {
                // Only process if we haven't already to prevent converted duplicates in the integration table
                if (!isset($processedLeads[$key])) {
                    // This is a converted lead so ignore it
                    if (!empty($trackedContacts['Lead'][$key])) {
                        /** @var IntegrationEntity $integrationEntity */
                        $integrationEntity = $this->em->getReference(
                            'MauticPluginBundle:IntegrationEntity',
                            $trackedContacts['Lead'][$key]
                        );
                        $integrationEntity->setIntegrationEntityId($sfEntityRecord['Id']);
                        $integrationEntities[] = $integrationEntity->setInternalEntity('lead-converted');
                    } else {
                        $integrationEntities[] = $this->createIntegrationEntity(
                            'Lead',
                            $sfEntityRecord['Id'],
                            'lead-converted',
                            $contactId,
                            [],
                            false
                        );
                    }
                    $this->logger->debug('SALESFORCE: Converted lead '.$sfEntityRecord['Email']);

                    // Mark that this lead has been processed then continue on because we can't update this record
                    unset($checkEmailsInSF[$key]);
                }

                continue;
            }

            // Keep track of Mautic ID to Salesforce ID for the integration table
            $salesforceIdMapping[$contactId] = $sfEntityRecord['Id'];

            $leadEntity = $this->em->getReference('MauticLeadBundle:Lead', $leadData['internal_entity_id']);
            $syncLead   = false;
            if ($updateLead = $this->buildCompositeBody(
                $mauticData,
                $requiredFields,
                $objectFields,
                $sfObject,
                $leadData,
                $sfEntityRecord['Id'],
                $sfEntityRecord
            )
            ) {
                // Get the lead entity
                /** @var Lead $leadEntity */
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
        $leadSfFieldsToCreate,
        $requiredFields,
        $objectFields
    ) {
        foreach ($checkEmailsInSF as $key => $lead) {
            if (!empty($lead['integration_entity_id'])) {
                if ($this->buildCompositeBody(
                    $mauticData,
                    $requiredFields,
                    $objectFields,
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
                    $requiredFields,
                    $leadSfFieldsToCreate, //use all matched fields when creating new records in SF
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
     * @param     $salesforceIdMapping
     * @param int $totalUpdated
     * @param int $totalCreated
     * @param int $totalErrored
     */
    protected function makeCompositeRequest($mauticData, $salesforceIdMapping, &$totalUpdated = 0, &$totalCreated = 0, &$totalErrored = 0)
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
                $this->processCompositeResponse($result['compositeResponse'], $salesforceIdMapping, $totalUpdated, $totalCreated, $totalErrored);
            }
        }
    }

    /**
     * @param $checkEmailsInSF
     * @param $mauticDuplicates
     * @param $lead
     *
     * @return bool|mixed|string
     */
    protected function setContactToSync(&$checkEmailsInSF, &$mauticDuplicates, $lead)
    {
        $key = $this->getSyncKey($lead['email']);
        if (isset($checkEmailsInSF[$key])) {
            // this is a duplicate in Mautic
            $mauticDuplicates[$lead['internal_entity_id']] = 'lead-duplicate';

            return false;
        }

        $checkEmailsInSF[$key] = $lead;

        return $key;
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
