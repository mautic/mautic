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
        return ['push_lead', 'pull_lead'];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            'companyid' => 'mautic.connectwise.form.companyid',
            'username'  => 'mautic.connectwise.form.publickey',
            'password'  => 'mautic.connectwise.form.privatekey',
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
    public function getClientIdKey()
    {
        return 'username';
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
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://staging.connectwisedev.com/v4_6_release';
    }

    /**
     * @return bool
     */
    public function authCallback($settings = [], $parameters = [])
    {
        $url         = $this->getApiUrl();
        $request_url = sprintf('%s/login/login.aspx', $url);
        $parameters  = [
            'username'    => $this->keys[$this->getClientIdKey()],
            'password'    => $this->keys[$this->getClientSecretKey()],
            'companyname' => $this->keys[$this->getCompanyIdKey()],
        ];

        $response = $this->makeRequest($request_url, $parameters, 'GET', ['headers' => ['cookie' => $this->keys[$this->getCompanyCookieKey()]]]);

        if ($response == 'FAIL') {
            return $this->translator->trans('mautic.connectwise.auth_error', ['%cause%' => (isset($response['CAUSE']) ? $response['CAUSE'] : 'UNKNOWN')]);
        }

        return $this->extractAuthKeys($response);
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
        return $this->getFormFieldsByObject('contacts', $settings);
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = [])
    {
        if ($fields = parent::getAvailableLeadFields()) {
            return $fields;
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
                        'contacts' => 'mautic.connectwise.object.contact',
                        'company'  => 'mautic.connectwise.object.company',
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
}
