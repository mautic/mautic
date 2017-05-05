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
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class SugarcrmIntegration.
 */
class SugarcrmIntegration extends CrmAbstractIntegration
{
    private $authorzationError;

    /**
     * Returns the name of the social integration that must match the name of the file.
     *
     * @return string
     */
    public function getName()
    {
        return 'Sugarcrm';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
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
     * {@inheritdoc}
     *
     * @return array
     */
    public function getSecretKeys()
    {
        return [
            'client_secret',
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
        return (isset($this->keys['version']) && $this->keys['version'] == '6') ? 'id' : 'access_token';
    }

    /**
     * SugarCRM 7 refresh tokens.
     */
    public function getRefreshTokenKeys()
    {
        return [
            'refresh_token',
            'expires',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        $apiUrl = ($this->keys['version'] == '6') ? 'service/v4_1/rest.php' : 'rest/v10/oauth2/token';

        return sprintf('%s/%s', $this->keys['sugarcrm_url'], $apiUrl);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthLoginUrl()
    {
        return $this->factory->getRouter()->generate('mautic_integration_auth_callback', ['integration' => $this->getName()]);
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
        if (isset($this->keys['version']) && $this->keys['version'] == '6') {
            $success = $this->isAuthorized();
            if (!$success) {
                return $this->authorzationError;
            } else {
                return false;
            }
        } else {
            $settings = [
                'grant_type'         => 'password',
                'ignore_redirecturi' => true,
            ];
            $parameters = [
                'username' => $this->keys['username'],
                'password' => $this->keys['password'],
                'platform' => 'base',
            ];

            return parent::authCallback($settings, $parameters);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'sugarcrm_url'  => 'mautic.sugarcrm.form.url',
            'client_id'     => 'mautic.sugarcrm.form.clientkey',
            'client_secret' => 'mautic.sugarcrm.form.clientsecret',
            'username'      => 'mautic.sugarcrm.form.username',
            'password'      => 'mautic.sugarcrm.form.password',
        ];
    }

    /**
     * @param array $settings
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getAvailableLeadFields($settings = [])
    {
        if ($fields = parent::getAvailableLeadFields($settings)) {
            return $fields;
        }

        $sugarFields       = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        try {
            if ($this->isAuthorized()) {
                $leadObject = $this->getApiHelper()->getLeadFields();
                if ($leadObject != null) {
                    if (isset($leadObject['module_fields'])) {
                        //6.x/community
                        foreach ($leadObject['module_fields'] as $fieldInfo) {
                            if (isset($fieldInfo['name']) && !in_array($fieldInfo['type'], ['id', 'assigned_user_name', 'bool', 'link', 'relate'])) {
                                $sugarFields[$fieldInfo['name']] = [
                                    'type'     => 'string',
                                    'label'    => $fieldInfo['label'],
                                    'required' => !empty($fieldInfo['required']),
                                ];
                            }
                        }
                    } elseif (isset($leadObject['fields'])) {
                        //7.x
                        foreach ($leadObject['fields'] as $fieldInfo) {
                            if (isset($fieldInfo['name']) && empty($fieldInfo['readonly']) && !empty($fieldInfo['comment']) && !in_array($fieldInfo['type'], ['id', 'team_list', 'bool', 'link', 'relate'])) {
                                $fieldName = (strpos($fieldInfo['name'], 'webtolead_email') === false) ? $fieldInfo['name'] : str_replace('webtolead_', '', $fieldInfo['name']);

                                $sugarFields[$fieldName] = [
                                    'type'     => 'string',
                                    'label'    => $fieldInfo['comment'],
                                    'required' => !empty($fieldInfo['required']),
                                ];
                            }
                        }
                    }

                    $this->cache->set('leadFields', $sugarFields);
                }
            } else {
                throw new ApiErrorException($this->authorzationError);
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $sugarFields;
    }

    /**
     * @param $response
     *
     * @return string
     */
    public function getErrorsFromResponse($response)
    {
        if ($this->keys['version'] == '6') {
            if (!empty($response['name'])) {
                return $response['description'];
            } else {
                return $this->factory->getTranslator()->trans('mautic.integration.error.genericerror', [], 'flashes');
            }
        } else {
            return parent::getErrorsFromResponse($response);
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return (isset($this->keys['version']) && $this->keys['version'] == '6') ? 'rest' : 'oauth2';
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
        if ($authType == 'oauth2' && empty($settings['authorize_session'])) {

            // Append the access token as the oauth-token header
            $headers = [
                "oauth-token: {$this->keys['access_token']}",
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
        if (!isset($this->keys['version'])) {
            return false;
        }

        if ($this->keys['version'] == '6') {
            $loginParams = [
                'user_auth' => [
                    'user_name' => $this->keys['username'],
                    'password'  => md5($this->keys['password']),
                    'version'   => '1',
                ],
                'application_name' => 'Mautic',
                'name_value_list'  => [],
                'method'           => 'login',
                'input_type'       => 'JSON',
                'response_type'    => 'JSON',
            ];
            $parameters = [
                'method'        => 'login',
                'input_type'    => 'JSON',
                'response_type' => 'JSON',
                'rest_data'     => json_encode($loginParams),
            ];

            $settings['auth_type'] = 'rest';
            $response              = $this->makeRequest($this->getAccessTokenUrl(), $parameters, 'GET', $settings);

            unset($response['module'], $response['name_value_list']);
            $error = $this->extractAuthKeys($response, 'id');

            $this->authorzationError = $error;

            return empty($error);
        } else {
            if ($this->isConfigured()) {
                // SugarCRM 7 uses password grant type so login each time to ensure session is valid
                $this->authCallback();
            }

            return parent::isAuthorized();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param $data
     */
    public function prepareResponseForExtraction($data)
    {
        // Extract expiry and set expires for 7.x
        if (is_array($data) && isset($data['expires_in'])) {
            $data['expires'] = $data['expires_in'] + time();
        }

        return $data;
    }

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'keys') {
            $builder->add('version', 'button_group', [
                'choices' => [
                    '6' => '6.x/community',
                    '7' => '7.x',
                ],
                'label'       => 'mautic.sugarcrm.form.version',
                'constraints' => [
                    new NotBlank([
                        'message' => 'mautic.core.value.required',
                    ]),
                ],
                'required' => true,
            ]);
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
     *
     * @return bool
     */
    public function getDataPriority()
    {
        return true;
    }
}
