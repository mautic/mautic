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

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Mautic\PluginBundle\Exception\ApiErrorException;
use Mautic\PluginBundle\Integration\IntegrationObject;
use Symfony\Component\Form\FormBuilder;

/**
 * Class ConnectwiseIntegration.
 */
class ConnectwiseIntegration extends CrmAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Connectwise';
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
     * @return array
     */
    public function getRequiredKeyFields()
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
     *
     * @return string
     */
    public function getCompanyCookieKey()
    {
        return 'appcookie';
    }

    /**
     * Get the array key for companyid.
     *
     * @return string
     */
    public function getCompanyIdKey()
    {
        return 'companyid';
    }

    /**
     * Get the array key for client id.
     *
     * @return string
     */
    public function getIntegrator()
    {
        return 'username';
    }

    /**
     * Get the array key for client id.
     *
     * @return string
     */
    public function getConnectwiseUrl()
    {
        return 'site';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'password';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSecretKeys()
    {
        return [
            'password',
        ];
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return sprintf('%s/v4_6_release/apis/3.0', $this->keys['site']);
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

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'basic';
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
     */
    public function getFormLeadFields($settings = [])
    {
        return $this->getFormFieldsByObject('Contact', $settings);
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = [])
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

    /**
     * @param $fields
     *
     * @return array
     */
    public function setFields($fields)
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
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
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
                        'Contact' => 'mautic.connectwise.object.contact',
                        'company' => 'mautic.connectwise.object.company',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.connectwise.form.objects_to_pull_from',
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
        }

        if ($formArea == 'integration') {
            if ($this->isAuthorized()) {
                $builder->add(
                    'push_activities',
                    'yesno_button_group',
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
                        'integration_campaign_task',
                        [
                            'label'  => false,
                            'helper' => $this->factory->getHelper('integration'),
                            'attr'   => [
                                'data-hide-on' => '{"campaignevent_properties_config_push_activities_0":"checked"}',
                            ],
                            'data' => (isset($data['campaign_task'])) ? $data['campaign_task'] : [],
                        ]);
            }
        }
    }

    /**
     * @return array of company fields for connectwise
     */
    public function getCompanyFields()
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
            //todo 'customFields' => 'array',
        ];
    }

    /**
     * @return array of contact fields for connectwise
     */
    public function getContactFields()
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
     * @param null  $query
     */
    public function getLeads($params = [], $query = null, &$executed = null, $result = [], $object = 'Contact')
    {
        return $this->getRecords($params, $object);
    }

    /**
     * Get Companies from connectwise.
     *
     * @param array $params
     * @param null  $query
     */
    public function getCompanies(array $params = [])
    {
        return $this->getRecords($params, 'company');
    }

    /**
     * @param $params
     * @param $object
     */
    public function getRecords($params, $object)
    {
        //todo data priority
        $executed            = null;
        $integrationEntities = [];
        try {
            if ($this->isAuthorized()) {
                $data = ($object == 'Contact')
                    ? $this->getApiHelper()->getContacts($params)
                    : $this->getApiHelper()->getCompanies(
                        $params
                    );
                $mauticReferenceObject = ($object == 'Contact') ? 'lead' : 'company';
                if (!empty($data)) {
                    foreach ($data as $record) {
                        if (is_array($record)) {
                            $id            = $record['id'];
                            $formattedData = $this->amendLeadDataBeforeMauticPopulate($record, $object);
                            $entity        = ($object == 'Contact')
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
                }
                if ($integrationEntities) {
                    $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($integrationEntities);
                    $this->em->clear('Mautic\PluginBundle\Entity\IntegrationEntity');
                }

                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    /**
     * Ammend mapped lead data before creating to Mautic.
     *
     * @param $data
     * @param $object
     */
    public function amendLeadDataBeforeMauticPopulate($data, $object)
    {
        $fieldsValues = [];

        if (empty($data)) {
            return $fieldsValues;
        }
        if ($object == 'Contact') {
            $fields = $this->getContactFields();
        } else {
            $fields = $this->getCompanyFields();
        }

        foreach ($data as $key => $field) {
            if (isset($fields[$key])) {
                $name = $key;
                if ($fields[$key]['type'] == 'array') {
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
                } elseif ($fields[$key]['type'] == 'ref') {
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

    /**
     * @param $entity
     * @param $object
     * @param $mauticObjectReference
     * @param $integrationEntityId
     *
     * @return IntegrationEntity|null|object
     */
    public function saveSyncedData($entity, $object, $mauticObjectReference, $integrationEntityId)
    {
        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
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
     * @param \Mautic\LeadBundle\Entity\Lead $lead
     * @param array                          $config
     *
     * @return array|bool
     */
    public function pushLead($lead, $config = [])
    {
        $config      = $this->mergeConfigToFeatureSettings($config);
        $personFound = false;
        $leadPushed  = false;
        $object      = 'Contact';

        if (empty($config['leadFields']) || !$lead->getEmail()) {
            return $leadPushed;
        }

        //findLead first
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

                if (isset($config['push_activities']) and $config['push_activities'] == true) {
                    $savedEntities = $this->createActivity($config['campaign_task'], $id, $lead->getId());
                    if ($savedEntities) {
                        $integrationEntities[] = $savedEntities;
                    }
                }

                if (!empty($integrationEntities)) {
                    $this->em->getRepository('MauticPluginBundle:IntegrationEntity')->saveEntities($integrationEntities);
                    $this->em->clear('Mautic\PluginBundle\Entity\IntegrationEntity');
                }

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

    /**
     * @param $cwContactData
     * @param $object
     * @param $lead
     * @param $personFound
     * @param $config
     *
     * @return array
     */
    public function getMappedFields($object, $lead, $personFound, $config, $cwContactData = [])
    {
        $fieldsToUpdateInCW = isset($config['update_mautic']) && $personFound ? array_keys($config['update_mautic'], 1) : [];
        $objectFields       = $this->prepareFieldsForPush($this->getContactFields());
        $leadFields         = $config['leadFields'];

        $cwContactExists = $this->amendLeadDataBeforeMauticPopulate($cwContactData, $object);

        $communicationItems = isset($cwContactData['communicationItems']) ? $cwContactData['communicationItems'] : [];

        $leadFields = array_diff_key($leadFields, array_flip($fieldsToUpdateInCW));
        $leadFields = $this->getBlankFieldsToUpdate($leadFields, $cwContactExists, $objectFields, $config);
        $mappedData = $this->populateLeadData(
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

        // @todo map company reference
        unset($mappedData['company']);

        return $mappedData;
    }

    /**
     * Match lead data with integration fields.
     *
     * @param $lead
     * @param $config
     *
     * @return array
     */
    public function populateLeadData($lead, $config = [])
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

            if ($integrationKey == 'communicationItems') {
                $communicationItems = [];
                foreach ($field['items']['keys'] as $keyItem => $item) {
                    $defaulValue = [];
                    $keyExists   = false;
                    if (isset($leadFields[$item])) {
                        if ($item == 'Email') {
                            $defaulValue = ['defaultFlag' => true];
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
                                $values = array_merge(['value' => $this->cleanPushData($fields[$mauticKey]['value'])], $defaulValue);

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
        if (isset($fieldsToUpdate['leadFields']) && is_array($objects)) {
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
     * @return array
     */
    public function getCampaignChoices()
    {
        $choices   = [];
        $campaigns = $this->getCampaigns();

        if (!empty($campaigns)) {
            foreach ($campaigns as $campaign) {
                if (isset($campaign['id'])) {
                    $choices[] = [
                        'value' => $campaign['id'],
                        'label' => $campaign['name'],
                    ];
                }
            }
        }

        return $choices;
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

        $campaignsMembersResults = [];
        $allCampaignMembers      = [];

        try {
            $campaignsMembersResults = $this->getApiHelper()->getCampaignMembers($campaignId);
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
            if (!$silenceExceptions) {
                throw $e;
            }
        }

        if (empty($campaignsMembersResults)) {
            return false;
        }

        $campaignMemberObject = new IntegrationObject('CampaignMember', 'lead');
        $recordList           = $this->getRecordList($campaignsMembersResults, 'id');

        $contacts = $this->integrationEntityModel->getSyncedRecords($campaignMemberObject, $this->getName(), $recordList);

        $existingContactsIds = array_map(
            function ($contact) {
                return ($contact['integration_entity'] == 'Contact') ? $contact['integration_entity_id'] : [];
            },
            $contacts
        );

        $contactList = $this->getRecordList($campaignsMembersResults, 'id');

        $contactsToFetch = array_diff_key($contactList, $existingContactsIds);

        if (!empty($contactsToFetch)) {
            $listOfContactsToFetch = implode(',', array_keys($contactsToFetch));
            $params['Ids']         = $listOfContactsToFetch;

            $this->getLeads($params);

            $allCampaignMembers = array_merge($existingContactsIds, array_keys($contactsToFetch));
        }

        $this->saveCampaignMembers($allCampaignMembers, $campaignMemberObject, $campaignId);
    }

    /**
     * @param $allCampaignMembers
     * @param $campaignMemberObject
     * @param $campaignId
     */
    public function saveCampaignMembers($allCampaignMembers, $campaignMemberObject, $campaignId)
    {
        if (empty($allCampaignMembers)) {
            return;
        }
        $persistEntities = [];
        $recordList      = $this->getRecordList($allCampaignMembers);
        $mauticObject    = new IntegrationObject('Contact', 'lead');

        $contacts = $this->integrationEntityModel->getSyncedRecords($mauticObject, $this->getName(), $recordList);
        //first find existing campaign members.
        foreach ($contacts as $campaignMember) {
            $existingCampaignMember = $this->integrationEntityModel->getSyncedRecords($campaignMemberObject, $this->getName(), $campaignMember['internal_entity_id']);
            if (empty($existingCampaignMember)) {
                $persistEntities[] = $this->createIntegrationEntity(
                    $campaignMemberObject->getType(),
                    $campaignId,
                    $campaignMemberObject->getInternalType(),
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

    /**
     * @param $records
     *
     * @return array
     */
    public function getRecordList($records, $index = null)
    {
        $recordList = [];

        foreach ($records as $i => $record) {
            if ($index and isset($record[$index])) {
                $record = $record[$index];
            }
            $recordList[$record] = [
                'id' => $record,
            ];
        }

        return $recordList;
    }

    /**
     * @return array
     */
    public function getActivityTypes()
    {
        $params       = [];
        $activities   = [];
        $cwActivities = $this->getApiHelper()->getActivityTypes($params);

        foreach ($cwActivities as $cwActivity) {
            if (isset($cwActivity['id'])) {
                $activities[$cwActivity['id']] = $cwActivity['name'];
            }
        }

        return $activities;
    }

    /**
     * @return array
     */
    public function getMembers()
    {
        $params    = [];
        $members   = [];
        $cwMembers = $this->getApiHelper()->getMembers($params);
        foreach ($cwMembers as $cwMember) {
            if (isset($cwMember['id'])) {
                $members[$cwMember['id']] = $cwMember['identifier'];
            }
        }

        return $members;
    }

    /**
     * @param $config
     * @param $cwContactId
     * @param $leadId
     *
     * @return IntegrationEntity|null
     */
    public function createActivity($config, $cwContactId, $leadId)
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
                $integrationEntity = new IntegrationEntity();
                $integrationEntity->setDateAdded(new \DateTime());
                $integrationEntity->setIntegration($this->getName());
                $integrationEntity->setIntegrationEntity('Activities');
                $integrationEntity->setIntegrationEntityId($activities['id']);
                $integrationEntity->setInternalEntity('lead');
                $integrationEntity->setInternalEntityId($leadId);

                return $integrationEntity;
            }
        }

        return null;
    }
}
