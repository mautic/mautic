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

use Mautic\PluginBundle\Integration\AbstractIntegration;
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

    public function getDescription()
    {
        return 'Microsoft Dynamics CRM integration';
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
            'requires_authorization' => false,
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
    public function getAuthenticationUrl()
    {
        return $this->getApiUrl().'/oauth2/authorize';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isAuthorized()
    {
        $keys = $this->getKeys();

        return isset($keys[$this->getAuthTokenKey()]);
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        $keys = $this->getKeys();

        return $keys[$this->getAuthTokenKey()];
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
