<?php

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Integration\IntegrationObject;
use MauticPlugin\MauticCrmBundle\Form\Type\IntegrationCampaignsTaskType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilder;

/**
 * @method \MauticPlugin\MauticCrmBundle\Api\ConnectwiseApi getApiHelper()
 */
class ConnectwiseIntegration extends CrmAbstractIntegration
{
    public const PAGESIZE = 200;

    public function getName(): string
    {
        return 'Connectwise';
    }

    public function getSupportedFeatures(): array
    {
        return ['push_lead', 'get_leads'];
    }

    /**
     * @return array<string, string>
     */
    public function getRequiredKeyFields(): array
    {
        return [
            'username'  => 'mautic.connectwise.form.integrator',
            'password'  => 'mautic.connectwise.form.privatekey',
            'site'      => 'mautic.connectwise.form.site',
            'appcookie' => 'mautic.connectwise.form.cookie',
        ];
    }

    /**
     * Get the array key for application cookie.
     */
    public function getCompanyCookieKey(): string
    {
        return 'appcookie';
    }

    /**
     * Get the array key for companyid.
     */
    public function getCompanyIdKey(): string
    {
        return 'companyid';
    }

    /**
     * Get the array key for client id.
     */
    public function getIntegrator(): string
    {
        return 'username';
    }

    /**
     * Get the array key for client id.
     */
    public function getConnectwiseUrl(): string
    {
        return 'site';
    }

    /**
     * Get the array key for client secret.
     */
    public function getClientSecretKey(): string
    {
        return 'password';
    }

    public function getSecretKeys(): array
    {
        return [
            'password',
        ];
    }

    public function getApiUrl(): string
    {
        return sprintf('%s/v4_6_release/apis/3.0', $this->keys['site']);
    }

    /**
     * @return string
     */
    public function getAuthLoginUrl()
    {
        return $this->router->generate('mautic_integration_auth_callback', ['integration' => $this->getName()]);
    }

    /**
     * @return bool
     */
    public function authCallback($settings = [], $parameters = [])
    {
        $url   = $this->getApiUrl();
        $error = false;
        try {
            $response = $this->makeRequest($url.'/system/members/', $parameters, 'GET', $settings);

            foreach ($response as $key => $r) {
                $key = preg_replace('/[\r\n]+/', '', $key);
                switch ($key) {
                    case '<!DOCTYPE_html_PUBLIC_"-//W3C//DTD_XHTML_1_0_Strict//EN"_"http://www_w3_org/TR/xhtml1/DTD/xhtml1-strict_dtd"><html_xmlns':
                        $error = '404 not found error';
                        break;
                    case 'code':
                        $error = $response['message'].' '.$r;
                        break;
                }
            }
            if (!$error) {
                $data = ['username' => $this->keys['username'], 'password' => $this->keys['password']];
                $this->extractAuthKeys($data, 'username');
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return $error;
    }

    public function getAuthenticationType(): string
    {
        return 'basic';
    }

    public function getDataPriority(): bool
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
        return $this->getFormFieldsByObject('Contact', $settings);
    }

    /**
     * @return mixed[]
     */
    public function getAvailableLeadFields($settings = []): array
    {
        $cwFields = [];
        if (isset($settings['feature_settings']['objects'])) {
            $cwObjects = $settings['feature_settings']['objects'];
        } else {
            $cwObjects['Contact'] = 'Contact';
        }
        if (!$this->isAuthorized()) {
            return [];
        }
        switch ($cwObjects) {
            case isset($cwObjects['Contact']):
                $contactFields = $this->getContactFields();

                $cwFields['Contact'] = $this->setFields($contactFields);
                break;
            case isset($cwObjects['company']):
                $company             = $this->getCompanyFields();
                $cwFields['company'] = $this->setFields($company);
                break;
        }

        return $cwFields;
    }

    public function setFields($fields): array
    {
        $cwFields = [];

        foreach ($fields as $fieldName => $field) {
            if (in_array($field['type'], ['string', 'boolean', 'ref'])) {
                $cwFields[$fieldName] = [
                    'type'     => $field['type'],
                    'label'    => ucfirst($fieldName),
                    'required' => $field['required'],
                ];
            }
        }

        return $cwFields;
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea): void
    {
        if ('features' == $formArea) {
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
                        'mautic.connectwise.object.contact' => 'Contact',
                        'mautic.connectwise.object.company' => 'company',
                    ],
                    'expanded'          => true,
                    'multiple'          => true,
                    'label'             => 'mautic.connectwise.form.objects_to_pull_from',
                    'label_attr'        => ['class' => ''],
                    'placeholder'       => false,
                    'required'          => false,
                ]
            );
        }

        if ('integration' == $formArea) {
            if ($this->isAuthorized()) {
                $builder->add(
                    'push_activities',
                    YesNoButtonGroupType::class,
                    [
                        'label'      => 'mautic.plugin.config.push.activities',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control',
                        ],
                        'data'     => (!isset($data['push_activities'])) ? true : $data['push_activities'],
                        'required' => false,
                    ]
                );

                $builder->add(
                    'campaign_task',
                    IntegrationCampaignsTaskType::class,
                    [
                        'label' => false,
                        'attr'  => [
                            'data-hide-on' => '{"campaignevent_properties_config_push_activities_0":"checked"}',
                        ],
                        'data' => $data['campaign_task'] ?? [],
                    ]);
            }
        }
    }

    /**
     * @return array of company fields for connectwise
     */
    public function getCompanyFields(): array
    {
        return [
            'identifier'            => ['type' => 'string', 'required' => true],
            'name'                  => ['type' => 'string', 'required' => true],
            'addressLine1'          => ['type' => 'string', 'required' => false],
            'addressLine2'          => ['type' => 'string', 'required' => false],
            'city'                  => ['type' => 'string', 'required' => false],
            'state'                 => ['type' => 'string', 'required' => false],
            'zip'                   => ['type' => 'string', 'required' => false],
            'phoneNumber'           => ['type' => 'string', 'required' => false],
            'faxNumber'             => ['type' => 'string', 'required' => false],
            'website'               => ['type' => 'string', 'required' => false],
            'territoryId'           => ['type' => 'string', 'required' => false],
            'marketId'              => ['type' => 'string', 'required' => false],
            'accountNumber'         => ['type' => 'string', 'required' => false],
            'dateAcquired'          => ['type' => 'string', 'required' => false],
            'annualRevenue'         => ['type' => 'string', 'required' => false],
            'numberOfEmployees'     => ['type' => 'string', 'required' => false],
            'leadSource'            => ['type' => 'string', 'required' => false],
            'leadFlag'              => ['type' => 'boolean', 'required' => false],
            'unsubscribeFlag'       => ['type' => 'boolean', 'required' => false],
            'calendarId'            => ['type' => 'string', 'required' => false],
            'userDefinedField1'     => ['type' => 'string', 'required' => false],
            'userDefinedField2'     => ['type' => 'string', 'required' => false],
            'userDefinedField3'     => ['type' => 'string', 'required' => false],
            'userDefinedField4'     => ['type' => 'string', 'required' => false],
            'userDefinedField5'     => ['type' => 'string', 'required' => false],
            'userDefinedField6'     => ['type' => 'string', 'required' => false],
            'userDefinedField7'     => ['type' => 'string', 'required' => false],
            'userDefinedField8'     => ['type' => 'string', 'required' => false],
            'userDefinedField9'     => ['type' => 'string', 'required' => false],
            'userDefinedField10'    => ['type' => 'string', 'required' => false],
            'vendorIdentifier'      => ['type' => 'string', 'required' => false],
            'taxIdentifier'         => ['type' => 'string', 'required' => false],
            'invoiceToEmailAddress' => ['type' => 'string', 'required' => false],
            'invoiceCCEmailAddress' => ['type' => 'string', 'required' => false],
            'deletedFlag'           => ['type' => 'boolean', 'required' => false],
            'dateDeleted'           => ['type' => 'string', 'required' => false],
            'deletedBy'             => ['type' => 'string', 'required' => false],
            // todo 'customFields' => 'array',
        ];
    }

    /**
     * @return array of contact fields for connectwise
     */
    public function getContactFields(): array
    {
        return [
            'firstName'              => ['type' => 'string', 'required' => true],
            'lastName'               => ['type' => 'string', 'required' => false],
            'type'                   => ['type' => 'string', 'required' => false],
            'company'                => ['type' => 'ref', 'required' => false, 'value' => 'name'],
            'addressLine1'           => ['type' => 'string', 'required' => false],
            'addressLine2'           => ['type' => 'string', 'required' => false],
            'city'                   => ['type' => 'string', 'required' => false],
            'state'                  => ['type' => 'string', 'required' => false],
            'zip'                    => ['type' => 'string', 'required' => false],
            'country'                => ['type' => 'string', 'required' => false],
            'inactiveFlag'           => ['type' => 'string', 'required' => false],
            'securityIdentifier'     => ['type' => 'string', 'required' => false],
            'managerContactId'       => ['type' => 'string', 'required' => false],
            'assistantContactId'     => ['type' => 'string', 'required' => false],
            'title'                  => ['type' => 'string', 'required' => false],
            'school'                 => ['type' => 'string', 'required' => false],
            'nickName'               => ['type' => 'string', 'required' => false],
            'marriedFlag'            => ['type' => 'boolean', 'required' => false],
            'childrenFlag'           => ['type' => 'boolean', 'required' => false],
            'significantOther'       => ['type' => 'string', 'required' => false],
            'portalPassword'         => ['type' => 'string', 'required' => false],
            'portalSecurityLevel'    => ['type' => 'string', 'required' => false],
            'disablePortalLoginFlag' => ['type' => 'boolean', 'required' => false],
            'unsubscribeFlag'        => ['type' => 'boolean', 'required' => false],
            'userDefinedField1'      => ['type' => 'string', 'required' => false],
            'userDefinedField2'      => ['type' => 'string', 'required' => false],
            'userDefinedField3'      => ['type' => 'string', 'required' => false],
            'userDefinedField4'      => ['type' => 'string', 'required' => false],
            'userDefinedField5'      => ['type' => 'string', 'required' => false],
            'userDefinedField6'      => ['type' => 'string', 'required' => false],
            'userDefinedField7'      => ['type' => 'string', 'required' => false],
            'userDefinedField8'      => ['type' => 'string', 'required' => false],
            'userDefinedField9'      => ['type' => 'string', 'required' => false],
            'userDefinedField10'     => ['type' => 'string', 'required' => false],
            'gender'                 => ['type' => 'string', 'required' => false],
            'birthDay'               => ['type' => 'string', 'required' => false],
            'anniversary'            => ['type' => 'string', 'required' => false],
            'presence'               => ['type' => 'string', 'required' => false],
            'mobileGuid'             => ['type' => 'string', 'required' => false],
            'facebookUrl'            => ['type' => 'string', 'required' => false],
            'twitterUrl'             => ['type' => 'string', 'required' => false],
            'linkedInUrl'            => ['type' => 'string', 'required' => false],
            'defaultBillingFlag'     => ['type' => 'boolean', 'required' => false],
            'communicationItems'     => [
                'type'     => 'array',
                'required' => false,
                'items'    => [
                    'name'  => ['type' => 'name'],
                    'value' => 'value',
                    'keys'  => ['Email', 'Direct', 'Fax', 'Cell'],
                ],
            ],
            'Direct'                 => ['type' => 'string', 'required' => false, 'configOnly' => true],
            'Cell'                   => ['type' => 'string', 'required' => false, 'configOnly' => true],
            'Email'                  => ['type' => 'string', 'required' => true, 'configOnly' => true],
            'Fax'                    => ['type' => 'string', 'required' => false, 'configOnly' => true],
        ];
    }

    /**
     * Get Contacts from connectwise.
     *
     * @param array $params
     */
    public function getLeads($params = [], $query = null, &$executed = null, $result = [], $object = 'Contact'): int
    {
        return $this->getRecords($params, $object);
    }

    /**
     * Get Companies from connectwise.
     */
    public function getCompanies(array $params = []): int
    {
        return $this->getRecords($params, 'company');
    }

    public function getRecords($params, $object): int
    {
        if (!$this->isAuthorized()) {
            return 0;
        }

        $page                = 1;
        $executed            = 0;
        $integrationEntities = [];
        try {
            while ($records = ('Contact' == $object)
                ? $this->getApiHelper()->getContacts($params, $page)
                : $this->getApiHelper()->getCompanies($params, $page)) {
                $mauticReferenceObject = ('Contact' == $object) ? 'lead' : 'company';
                foreach ($records as $record) {
                    if (is_array($record)) {
                        $id            = $record['id'];
                        $formattedData = $this->amendLeadDataBeforeMauticPopulate($record, $object);
                        $entity        = ('Contact' == $object)
                            ? $this->getMauticLead($formattedData)
                            : $this->getMauticCompany(
                                $formattedData,
                                'company'
                            );
                        if ($entity) {
                            $integrationEntities[] = $this->saveSyncedData($entity, $object, $mauticReferenceObject, $id);
                            $this->em->detach($entity);
                            unset($entity);
                            ++$executed;
                        }
                    }
                }

                if ($integrationEntities) {
                    $this->em->getRepository(\Mautic\PluginBundle\Entity\IntegrationEntity::class)->saveEntities($integrationEntities);
                    $this->integrationEntityModel->getRepository()->detachEntities($integrationEntities);
                }

                // No use checking the next page if there are less records than the requested page size

                if (count($records) < self::PAGESIZE) {
                    break;
                }

                ++$page;
            }

            return $executed;
        } catch (\Exception $e) {
            if (404 !== $e->getCode()) {
                $this->logIntegrationError($e);
            }
        }

        return $executed;
    }

    /**
     * Ammend mapped lead data before creating to Mautic.
     *
     * @return mixed[]
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object): array
    {
        $fieldsValues = [];

        if (empty($data)) {
            return $fieldsValues;
        }
        if ('Contact' == $object) {
            $fields = $this->getContactFields();
        } else {
            $fields = $this->getCompanyFields();
        }

        foreach ($data as $key => $field) {
            if (isset($fields[$key])) {
                $name = $key;
                if ('array' == $fields[$key]['type']) {
                    $items = $fields[$key]['items'];
                    foreach ($field as $item) {
                        if (is_array($item[key($items['name'])])) {
                            foreach ($item[key($items['name'])] as $nameKey => $nameField) {
                                if ($nameKey == $items['name'][key($items['name'])]) {
                                    $name = $nameField;
                                }
                            }
                        }
                        $fieldsValues[$name] = $item[$items['value']];
                    }
                } elseif ('ref' == $fields[$key]['type']) {
                    $fieldsValues[$name] = $field[$fields[$key]['value']];
                } else {
                    $fieldsValues[$name] = $field;
                }
            }
        }
        if (isset($data['id'])) {
            $fieldsValues['id'] = $data['id'];
        }

        return $fieldsValues;
    }

    public function saveSyncedData($entity, $object, $mauticObjectReference, $integrationEntityId): IntegrationEntity
    {
        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository(\Mautic\PluginBundle\Entity\IntegrationEntity::class);
        $integrationEntities   = $integrationEntityRepo->getIntegrationEntities(
            $this->getName(),
            $object,
            $mauticObjectReference,
            [$entity->getId()]
        );

        if ($integrationEntities) {
            $integrationEntity = reset($integrationEntities);
            $integrationEntity->setLastSyncDate(new \DateTime());
        } else {
            $integrationEntity = new IntegrationEntity();
            $integrationEntity->setDateAdded(new \DateTime());
            $integrationEntity->setIntegration($this->getName());
            $integrationEntity->setIntegrationEntity($object);
            $integrationEntity->setIntegrationEntityId($integrationEntityId);
            $integrationEntity->setInternalEntity($mauticObjectReference);
            $integrationEntity->setInternalEntityId($entity->getId());
        }

        return $integrationEntity;
    }

    /**
     * @param array|Lead $lead
     * @param array      $config
     *
     * @throws ApiErrorException
     */
    public function pushLead($lead, $config = []): bool
    {
        $config      = $this->mergeConfigToFeatureSettings($config);
        $personFound = false;
        $leadPushed  = false;
        $object      = 'Contact';

        if (empty($config['leadFields']) || !$lead->getEmail()) {
            return $leadPushed;
        }

        // findLead first
        $cwContactExists = $this->getApiHelper()->getContacts(['Email' => $lead->getEmail()]);

        if (!empty($cwContactExists)) {
            $personFound = true;
        }

        $personData = [];

        try {
            if ($personFound) {
                foreach ($cwContactExists as $cwContact) { // go through array of contacts found since Connectwise lets you duplicate records with same email address
                    $mappedData = $this->getMappedFields($object, $lead, $personFound, $config, $cwContact);

                    if (!empty($mappedData)) {
                        $personData = $this->getApiHelper()->updateContact($mappedData, $cwContact['id']);
                    } else {
                        $personData['id'] = $cwContact['id'];
                    }
                }
            } else {
                $mappedData = $this->getMappedFields($object, $lead, $personFound, $config);
                $personData = $this->getApiHelper()->createContact($mappedData);
            }

            if (!empty($personData['id'])) {
                $id                    = $personData['id'];
                $integrationEntities[] = $this->saveSyncedData($lead, $object, 'lead', $id);

                if (isset($config['push_activities']) and true == $config['push_activities']) {
                    $savedEntity = $this->createActivity($config['campaign_task'], $id, $lead->getId());
                    if ($savedEntity instanceof IntegrationEntity) {
                        $integrationEntities[] = $savedEntity;
                    }
                }

                $this->em->getRepository(\Mautic\PluginBundle\Entity\IntegrationEntity::class)->saveEntities($integrationEntities);
                $this->integrationEntityModel->getRepository()->detachEntities($integrationEntities);

                $leadPushed = true;
            }
        } catch (\Exception $e) {
            if ($e instanceof ApiErrorException) {
                $e->setContact($lead);
            }
            $this->logIntegrationError($e);
        }

        return $leadPushed;
    }

    public function getMappedFields($object, $lead, $personFound, $config, $cwContactData = []): array
    {
        $fieldsToUpdateInCW = isset($config['update_mautic']) && $personFound ? array_keys($config['update_mautic'], 1) : [];
        $objectFields       = $this->prepareFieldsForPush($this->getContactFields());
        $leadFields         = $config['leadFields'];

        $cwContactExists = $this->amendLeadDataBeforeMauticPopulate($cwContactData, $object);

        $communicationItems = $cwContactData['communicationItems'] ?? [];

        $leadFields = array_diff_key($leadFields, array_flip($fieldsToUpdateInCW));
        $leadFields = $this->getBlankFieldsToUpdate($leadFields, $cwContactExists, $objectFields, $config);

        return $this->populateLeadData(
            $lead,
            [
                'leadFields'       => $leadFields,
                'object'           => 'Contact',
                'feature_settings' => [
                    'objects' => $config['objects'],
                ],
                'update'             => $personFound,
                'communicationItems' => $communicationItems,
            ]
        );
    }

    /**
     * Match lead data with integration fields.
     */
    public function populateLeadData($lead, $config = []): array
    {
        if ($lead instanceof Lead) {
            $fields = $lead->getFields(true);
        } else {
            $fields = $lead;
        }

        $leadFields = $config['leadFields'];
        if (empty($leadFields)) {
            return [];
        }
        $availableFields = $this->getContactFields();
        $unknown         = $this->translator->trans('mautic.integration.form.lead.unknown');
        $matched         = [];

        foreach ($availableFields as $key => $field) {
            $integrationKey = $matchIntegrationKey = $key;

            if (isset($field['configOnly'])) {
                continue;
            }

            if ('communicationItems' == $integrationKey) {
                $communicationItems = [];
                foreach ($field['items']['keys'] as $keyItem => $item) {
                    $defaultValue = [];
                    $keyExists    = false;
                    if (isset($leadFields[$item])) {
                        if ('Email' == $item) {
                            $defaultValue = ['defaultFlag' => true];
                        }
                        $mauticKey = $leadFields[$item];
                        if (isset($fields[$mauticKey]) && !empty($fields[$mauticKey]['value'])) {
                            foreach ($config['communicationItems'] as $key => $ci) {
                                if ($ci['type']['id'] == $keyItem + 1) {
                                    $config['communicationItems'][$key]['value'] = $fields[$mauticKey]['value'];
                                    $keyExists                                   = true;
                                }
                            }
                            if (!$keyExists) {
                                $type = [
                                    'type' => ['id' => $keyItem + 1, 'name' => $item], ];
                                $values = array_merge(['value' => $this->cleanPushData($fields[$mauticKey]['value'])], $defaultValue);

                                $communicationItems[] = array_merge($type, $values);
                            }
                        }
                    }
                }

                if ($config['update']) {
                    $communicationItems = array_merge($config['communicationItems'], $communicationItems);
                }
                if (!empty($communicationItems)) {
                    $matched[$integrationKey] = $communicationItems;
                }
            }

            if ('company' === $integrationKey && !empty($fields['company']['value'])) {
                try {
                    $foundCompanies = $this->getApiHelper()->getCompanies([
                        'conditions' => [
                            sprintf('Name = "%s"', $fields['company']['value']),
                        ],
                    ]);

                    $matched['company'] = ['identifier' => $foundCompanies[0]['identifier']];
                } catch (ApiErrorException) {
                    // No matching companies were found
                }

                continue;
            }

            if (isset($leadFields[$integrationKey])) {
                $mauticKey = $leadFields[$integrationKey];
                if (isset($fields[$mauticKey]) && !empty($fields[$mauticKey]['value'])) {
                    $matched[$matchIntegrationKey] = $this->cleanPushData($fields[$mauticKey]['value']);
                }
            }

            if (!empty($field['required']) && empty($matched[$matchIntegrationKey]) && !$config['update']) {
                $matched[$matchIntegrationKey] = $unknown;
            }
        }

        if ($config['update']) {
            $updateFields = [];
            foreach ($matched as $key => $field) {
                $updateFields[] = [
                    'op'    => 'replace',
                    'path'  => $key,
                    'value' => $field,
                ];
            }
            $matched = $updateFields;
        }

        return $matched;
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
        if (isset($fieldsToUpdate['leadFields']) && is_array($objects)) {
            // Pass in the whole config
            $fields = $fieldsToUpdate['leadFields'];
        } else {
            $fields = array_flip($fieldsToUpdate);
        }

        return $this->prepareFieldsForSync($fields, $fieldsToUpdate, $objects);
    }

    /**
     * @param string $priorityObject
     *
     * @return mixed
     */
    protected function getPriorityFieldsForMautic($config, $object = null, $priorityObject = 'mautic')
    {
        if ('company' == $object) {
            $priority = parent::getPriorityFieldsForMautic($config, $object, 'mautic_company');
            $fields   = array_intersect_key($config['companyFields'], $priority);
        } else {
            $fields = parent::getPriorityFieldsForMautic($config, $object, $priorityObject);
        }

        return ($object && isset($fields[$object])) ? $fields[$object] : $fields;
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
     * @throws \Exception
     */
    public function getCampaignChoices(): array
    {
        $choices   = [];
        $campaigns = $this->getCampaigns();

        foreach ($campaigns as $campaign) {
            if (isset($campaign['id'])) {
                $choices[] = [
                    'value' => $campaign['id'],
                    'label' => $campaign['name'],
                ];
            }
        }

        return $choices;
    }

    public function getCampaignMembers($campaignId): bool
    {
        if (!$this->isAuthorized()) {
            return false;
        }

        try {
            $page = 1;
            while ($campaignsMembersResults = $this->getApiHelper()->getCampaignMembers($campaignId, $page)) {
                $campaignMemberObject = new IntegrationObject('CampaignMember', 'lead');
                $recordList           = $this->getRecordList($campaignsMembersResults, 'id');
                $contacts             = (array) $this->integrationEntityModel->getSyncedRecords(new IntegrationObject('Contact', 'lead'), $this->getName(), $recordList);

                $existingContactsIds = array_column(array_filter(
                    $contacts,
                    fn ($contact): bool => 'lead' === $contact['internal_entity']
                ), 'integration_entity_id');

                $contactsToFetch = array_diff_key($recordList, array_flip($existingContactsIds));

                if (!empty($contactsToFetch)) {
                    $listOfContactsToFetch = implode(',', array_keys($contactsToFetch));
                    $params['Ids']         = $listOfContactsToFetch;

                    $this->getLeads($params);
                }

                $saveCampaignMembers = array_merge($existingContactsIds, array_keys($contactsToFetch));

                $this->saveCampaignMembers($saveCampaignMembers, $campaignMemberObject, $campaignId);

                if (count($campaignsMembersResults) < self::PAGESIZE) {
                    // No use continuing as we have less results than page size
                    break;
                }

                ++$page;
            }

            return true;
        } catch (\Exception $e) {
            if (404 !== $e->getCode()) {
                $this->logIntegrationError($e);
            }
        }

        return false;
    }

    public function saveCampaignMembers($allCampaignMembers, $campaignMemberObject, $campaignId): void
    {
        if (empty($allCampaignMembers)) {
            return;
        }
        $persistEntities = [];
        $recordList      = $this->getRecordList($allCampaignMembers);
        $mauticObject    = new IntegrationObject('Contact', 'lead');

        $contacts = $this->integrationEntityModel->getSyncedRecords($mauticObject, $this->getName(), $recordList);
        // first find existing campaign members.
        foreach ($contacts as $contact) {
            $existingCampaignMember = $this->integrationEntityModel->getSyncedRecords($campaignMemberObject, $this->getName(), $campaignId, $contact['internal_entity_id']);
            if (empty($existingCampaignMember)) {
                $persistEntities[] = $this->createIntegrationEntity(
                    $campaignMemberObject->getType(),
                    $campaignId,
                    $campaignMemberObject->getInternalType(),
                    $contact['internal_entity_id'],
                    [],
                    false
                );
            }
        }

        if ($persistEntities) {
            $this->em->getRepository(\Mautic\PluginBundle\Entity\IntegrationEntity::class)->saveEntities($persistEntities);
            $this->integrationEntityModel->getRepository()->detachEntities($persistEntities);
            unset($persistEntities);
        }
    }

    public function getRecordList($records, $index = null): array
    {
        $recordList = [];

        foreach ($records as $record) {
            if ($index && isset($record[$index])) {
                $record = $record[$index];
            }

            $recordList[$record] = [
                'id' => $record,
            ];
        }

        return $recordList;
    }

    /**
     * @throws ApiErrorException
     */
    public function getActivityTypes(): array
    {
        $activities   = [];
        $cwActivities = $this->getApiHelper()->getActivityTypes();

        foreach ($cwActivities as $cwActivity) {
            if (isset($cwActivity['id'])) {
                $activities[$cwActivity['id']] = $cwActivity['name'];
            }
        }

        return $activities;
    }

    /**
     * @throws ApiErrorException
     */
    public function getMembers(): array
    {
        $members   = [];
        $cwMembers = $this->getApiHelper()->getMembers();
        foreach ($cwMembers as $cwMember) {
            if (isset($cwMember['id'])) {
                $members[$cwMember['id']] = $cwMember['identifier'];
            }
        }

        return $members;
    }

    /**
     * @throws ApiErrorException
     */
    public function createActivity($config, $cwContactId, $leadId): ?IntegrationEntity
    {
        if ($cwContactId and !empty($config['activity_name'])) {
            $activity = [
                'name'     => $config['activity_name'],
                'type'     => ['id' => $config['campaign_activity_type']],
                'assignTo' => ['id' => $config['campaign_members']],
                'contact'  => ['id' => $cwContactId],
            ];
            $activities = $this->getApiHelper()->postActivity($activity);

            if (isset($activities['id'])) {
                return $this->createIntegrationEntity('Activities', $activities['id'], 'lead', $leadId, null, false);
            }
        }

        return null;
    }
}
