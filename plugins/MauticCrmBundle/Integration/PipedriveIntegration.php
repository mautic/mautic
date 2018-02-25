<?php

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PipedriveIntegration extends CrmAbstractIntegration
{
    const INTEGRATION_NAME         = 'Pipedrive';
    const PERSON_ENTITY_TYPE       = 'person';
    const LEAD_ENTITY_TYPE         = 'lead';
    const ORGANIZATION_ENTITY_TYPE = 'organization';
    const COMPANY_ENTITY_TYPE      = 'company';

    private $apiHelper;

    private $requiredFields = [
        'organization' => ['name'],
    ];

    /**
     * @return string
     */
    public function getName()
    {
        return self::INTEGRATION_NAME;
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'key';
    }

    /**
     * Get the array key for client secret.
     *
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'token';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSecretKeys()
    {
        return [
            'token',
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
        return 'token';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'url'      => 'mautic.pipedrive.api_url',
            'token'    => 'mautic.pipedrive.token',
            'user'     => 'mautic.pipedrive.webhook_user',
            'password' => 'mautic.pipedrive.webhook_password',
        ];
    }

    public function getApiUrl()
    {
        return $this->getKeys()['url'];
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized()
    {
        $keys = $this->getKeys();

        return isset($keys[$this->getClientSecretKey()]);
    }

    /**
     * Get available company fields for choices in the config UI.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormCompanyFields1($settings = [])
    {
        $settings['feature_settings']['objects'][] = self::ORGANIZATION_ENTITY_TYPE;
        $fields                                    = $this->getAvailableLeadFields($settings);

        return $fields;
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields1($settings = [])
    {
        $settings['feature_settings']['objects'][] = self::PERSON_ENTITY_TYPE;
        $fields                                    = $this->getAvailableLeadFields($settings);

        // handle fields with are available in Pipedrive, but not listed
        return array_merge($fields, [
            'last_name' => [
                'label'    => 'Last Name',
                'required' => true,
            ],
            'first_name' => [
                'label'    => 'First Name',
                'required' => true,
            ],
        ]);
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = [])
    {
        $pipedriveFields      = [];
        $silenceExceptions    = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

        if (isset($settings['feature_settings']['objects'])) {
            $pipedriveObjects = $settings['feature_settings']['objects'];
        } else {
            $settings         = $this->settings->getFeatureSettings();
            $pipedriveObjects = isset($settings['objects']) ? $settings['objects'] : ['contacts'];
        }
        try {
            if ($this->isAuthorized()) {
                if (!empty($pipedriveObjects) && is_array($pipedriveObjects)) {
                    foreach ($pipedriveObjects as $object) {
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$object;
                        if ($fields = parent::getAvailableLeadFields($settings) && !empty($fields)) {
                            $pipedriveFields[$object] = $fields;
                            continue;
                        }
                        // Create the array if it doesn't exist to prevent PHP notices
                        if (!isset($pipedriveFields[$object])) {
                            $pipedriveFields[$object] = [];
                        }

                        if ($object == 'company') {
                            $objectPipdrive = self::ORGANIZATION_ENTITY_TYPE;
                        } else {
                            $objectPipdrive = self::PERSON_ENTITY_TYPE;
                        }
                        $leadFields           = $this->getApiHelper()->getFields($objectPipdrive);
                        $leadFields['fields'] = $leadFields;
                        if (isset($leadFields['fields'])) {
                            foreach ($leadFields['fields'] as $fieldInfo) {
                                if (!isset($fieldInfo['key']) || in_array(
                                        $fieldInfo['key'],
                                        ['owner', 'reference', 'boolean', 'autogenerated']
                                    )
                                ) {
                                    continue;
                                }

                                $pipedriveFields[$object][$fieldInfo['key']] = [
                                    'type'     => 'string',
                                    'label'    => $fieldInfo['name'],
                                    'required' => (in_array($fieldInfo['key'], ['email', 'accountname'])),
                                ];
                            }
                        }

                        $this->cache->set('leadFields'.$cacheSuffix, $pipedriveFields[$object]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $pipedriveFields;
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields2($object = null)
    {
        $integrationFields = [];

        /**
         * $object as Array comes from clicking "Apply" button on Plugins Configuration form.
         * I dont't know why its calling again pipedrive API to get fields which are already inside form...
         * Also i have no idea why is trying to pass some strange object...
         */
        if (!$this->isAuthorized() || !$object || is_array($object)) {
            return $integrationFields;
        }

        try {
            $leadFields = $this->getApiHelper()->getFields($object);

            if (!isset($leadFields)) {
                return $integrationFields;
            }

            foreach ($leadFields as $fieldInfo) {
                $integrationFields[$fieldInfo['key']] = [
                    'label'    => $fieldInfo['name'],
                    'required' => isset($this->requiredFields[$object]) && in_array($fieldInfo['key'], $this->requiredFields[$object]),
                ];
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $integrationFields;
    }

    /**
     * Get the API helper.
     *
     * @return object
     */
    public function getApiHelper()
    {
        if (empty($this->apiHelper)) {
            $client          = $this->factory->get('mautic_integration.service.transport');
            $class           = '\\MauticPlugin\\MauticCrmBundle\\Api\\'.$this->getName().'Api'; //TODO replace with service
            $this->apiHelper = new $class($this, $client);
        }

        return $this->apiHelper;
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
                        'contacts' => 'mautic.pipedrive.object.contacts',
                        'company'  => 'mautic.pipedrive.object.organization',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.pipedrive.form.objects_to_pull_from',
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
        }
    }

    public function isCompanySupportEnabled()
    {
        $supportedFeatures = $this->getIntegrationSettings()->getFeatureSettings();

        return isset($supportedFeatures['objects']) && in_array('company', $supportedFeatures['objects']);
    }

    public function pushLead($lead, $config = [])
    {
        $leadExport = $this->factory->get('mautic_integration.pipedrive.export.lead');
        $leadExport->setIntegration($this);

        return $leadExport->create($lead);
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string
     */
    public function getFormNotes($section)
    {
        $router     = $this->factory->get('router');
        $translator = $this->getTranslator();

        if ($section == 'authorization') {
            return [$translator->trans('mautic.pipedrive.webhook_callback').$router->generate('mautic_integration.pipedrive.webhook', [], UrlGeneratorInterface::ABSOLUTE_URL), 'info'];
        }

        return parent::getFormNotes($section);
    }
}
