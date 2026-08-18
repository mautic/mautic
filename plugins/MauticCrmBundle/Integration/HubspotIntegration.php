<?php

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\EmailBundle\Helper\EmailValidator;
use Mautic\LeadBundle\DataObject\LeadManipulator;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\StagesChangeLog;
use Mautic\StageBundle\Entity\Stage;
use Mautic\StageBundle\Entity\StageRepository;
use MauticPlugin\MauticCrmBundle\Api\HubspotApi;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @method HubspotApi getApiHelper()
 *
 * @extends CrmAbstractIntegration<HubspotApi>
 */
class HubspotIntegration extends CrmAbstractIntegration
{
    private StageRepository $stageRepository;

    protected UserHelper $userHelper;

    private EmailValidator $emailValidator;

    #[Required]
    public function autowireHubspotIntegration(
        StageRepository $stageRepository,
        UserHelper $userHelper,
        EmailValidator $emailValidator,
    ): void {
        $this->stageRepository = $stageRepository;
        $this->userHelper = $userHelper;
        $this->emailValidator = $emailValidator;
    }

    public function getApiHelper(): HubspotApi
    {
        if (empty($this->helper)) {
            $this->helper = new HubspotApi($this, $this->emailValidator);
        }

        return $this->helper;
    }

    public const ACCESS_KEY = 'accessKey';

    public function getName(): string
    {
        return 'Hubspot';
    }

    /**
     * @return array<string, string>
     */
    public function getRequiredKeyFields(): array
    {
        return [];
    }

    public function getApiKey(): string
    {
        return 'hapikey';
    }

    /**
     * Get the array key for the auth token.
     */
    public function getAuthTokenKey(): string
    {
        return 'hapikey';
    }

    public function getSupportedFeatures(): array
    {
        return ['push_lead', 'get_leads'];
    }

    public function getBearerToken(bool $inAuthorization = false): false|string
    {
        $tokenData = $this->getKeys();

        return $tokenData[self::ACCESS_KEY] ?? false;
    }

    /**
     * @return array<string, bool>
     */
    public function getFormSettings(): array
    {
        return [
            'requires_callback'      => false,
            'requires_authorization' => false,
        ];
    }

    public function getAuthenticationType(): string
    {
        return $this->getBearerToken() ? 'oauth2' : 'key';
    }

    public function getApiUrl(): string
    {
        return 'https://api.hubapi.com';
    }

    /**
     * Get if data priority is enabled in the integration or not default is false.
     */
    public function getDataPriority(): bool
    {
        return true;
    }

    /**
     * Get available company fields for choices in the config UI.
     *
     * @return array
     */
    public function getFormCompanyFields(array $settings = [])
    {
        return $this->getFormFieldsByObject('company', $settings);
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields(array $settings = [])
    {
        return $this->getFormFieldsByObject('contacts', $settings);
    }

    /**
     * @return mixed[]
     */
    public function getAvailableLeadFields(array $settings = []): array
    {
        if ($fields = parent::getAvailableLeadFields()) {
            return $fields;
        }

        $hubsFields        = [];
        $silenceExceptions = $settings['silence_exceptions'] ?? true;

        if (isset($settings['feature_settings']['objects'])) {
            $hubspotObjects = $settings['feature_settings']['objects'];
        } else {
            $settings       = $this->settings->getFeatureSettings();
            $hubspotObjects = $settings['objects'] ?? ['contacts'];
        }

        try {
            if ($this->isAuthorized()) {
                if (!empty($hubspotObjects) && is_array($hubspotObjects)) {
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
     * @param array $objects
     *
     * @return array
     */
    protected function cleanPriorityFields(array $fieldsToUpdate, $objects = null)
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
     */
    public function formatLeadDataForCreateOrUpdate($leadData, $lead, $updateLink = false): array
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

    public function isAuthorized(): bool
    {
        $keys = $this->getKeys();

        return isset($keys[$this->getAuthTokenKey()]) || isset($keys[self::ACCESS_KEY]);
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
     * @param FormBuilderInterface $builder
     * @param mixed[]              $data
     * @param string               $formArea
     */
    public function appendToForm(&$builder, $data, $formArea): void
    {
        if ('keys' === $formArea) {
            $builder->add(
                self::ACCESS_KEY,
                TextType::class,
                [
                    'label'       => 'mautic.hubspot.form.accessKey',
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => [
                        'class'    => 'form-control',
                    ],
                    'required'    => false,
                ]
            );

            $builder->add(
                $this->getApiKey(),
                TextType::class,
                [
                    'label'       => 'mautic.hubspot.form.apikey',
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => [
                        'class'    => 'form-control',
                        'readonly' => true,
                    ],
                    'required'    => false,
                ]
            );
        }
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

    public function amendLeadDataBeforeMauticPopulate($data, $object): array
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
                $fields                         = implode('&property=', array_keys($config['leadFields'] ?? []));
                $params['post_append_to_query'] = '&property='.$fields.'&property=lifecyclestage';
                $params['Count']                = 100;

                $data = $this->getApiHelper()->getContacts($params);
                if (isset($data['contacts'])) {
                    foreach ($data['contacts'] as $contact) {
                        if (is_array($contact)) {
                            $contactData = $this->amendLeadDataBeforeMauticPopulate($contact, 'Lead');
                            $contact     = $this->getMauticLead($contactData);
                            if ($contact && !$contact->isNewlyCreated()) { // updated
                                ++$executed[0];
                            } elseif ($contact && $contact->isNewlyCreated()) { // newly created
                                ++$executed[1];
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
     * @param bool $id
     */
    public function getCompanies(array $params = [], $id = false, &$executed = null)
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
                if (isset($data['hasMore']) && $data['hasMore']) {
                    $params['offset'] = $data['offset'];
                    if ($params['offset'] < strtotime($params['start'])) {
                        $this->getCompanies($params, $id, $executed);
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
     * @param mixed|null  $identifiers
     * @param string|null $object
     *
     * @return Lead
     */
    public function getMauticLead($data, $persist = true, $socialCache = null, $identifiers = null, $object = null)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data, true));
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
                $stage = $this->stageRepository->getStageByName($stageName);

                if (null === $stage) {
                    $stage = new Stage();
                    $stage->setName($stageName);
                    $stages[$stageName] = $stage;
                }
                if (!$lead->getStage() && $lead->getStage() != $stage) {
                    $lead->setStage($stage);

                    // add a contact stage change log
                    $log = new StagesChangeLog();
                    $log->setStage($stage);
                    $log->setEventName($stage->getId().':'.$stage->getName());
                    $log->setLead($lead);
                    $log->setActionName(
                        $this->translator->trans(
                            'mautic.stage.import.action.name',
                            [
                                '%name%' => $this->userHelper->getUser()->getUserIdentifier(),
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
                    $this->logger->warning($exception->getMessage());

                    return;
                }
            }
        }

        return $lead;
    }

    /**
     * @param Lead  $lead
     * @param array $config
     */
    public function pushLead($lead, $config = []): array|bool
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return [];
        }

        $object         = 'contacts';
        $createFields   = $config['leadFields'];

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
                $integrationId     = $this->integrationEntityRepository->getIntegrationsEntityId($this->getName(), $object, 'lead', $lead->getId());
                $integrationEntity = ([] === $integrationId) ?
                    $this->createIntegrationEntity(
                        $object,
                        $leadData['vid'],
                        'lead',
                        $lead->getId(),
                        [],
                        false
                    ) : $this->integrationEntityRepository->getEntity($integrationId[0]['id']);

                $integrationEntity->setLastSyncDate($this->getLastSyncDate());
                $this->integrationEntityRepository->saveEntity($integrationEntity);
                $this->em->detach($integrationEntity);
            }

            return true;
        }

        return false;
    }

    /**
     * Amend mapped lead data before pushing to CRM.
     */
    public function amendLeadDataBeforePush(&$mappedData): void
    {
        foreach ($mappedData as &$data) {
            $data = str_replace('|', ';', $data);
        }
    }

    /**
     * @throws \Exception
     */
    private function getReadOnlyFields(string $object): array
    {
        $fields = ArrayHelper::getValue($object, $this->getAvailableLeadFields(), []);

        return array_filter(
            $fields,
            fn (array $field): bool => !empty($field['readOnly'])
        );
    }
}
