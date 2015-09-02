<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\PluginBundle\Exception\ApiErrorException;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class SugarcrmIntegration
 */
class SugarcrmIntegration extends CrmAbstractIntegration
{

    private $authorzationError;

    /**
     * Returns the name of the social integration that must match the name of the file
     *
     * @return string
     */
    public function getName()
    {
        return 'Sugarcrm';
    }

    /**
     * Get the array key for clientId
     *
     * @return string
     */
    public function getClientIdKey()
    {
        return 'client_id';
    }

    /**
     * Get the array key for client secret
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
        return array(
            'client_secret',
            'password'
        );
    }


    /**
     * Get the array key for the auth token
     *
     * @return string
     */
    public function getAuthTokenKey ()
    {
        return (isset($this->keys['version']) && $this->keys['version'] == '6') ? 'id' : 'access_token';
    }

    /**
     * SugarCRM 7 refresh tokens
     */
    public function getRefreshTokenKeys()
    {
        return array(
            'refresh_token',
            'expires'
        );
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
    public function getAuthLoginUrl ()
    {
        return $this->factory->getRouter()->generate('mautic_integration_auth_callback', array('integration' => $this->getName()));
    }

    /**
     * Retrieves and stores tokens returned from oAuthLogin
     *
     * @param array $settings
     * @param array $parameters
     *
     * @return array
     */
    public function authCallback ($settings = array(), $parameters = array())
    {
        if (isset($this->keys['version']) && $this->keys['version'] == '6') {
            $success = $this->isAuthorized();
            if (!$success) {
                return $this->authorzationError;
            } else {
                return false;
            }
        } else {
            $settings   = array(
                'grant_type'         => 'password',
                'ignore_redirecturi' => true
            );
            $parameters = array(
                'username' => $this->keys['username'],
                'password' => $this->keys['password'],
                'platform' => 'base'
            );

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
        return array(
            'sugarcrm_url'  => 'mautic.sugarcrm.form.url',
            'client_id'     => 'mautic.sugarcrm.form.clientkey',
            'client_secret' => 'mautic.sugarcrm.form.clientsecret',
            'username'      => 'mautic.sugarcrm.form.username',
            'password'      => 'mautic.sugarcrm.form.password'
        );
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = array())
    {
        $sugarFields       = array();
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        try {
            if ($this->isAuthorized()) {
                $leadObject  = $this->getApiHelper()->getLeadFields();
                if ($leadObject != null) {
                    if (isset($leadObject['module_fields'])) {
                        //6.x/community
                        foreach ($leadObject['module_fields'] as $fieldInfo) {
                            if (isset($fieldInfo['name']) && !in_array($fieldInfo['type'], array('id', 'assigned_user_name', 'bool', 'link', 'relate'))) {
                                $sugarFields[$fieldInfo['name']] = array(
                                    'type'     => 'string',
                                    'label'    => $fieldInfo['label'],
                                    'required' => !empty($fieldInfo['required'])
                                );
                            }
                        }
                    } elseif (isset($leadObject['fields'])) {
                        //7.x
                        foreach ($leadObject['fields'] as $fieldInfo) {
                            if (isset($fieldInfo['name']) && empty($fieldInfo['readonly']) && !empty($fieldInfo['comment']) && !in_array($fieldInfo['type'], array('id', 'team_list', 'bool', 'link', 'relate'))) {
                                $sugarFields[$fieldInfo['name']] = array(
                                    'type'     => 'string',
                                    'label'    => $fieldInfo['comment'],
                                    'required' => !empty($fieldInfo['required'])
                                );
                            }
                        }
                    }
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
                return $this->factory->getTranslator()->trans("mautic.integration.error.genericerror", array(), "flashes");
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
            $loginParams = array(
                'user_auth'        => array(
                    'user_name' => $this->keys['username'],
                    'password'  => md5($this->keys['password']),
                    'version'   => '1'
                ),
                'application_name' => 'Mautic',
                'name_value_list'  => array(),
                'method'           => 'login',
                'input_type'       => 'JSON',
                'response_type'    => 'JSON',
            );
            $parameters = array(
                'method'        => 'login',
                'input_type'    => 'JSON',
                'response_type' => 'JSON',
                'rest_data'     => json_encode($loginParams)
            );

            $settings['auth_type'] = 'rest';
            $response              = $this->makeRequest($this->getAccessTokenUrl(), $parameters, 'GET', $settings);

            unset($response['module'], $response['name_value_list']);
            $error = $this->extractAuthKeys($response, 'id');

            $this->authorzationError = $error;

            return (empty($error));
        } else {
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
     * @param FormBuilder|Form $builder
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'keys') {
            $builder->add('version', 'button_group', array(
                'choices' => array(
                    '6' => '6.x/community',
                    '7' => '7.x'
                ),
                'label' => 'mautic.sugarcrm.form.version',
                'constraints' => array(
                    new NotBlank(array(
                        'message' => 'mautic.core.value.required'
                    ))
                ),
                'required' => true
            ));
        }
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        return array(
            'requires_callback'      => true,
            'requires_authorization' => true
        );
    }
}
