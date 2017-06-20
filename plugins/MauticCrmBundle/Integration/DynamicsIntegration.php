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

use Mautic\PluginBundle\Exception\ApiErrorException;

class DynamicsIntegration extends CrmAbstractIntegration
{
    public function getName()
    {
        return 'Dynamics';
    }

    public function getDisplayName()
    {
        return 'Dynamics CRM';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead', 'get_leads', 'push_leads'];
    }

    /**
     * Return's authentication method such as oauth2, oauth1a, key, etc.
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * Return array of key => label elements that will be converted to inputs to
     * obtain from the user.
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'resource'      => 'mautic.integration.dynamics.resource',
            'client_id'     => 'mautic.integration.dynamics.client_id',
            'client_secret' => 'mautic.integration.dynamics.client_secret',
            'username'      => 'mautic.integration.dynamics.username',
            'password'      => 'mautic.integration.dynamics.password',
        ];
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
                        'contact' => 'mautic.dynamics.object.contact',
                        'company' => 'mautic.dynamics.object.company',
                    ],
                    'expanded'    => true,
                    'multiple'    => true,
                    'label'       => 'mautic.dynamics.form.objects_to_pull_from',
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
     * {@inheritdoc}
     */
    public function sortFieldsAlphabetically()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://login.microsoftonline.com/common';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return $this->getApiUrl().'/oauth2/token';
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
        $settings = array_merge($settings, [
            'grant_type'         => 'password',
            'ignore_redirecturi' => true,
        ]);
        $parameters = array_merge($parameters, [
            'username'      => $this->keys['username'],
            'password'      => $this->keys['password'],
            'client_id'     => $this->keys['client_id'],
            'client_secret' => $this->keys['client_secret'],
            'resource'      => $this->keys['resource'],
        ]);

        return parent::authCallback($settings, $parameters);
    }

    /**
     * @param $url
     * @param $parameters
     * @param $method
     * @param $settings
     * @param $authType
     *
     * @return array
     */
    public function prepareRequest($url, $parameters, $method, $settings, $authType)
    {
        if ($authType == 'oauth2' && empty($settings['authorize_session']) && isset($this->keys['access_token'])) {

            // Append the access token as the oauth-token header
            $headers = [
                'Content-Type: application/json',
                "Authorization: Bearer {$this->keys['access_token']}",
            ];

            return [$parameters, $headers];
        } else {
            return parent::prepareRequest($url, $parameters, $method, $settings, $authType);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized()
    {
        if ($this->isConfigured()) {
            // Dynamics also uses password grant type so login each time to ensure session is valid
            $this->authCallback();
        }

        return parent::isAuthorized();
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
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string|array
     */
    public function getFormNotes($section)
    {
        if ('custom' === $section) {
            return [
                'template'   => 'MauticCrmBundle:Integration:dynamics.html.php',
                'parameters' => [

                ],
            ];
        }

        return parent::getFormNotes($section);
    }

    /**
     * @param $lead
     * @param $config
     *
     * @return string
     */
    public function populateLeadData($lead, $config = [])
    {
        $config['object'] = 'contact';
        $mappedData       = parent::populateLeadData($lead, $config);

        return $mappedData;
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
        return $this->getFormFieldsByObject('account', $settings);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields($settings = [])
    {
        return  $this->getFormFieldsByObject('contact', $settings);
    }

    /**
     * @param array $settings
     *
     * @return array|bool
     *
     * @throws ApiErrorException
     */
    public function getAvailableLeadFields($settings = [])
    {
        if ($fields = parent::getAvailableLeadFields($settings)) {
            return $fields;
        }
        $dynamicsFields    = [];
        $silenceExceptions = isset($settings['silence_exceptions']) ? $settings['silence_exceptions'] : true;
        if (isset($settings['feature_settings']['objects'])) {
            $dynamicsObjects = $settings['feature_settings']['objects'];
        } else {
            $settings        = $this->settings->getFeatureSettings();
            $dynamicsObjects = isset($settings['objects']) ? $settings['objects'] : ['contact'];
        }
        try {
            if ($this->isAuthorized()) {
                if (!empty($dynamicsObjects) && is_array($dynamicsObjects)) {
                    foreach ($dynamicsObjects as $key => $dynamicsObject) {
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$dynamicsObject;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $dynamicsFields[$dynamicsObject] = $fields;
                            continue;
                        }
                        $leadObject = $this->getApiHelper()->getLeadFields($dynamicsObject);
                        if (null === $leadObject || (isset($leadObject['response']) && isset($leadObject['response']['error']))) {
                            return [];
                        }
                        /** @var array $opts */
                        $fields = $leadObject['value'];
                        foreach ($fields as $field) {
                            if (!(bool) $field['IsValidForUpdate'] || in_array($field['AttributeType'], ['Virtual', 'Uniqueidentifier', 'Picklist', 'Lookup', 'Boolean', 'Owner', 'Customer'], true)) {
                                continue;
                            }
                            $dynamicsFields[$dynamicsObject][$field['LogicalName']] = [
                                'type'     => 'string',
                                'label'    => $field['DisplayName']['UserLocalizedLabel']['Label'],
                                'dv'       => $field['LogicalName'],
                                'required' => 'ApplicationRequired' === $field['RequiredLevel']['Value'],
                            ];
                        }
                        $this->cache->set('leadFields'.$cacheSuffix, $dynamicsFields[$dynamicsObject]);
                    }
                }
            }
        } catch (ApiErrorException $exception) {
            $this->logIntegrationError($exception);
            if (!$silenceExceptions) {
                throw $exception;
            }

            return false;
        }

        return $dynamicsFields;
    }
}
