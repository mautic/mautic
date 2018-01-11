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

/**
 * Class VtigerIntegration.
 */
class VtigerIntegration extends CrmAbstractIntegration
{
    private $authorzationError = '';

    /**
     * Returns the name of the social integration that must match the name of the file.
     *
     * @return string
     */
    public function getName()
    {
        return 'Vtiger';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'vTiger';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'url'       => 'mautic.vtiger.form.url',
            'username'  => 'mautic.vtiger.form.username',
            'accessKey' => 'mautic.vtiger.form.password',
        ];
    }

    /**
     * @return string
     */
    public function getClientIdKey()
    {
        return 'username';
    }

    /**
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'accessKey';
    }

    /**
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'sessionName';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return sprintf('%s/webservice.php', $this->keys['url']);
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized()
    {
        if (!isset($this->keys['url'])) {
            return false;
        }

        $url        = $this->getApiUrl();
        $parameters = [
            'operation' => 'getchallenge',
            'username'  => $this->keys['username'],
        ];

        $response = $this->makeRequest($url, $parameters, 'GET', ['authorize_session' => true]);

        if (empty($response['success'])) {
            return $this->getErrorsFromResponse($response);
        }

        $loginParameters = [
            'operation' => 'login',
            'username'  => $this->keys['username'],
            'accessKey' => md5($response['result']['token'].$this->keys['accessKey']),
        ];

        $response = $this->makeRequest($url, $loginParameters, 'POST', ['authorize_session' => true]);

        if (empty($response['success'])) {
            if (is_array($response) && array_key_exists('error', $response)) {
                $this->authorzationError = $response['error']['message'];
            }

            return false;
        } else {
            $error = $this->extractAuthKeys($response['result']);

            if (empty($error)) {
                return true;
            } else {
                $this->authorzationError = $error;

                return false;
            }
        }
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
     * Retrieves and stores tokens returned from oAuthLogin.
     *
     * @param array $settings
     * @param array $parameters
     *
     * @return array
     */
    public function authCallback($settings = [], $parameters = [])
    {
        $success = $this->isAuthorized();
        if (!$success) {
            return $this->authorzationError;
        } else {
            return false;
        }
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = [])
    {
        if ($fields = parent::getAvailableLeadFields($settings)) {
            return $fields;
        }

        $vTigerFields      = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;

        if (isset($settings['feature_settings']['objects'])) {
            $vTigerObjects = $settings['feature_settings']['objects'];
        } else {
            $settings      = $this->settings->getFeatureSettings();
            $vTigerObjects = isset($settings['objects']) ? $settings['objects'] : ['contacts'];
        }

        try {
            if ($this->isAuthorized()) {
                if (!empty($vTigerObjects) && is_array($vTigerObjects)) {
                    foreach ($vTigerObjects as $object) {
                        // The object key for contacts should be 0 for some BC reasons
                        if ($object == 'contacts') {
                            $object = 0;
                        }

                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$object;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $vTigerFields[$object] = $fields;
                            continue;
                        }

                        // Create the array if it doesn't exist to prevent PHP notices
                        if (!isset($vTigerFields[$object])) {
                            $vTigerFields[$object] = [];
                        }

                        $leadFields = $this->getApiHelper()->getLeadFields($object);
                        if (isset($leadFields['fields'])) {
                            foreach ($leadFields['fields'] as $fieldInfo) {
                                if (!isset($fieldInfo['name']) || !$fieldInfo['editable'] || in_array(
                                        $fieldInfo['type']['name'],
                                        ['owner', 'reference', 'boolean', 'autogenerated']
                                    )
                                ) {
                                    continue;
                                }

                                $vTigerFields[$object][$fieldInfo['name']] = [
                                    'type'     => 'string',
                                    'label'    => $fieldInfo['label'],
                                    'required' => (in_array($fieldInfo['name'], ['email', 'accountname'])),
                                ];
                            }
                        }

                        $this->cache->set('leadFields'.$cacheSuffix, $vTigerFields[$object]);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $vTigerFields;
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
        if ($section == 'leadfield_match') {
            return ['mautic.vtiger.form.field_match_notes', 'info'];
        }

        return parent::getFormNotes($section);
    }

    /**
     * {@inheritdoc}
     *
     * @param $mappedData
     */
    public function amendLeadDataBeforePush(&$mappedData)
    {
        if (!empty($mappedData)) {
            //vtiger requires assigned_user_id so default to authenticated user
            $mappedData['assigned_user_id'] = $this->keys['userId'];
        }
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
                        'contacts' => 'mautic.vtiger.object.contact',
                        'company'  => 'mautic.vtiger.object.company',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.vtiger.form.objects_to_pull_from',
                    'label_attr'  => ['class' => ''],
                    'empty_value' => false,
                    'required'    => false,
                ]
            );
        }
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
     * Get available company fields for choices in the config UI.
     *
     * @param array $settings
     *
     * @return array
     */
    public function getFormCompanyFields($settings = [])
    {
        return parent::getAvailableLeadFields(['cache_suffix' => '.company']);
    }
}
