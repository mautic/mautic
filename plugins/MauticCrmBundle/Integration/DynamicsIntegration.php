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

use Symfony\Component\Form\Form;

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
                        'contacts' => 'mautic.dynamics.object.contact',
                        'company'  => 'mautic.dynamics.object.company',
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
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
     */
    public function getLeads($params = [], $query = null, &$executed = null, &$result = [], $object = 'Leads')
    {
        if ('Lead' === $object || 'Contact' === $object) {
            $object .= 's'; // pluralize object name for Zoho
        }
        $executed = 0;
        try {
            if ($this->isAuthorized()) {
                $config           = $this->mergeConfigToFeatureSettings();
                $fields           = $config['leadFields'];
                $config['object'] = $object;
                $aFields          = $this->getAvailableLeadFields($config);
                $mappedData       = [];
                foreach (array_keys($fields) as $k) {
                    if (isset($aFields[$object][$k])) {
                        $mappedData[] = $aFields[$object][$k]['dv'];
                    }
                }
                $fields                  = implode(',', $mappedData);
                $params['selectColumns'] = $object.'('.$fields.')';
                $params['toIndex']       = 200; // maximum number of records
                $data                    = $this->getApiHelper()->getLeads($params, $object);
                $result                  = $this->amendLeadDataBeforeMauticPopulate($data, $object);
                $executed += count($result);
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }
    /**
     * @param array      $params
     * @param null|array $query
     *
     * @return int|null
     */
    public function getCompanies($params = [], $query = null, &$executed = null, &$result = [])
    {
        $executed = 0;
        $object   = 'company';
        try {
            if ($this->isAuthorized()) {
                $config           = $this->mergeConfigToFeatureSettings();
                $fields           = $config['companyFields'];
                $config['object'] = $object;
                $aFields          = $this->getAvailableLeadFields($config);
                $mappedData       = [];
                if (isset($aFields['company'])) {
                    $aFields = $aFields['company'];
                }
                foreach (array_keys($fields) as $k) {
                    $mappedData[] = $aFields[$k]['dv'];
                }
                $fields                  = implode(',', $mappedData);
                $params['selectColumns'] = 'Accounts('.$fields.')';
                $params['toIndex']       = 200; // maximum number of records
                $data                    = $this->getApiHelper()->getCompanies($params);
                $result                  = $this->amendLeadDataBeforeMauticPopulate($data, $object);
                $executed += count($result);
//              //TODO: fetch more records using fromIndex and toIndex until exception is thrown
//              if (isset($data['hasMore']) && $data['hasMore']) {
//                  $executed += $this->getCompanies($params);
//              }
                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @param array $config
     * @param null  $object
     *
     * @return array
     */
    public function populateMauticLeadData($data, $config = [], $object = 'Leads')
    {
        // Match that data with mapped lead fields
        $aFields       = $this->getAvailableLeadFields($config);
        $matchedFields = [];
        $fieldsName    = ('company' === $object) ? 'companyFields' : 'leadFields';
        if (isset($aFields[$object])) {
            $aFields = $aFields[$object];
        }
        foreach ($aFields as $k => $v) {
            foreach ($data as $dk => $dv) {
                if ($dk === $v['dv']) {
                    $matchedFields[$config[$fieldsName][$k]] = $dv;
                }
            }
        }

        return $matchedFields;
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
        return $this->getFormFieldsByObject('Accounts', $settings);
    }

    /**
     * @param array $settings
     *
     * @return array|mixed
     */
    public function getFormLeadFields($settings = [])
    {
        $leadFields    = $this->getFormFieldsByObject('Leads', $settings);
        $contactFields = $this->getFormFieldsByObject('Contacts', $settings);

        return array_merge($leadFields, $contactFields);
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
        $zohoFields        = [];
        $silenceExceptions = isset($settings['silence_exceptions']) ? $settings['silence_exceptions'] : true;
        if (isset($settings['feature_settings']['objects'])) {
            $zohoObjects = $settings['feature_settings']['objects'];
        } else {
            $settings    = $this->settings->getFeatureSettings();
            $zohoObjects = isset($settings['objects']) ? $settings['objects'] : ['Leads'];
        }
        try {
            if ($this->isAuthorized()) {
                if (!empty($zohoObjects) && is_array($zohoObjects)) {
                    foreach ($zohoObjects as $key => $zohoObject) {
                        // Check the cache first
                        $settings['cache_suffix'] = $cacheSuffix = '.'.$zohoObject;
                        if ($fields = parent::getAvailableLeadFields($settings)) {
                            $zohoFields[$zohoObject] = $fields;
                            continue;
                        }
                        $leadObject = $this->getApiHelper()->getLeadFields($zohoObject);
                        if (null === $leadObject || (isset($leadObject['response']) && isset($leadObject['response']['error']))) {
                            return [];
                        }
                        $objKey = 'company' === $zohoObject ? 'Accounts' : $zohoObject;
                        /** @var array $opts */
                        $opts = $leadObject[$objKey]['section'];
                        foreach ($opts as $optgroup) {
                            //$zohoFields[$optgroup['dv']] = array();
                            if (!array_key_exists(0, $optgroup['FL'])) {
                                $optgroup['FL'] = [$optgroup['FL']];
                            }
                            foreach ($optgroup['FL'] as $field) {
                                if (!(bool) $field['isreadonly'] || in_array($field['type'], ['Lookup', 'OwnerLookup', 'Boolean'], true)) {
                                    continue;
                                }
                                $zohoFields[$zohoObject][$this->getFieldKey($field['dv'])] = [
                                    'type'     => 'string',
                                    'label'    => $field['label'],
                                    'dv'       => $field['dv'],
                                    'required' => $field['req'] === 'true',
                                ];
                            }
                        }
                        $this->cache->set('leadFields'.$cacheSuffix, $zohoFields[$zohoObject]);
                    }
                }
            }
        } catch (ApiErrorException $exception) {
            $this->logIntegrationError($exception);
            if (!$silenceExceptions) {
                if (strpos($exception->getMessage(), 'Invalid Ticket Id') !== false) {
                    // Use a bit more friendly message
                    $exception = new ApiErrorException('There was an issue with communicating with Zoho. Please try to reauthorize.');
                }
                throw $exception;
            }

            return false;
        }

        return $zohoFields;
    }
}
