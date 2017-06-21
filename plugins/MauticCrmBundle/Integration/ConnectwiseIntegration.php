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

use Mautic\PluginBundle\Entity\IntegrationEntity;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use Spinen\ConnectWise\Models\Company\Company;
use Spinen\ConnectWise\Models\Company\Contact;

/**
 * Class ConnectwiseIntegration.
 */
class ConnectwiseIntegration extends CrmAbstractIntegration
{
    private $client;
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
        return ['push_lead', 'pull_lead', 'get_leads'];
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
        return sprintf('%s/v4_6_release/apis/3.0/', $this->keys['site']);
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
            $response = $this->makeRequest($url.'system/members/', $parameters, 'GET', $settings);

            if (isset($response['message'])) {
                $error = $response['message'];
                $this->extractAuthKeys($response);
            } else {
                $data = ['username' => $this->keys['username'], 'password' => $this->keys['password']];
                $this->extractAuthKeys($data, 'username');
            }
        } catch (RequestException $e) {
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
            $cwObjects[] = 'Contact';
        }
        if (!$this->isAuthorized()) {
            return [];
        }
        switch ($cwObjects) {
            case isset($cwObjects['Contact']):
                $contactFields       = $this->getContactFields();
                $cwFields['Contact'] = $this->setFields($contactFields);
                break;
            case isset($cwObjects['company']):
                $company             = $this->getCompanyFields();
                $cwFields['company'] = $this->setFields($company);
                break;
        }

        return $cwFields;
    }

    public function setFields($fields)
    {
        $cwFields = [];

        foreach ($fields as $fieldName => $field) {
            if ($field['type'] == 'string' || $field['type'] == 'boolean') {
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
            'communicationItems'     => ['type' => 'array', 'required' => false,
                'items'                         => ['name' => ['type' => 'name'], 'value' => 'value'],
            ],
            'Direct' => ['type' => 'string', 'required' => false],
            'Cell'   => ['type' => 'string', 'required' => false],
            'Email'  => ['type' => 'string', 'required' => true],
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
    public function getCompanies($params = [], $query = null, $executed = null)
    {
        return $this->getRecords($params, 'company');
    }

    public function getRecords($params, $object)
    {
        //todo data priority
        $executed            = null;
        $integrationEntities = [];
        try {
            if ($this->isAuthorized()) {
                $data                  = ($object == 'Contact') ? $this->getApiHelper()->getContacts($params) : $this->getApiHelper()->getCompanies($params);
                $mauticReferenceObject = ($object == 'Contact') ? 'lead' : 'company';
                if (!empty($data)) {
                    foreach ($data as $record) {
                        if (is_array($record)) {
                            $id            = $record['id'];
                            $formattedData = $this->amendLeadDataBeforeMauticPopulate($record, $object);
                            $entity        = ($object == 'Contact') ? $this->getMauticLead($formattedData) : $this->getMauticCompany($formattedData);
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

        return $fieldsValues;
    }

    public function saveSyncedData($entity, $object, $mauticObjectReference, $id)
    {
        $integrationEntity = null;

        /** @var IntegrationEntityRepository $integrationEntityRepo */
        $integrationEntityRepo = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
        $integrationId         = $integrationEntityRepo->getIntegrationsEntityId(
            $this->getName(),
            $object,
            $mauticObjectReference,
            $entity->getId()
        );

        if ($integrationId == null) {
            $integrationEntity = new IntegrationEntity();
            $integrationEntity->setDateAdded(new \DateTime());
            $integrationEntity->setIntegration($this->getName());
            $integrationEntity->setIntegrationEntity($object);
            $integrationEntity->setIntegrationEntityId($id);
            $integrationEntity->setInternalEntity($mauticObjectReference);
            $integrationEntity->setInternalEntityId($entity->getId());
            $integrationEntities[] = $integrationEntity;
        } else {
            $integrationEntity = $integrationEntityRepo->getEntity($integrationId[0]['id']);
            $integrationEntity->setLastSyncDate(new \DateTime());
            $integrationEntities[] = $integrationEntity;
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
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return [];
        }

        $object = 'Contact';

        $leadFields = $this->getContactFields();

        $fieldsToUpdateInCW  = isset($config['update_mautic']) ? array_keys($config['update_mautic'], 1) : [];
        $leadFields          = array_diff_key($leadFields, array_flip($fieldsToUpdateInCW));
        $mappedData[$object] = $this->populateLeadData(
            $lead,
            ['leadFields' => $leadFields, 'object' => $object, 'feature_settings' => ['objects' => $config['objects']]]
        );

        if (isset($config['objects']) && array_search('Contact', $config['objects'])) {
            $contactFields         = $this->getContactFields();
            $mappedData['Contact'] = $this->populateLeadData(
                $lead,
                ['leadFields' => $contactFields, 'object' => 'Contact', 'feature_settings' => ['objects' => $config['objects']]]
            );
            $this->amendLeadDataBeforePush($mappedData['Contact']);
        }
        if (empty($mappedData)) {
            return false;
        }

        return false;
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
            $leadId = $lead->getId();
        } else {
            $fields = $lead;
            $leadId = $lead['id'];
        }

        $leadFields      = $config['leadFields'];
        $availableFields = $this->getAvailableLeadFields($config);

        $unknown = $this->translator->trans('mautic.integration.form.lead.unknown');
        $matched = [];

        foreach ($availableFields as $key => $field) {
            $integrationKey = $matchIntegrationKey = $this->convertLeadFieldKey($key, $field);
            if (is_array($integrationKey)) {
                list($integrationKey, $matchIntegrationKey) = $integrationKey;
            }

            if (isset($leadFields[$integrationKey])) {
                if ($leadFields[$integrationKey] == 'mauticContactTimelineLink') {
                    $this->pushContactLink  = true;
                    $mauticContactLinkField = $integrationKey;
                    continue;
                }
                $mauticKey = $leadFields[$integrationKey];
                if (isset($fields[$mauticKey]) && !empty($fields[$mauticKey]['value'])) {
                    $matched[$matchIntegrationKey] = $this->cleanPushData($fields[$mauticKey]['value']);
                }
            }

            if (!empty($field['required']) && empty($matched[$matchIntegrationKey])) {
                $matched[$matchIntegrationKey] = $unknown;
            }
        }
        if ($this->pushContactLink) {
            $matched[$mauticContactLinkField] = $this->router->generate(
                'mautic_plugin_timeline_view',
                ['integration' => $this->getName(), 'leadId' => $leadId],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }
        //todo finish here
        return $matched;
    }
}
