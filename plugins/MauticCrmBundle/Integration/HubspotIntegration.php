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

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\CacheStorageHelper;
use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\PathsHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Model\NotificationModel;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\DoNotContact;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Model\IntegrationEntityModel;
use Mautic\StageBundle\Entity\Stage;
use MauticPlugin\MauticCrmBundle\Api\HubspotApi;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * @method HubspotApi getApiHelper
 */
class HubspotIntegration extends CrmAbstractIntegration
{
    /**
     * @var UserHelper
     */
    protected $userHelper;

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
        DoNotContact $doNotContact,
        UserHelper $userHelper
    ) {
        $this->userHelper = $userHelper;

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
            $doNotContact
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Hubspot';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            $this->getApiKey() => 'mautic.hubspot.form.apikey',
        ];
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return 'hapikey';
    }

    /**
     * Get the array key for the auth token.
     *
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'hapikey';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'get_leads'];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'key';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://api.hubapi.com';
    }

    /**
     * Get if data priority is enabled in the integration or not default is false.
     *
     * @return string
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
     */
    public function getFormLeadFields($settings = [])
    {
        return $this->getFormFieldsByObject('contacts', $settings);
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = [])
    {
        if ($fields = parent::getAvailableLeadFields()) {
            return $fields;
        }

        $hubsFields        = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

        if (isset($settings['feature_settings']['objects'])) {
            $hubspotObjects = $settings['feature_settings']['objects'];
        } else {
            $settings       = $this->settings->getFeatureSettings();
            $hubspotObjects = isset($settings['objects']) ? $settings['objects'] : ['contacts'];
        }

        try {
            if ($this->isAuthorized()) {
                if (!empty($hubspotObjects) and is_array($hubspotObjects)) {
                    foreach ($hubspotObjects as $object) {
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$object;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $hubsFields[$object] = $fields;

                            continue;
                        }

                        $leadFields = $this->getApiHelper()->getLeadFields($object);
                        if (isset($leadFields)) {
                            foreach ($leadFields as $fieldInfo) {
                                $hubsFields[$object][$fieldInfo['name']] = [
                                    'type'     => 'string',
                                    'label'    => $fieldInfo['label'],
                                    'required' => ('email' === $fieldInfo['name']),
                                ];
                                if (!empty($fieldInfo['readOnlyValue'])) {
                                    $hubsFields[$object][$fieldInfo['name']]['update_mautic'] = 1;
                                    $hubsFields[$object][$fieldInfo['name']]['readOnly']      = 1;
                                }
                            }
                        }

                        $this->cache->set('leadFields'.$cacheSuffix, $hubsFields[$object]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $hubsFields;
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

        return $this->prepareFieldsForSync($fields, $fieldsToUpdate, $objects);
    }

    /**
     * Format the lead data to the structure that HubSpot requires for the createOrUpdate request.
     *
     * @param array $leadData All the lead fields mapped
     *
     * @return array
     */
    public function formatLeadDataForCreateOrUpdate($leadData, $lead, $updateLink = false)
    {
        $formattedLeadData = [];

        if (!$updateLink) {
            foreach ($leadData as $field => $value) {
                if ('lifecyclestage' == $field || 'associatedcompanyid' == $field) {
                    continue;
                }
                $formattedLeadData['properties'][] = [
                    'property' => $field,
                    'value'    => $value,
                ];
            }
        }

        return $formattedLeadData;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized()
    {
        $keys = $this->getKeys();

        return isset($keys[$this->getAuthTokenKey()]);
    }

    /**
     * @return mixed
     */
    public function getHubSpotApiKey()
    {
        $tokenData = $this->getKeys();

        return $tokenData[$this->getAuthTokenKey()];
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' == $formArea) {
            $builder->add(
                'objects',
                ChoiceType::class,
                [
                    'choices' => [
                        'mautic.hubspot.object.contact' => 'contacts',
                        'mautic.hubspot.object.company' => 'company',
                    ],
                    'expanded'          => true,
                    'multiple'          => true,
                    'label'             => $this->getTranslator()->trans('mautic.crm.form.objects_to_pull_from', ['%crm%' => 'Hubspot']),
                    'label_attr'        => ['class' => ''],
                    'placeholder'       => false,
                    'required'          => false,
                ]
            );
        }
    }

    /**
     * @param $data
     * @param $object
     *
     * @return array
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object)
    {
        if (!isset($data['properties'])) {
            return [];
        }
        foreach ($data['properties'] as $key => $field) {
            $value              = str_replace(';', '|', $field['value']);
            $fieldsValues[$key] = $value;
        }
        if ('Lead' == $object && !isset($fieldsValues['email'])) {
            foreach ($data['identity-profiles'][0]['identities'] as $identifiedProfile) {
                if ('EMAIL' == $identifiedProfile['type']) {
                    $fieldsValues['email'] = $identifiedProfile['value'];
                }
            }
        }

        return $fieldsValues;
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
        if (!is_array($executed)) {
            $executed = [
                0 => 0,
                1 => 0,
            ];
        }
        try {
            if ($this->isAuthorized()) {
                $config                         = $this->mergeConfigToFeatureSettings();
                $fields                         = implode('&property=', array_keys($config['leadFields']));
                $params['post_append_to_query'] = '&property='.$fields.'&property=lifecyclestage';
                $params['Count']                = 100;

                $data = $this->getApiHelper()->getContacts($params);
                if (isset($data['contacts'])) {
                    foreach ($data['contacts'] as $contact) {
                        if (is_array($contact)) {
                            $contactData = $this->amendLeadDataBeforeMauticPopulate($contact, 'Lead');
                            $contact     = $this->getMauticLead($contactData);
                            if ($contact && !$contact->isNewlyCreated()) { //updated
                                $executed[0] = $executed[0] + 1;
                            } elseif ($contact && $contact->isNewlyCreated()) { //newly created
                                $executed[1] = $executed[1] + 1;
                            }

                            if ($contact) {
                                $this->em->detach($contact);
                            }
                        }
                    }
                    if ($data['has-more']) {
                        $params['vidOffset']  = $data['vid-offset'];
                        $params['timeOffset'] = $data['time-offset'];

                        $this->getLeads($params, $query, $executed);
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
     * @param null  $executed
     */
    public function getCompanies($params = [], $id = false, &$executed = null)
    {
        $results = [];
        try {
            if ($this->isAuthorized()) {
                $params['Count'] = 100;
                $data            = $this->getApiHelper()->getCompanies($params, $id);
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
                                $this->em->detach($company);
                            }
                        }
                    }
                    if (isset($data['hasMore']) and $data['hasMore']) {
                        $params['offset'] = $data['offset'];
                        if ($params['offset'] < strtotime($params['start'])) {
                            $this->getCompanies($params, $id, $executed);
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

        if (isset($data['lifecyclestage'])) {
            $stageName = $data['lifecyclestage'];
            unset($data['lifecyclestage']);
        }

        if (isset($data['associatedcompanyid'])) {
            $company = $this->getCompanies([], $data['associatedcompanyid']);
            unset($data['associatedcompanyid']);
        }

        if ($lead = parent::getMauticLead($data, false, $socialCache, $identifiers, $object)) {
            if (isset($stageName)) {
                $stage = $this->em->getRepository('MauticStageBundle:Stage')->getStageByName($stageName);

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
                        $this->translator->trans(
                            'mautic.stage.import.action.name',
                            [
                                '%name%' => $this->userHelper->getUser()->getUsername(),
                            ]
                        )
                    );
                    $log->setDateAdded(new \DateTime());
                    $lead->stageChangeLog($log);
                }
            }

            if ($persist && !empty($lead->getChanges(true))) {
                // Only persist if instructed to do so as it could be that calling code needs to manipulate the lead prior to executing event listeners
                try {
                    $lead->setManipulator(new LeadManipulator(
                        'plugin',
                        $this->getName(),
                        null,
                        $this->getDisplayName()
                    ));
                    $this->leadModel->saveEntity($lead, false);
                    if (isset($company)) {
                        $this->leadModel->addToCompany($lead, $company);
                        $this->em->detach($company);
                    }
                } catch (\Exception $exception) {
                    $this->logger->addWarning($exception->getMessage());

                    return;
                }
            }
        }

        return $lead;
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

        $object         = 'contacts';
        $fieldsToUpdate = $this->getPriorityFieldsForIntegration($config);
        $createFields   = $config['leadFields'];

        //@todo Hubspot's createLead uses createOrUpdate endpoint which means we don't know before we send mapped data if the contact will be updated or created; so we have to send all mapped fields
        $updateFields = array_intersect_key(
            $createFields,
            $fieldsToUpdate
        );

        $readOnlyFields = $this->getReadOnlyFields($object);

        $createFields = array_filter(
            $createFields,
            function ($createField, $key) use ($readOnlyFields) {
                if (!isset($readOnlyFields[$key])) {
                    return $createField;
                }
            },
            ARRAY_FILTER_USE_BOTH
        );

        $mappedData = $this->populateLeadData(
            $lead,
            [
                'leadFields'       => $createFields,
                'object'           => $object,
                'feature_settings' => ['objects' => $config['objects']],
            ]
        );
        $this->amendLeadDataBeforePush($mappedData);

        if (empty($mappedData)) {
            return false;
        }

        if ($this->isAuthorized()) {
            $leadData = $this->getApiHelper()->createLead($mappedData, $lead);

            if (!empty($leadData['vid'])) {
                /** @var IntegrationEntityRepository $integrationEntityRepo */
                $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
                $integrationId         = $integrationEntityRepo->getIntegrationsEntityId($this->getName(), $object, 'lead', $lead->getId());
                $integrationEntity     = (empty($integrationId)) ?
                    $this->createIntegrationEntity(
                        $object,
                        $leadData['vid'],
                        'lead',
                        $lead->getId(),
                        [],
                        false
                    ) : $integrationEntityRepo->getEntity($integrationId[0]['id']);

                $integrationEntity->setLastSyncDate($this->getLastSyncDate());
                $this->getIntegrationEntityRepository()->saveEntity($integrationEntity);
                $this->em->detach($integrationEntity);
            }

            return true;
        }

        return false;
    }

    /**
     * Amend mapped lead data before pushing to CRM.
     *
     * @param $mappedData
     */
    public function amendLeadDataBeforePush(&$mappedData)
    {
        foreach ($mappedData as &$data) {
            $data = str_replace('|', ';', $data);
        }
    }

    /**
     * @param $object
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getReadOnlyFields($object)
    {
        $fields = ArrayHelper::getValue($object, $this->getAvailableLeadFields(), []);

        return array_filter(
            $fields,
            function ($field) {
                if (!empty($field['readOnly'])) {
                    return $field;
                }
            }
        );
    }
}
