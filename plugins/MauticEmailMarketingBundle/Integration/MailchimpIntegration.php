<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEmailMarketingBundle\Integration;

use MauticPlugin\MauticEmailMarketingBundle\Form\Type\MailchimpType;

/**
 * Class MailchimpIntegration.
 */
class MailchimpIntegration extends EmailAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Mailchimp';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'MailChimp';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return (empty($this->keys['client_id'])) ? 'basic' : 'oauth2';
    }

    /**
     * Get the URL required to obtain an oauth2 access token.
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return 'https://login.mailchimp.com/oauth2/token';
    }

    /**
     * Get the authentication/login URL for oauth2 access.
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return 'https://login.mailchimp.com/oauth2/authorize';
    }

    /**
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return (empty($this->keys['client_id'])) ?
            [
                'username' => 'mautic.integration.keyfield.username',
                'password' => 'mautic.integration.keyfield.api',
            ] :
            [
                'client_id'     => 'mautic.integration.keyfield.clientid',
                'client_secret' => 'mautic.integration.keyfield.clientsecret',
            ];
    }

    /**
     * @param array $settings
     * @param array $parameters
     *
     * @return bool|string
     */
    public function authCallback($settings = [], $parameters = [])
    {
        $error = parent::authCallback($settings, $parameters);

        if (empty($error)) {
            // Now post to the metadata URL
            $data = $this->makeRequest('https://login.mailchimp.com/oauth2/metadata');

            return $this->extractAuthKeys($data, 'dc');
        } else {
            return $error;
        }
    }

    /**
     * @param array $settings
     *
     * @return mixed
     */
    public function getAvailableLeadFields($settings = [])
    {
        if (isset($settings['list'])) {
            // Ajax update
            $listId = $settings['list'];
        } elseif (!empty($settings['feature_settings']['list_settings']['list'])) {
            // Form load
            $listId = $settings['feature_settings']['list_settings']['list'];
        } elseif (!empty($settings['list_settings']['list'])) {
            // Push action
            $listId = $settings['list_settings']['list'];
        }

        if (!empty($listId)) {
            $settings['cache_suffix'] = $cacheSuffix = '.'.$listId;
            if ($fields = parent::getAvailableLeadFields($settings)) {
                return $fields;
            }

            $fields = $this->getApiHelper()->getCustomFields($listId);

            if (!empty($fields['merge_fields']) && count($fields['merge_fields'])) {
                foreach ($fields['merge_fields'] as $field) {
                    $leadFields[$field['tag']] = [
                        'label'    => $field['name'],
                        'type'     => 'string',
                        'required' => $field['required'],
                    ];
                }
            }

            $leadFields['EMAIL'] = [
                'label'    => 'Email',
                'type'     => 'string',
                'required' => true,
            ];

            $this->cache->set('leadFields'.$cacheSuffix, $leadFields);

            return $leadFields;
        }

        return [];
    }

    /**
     * @param       $lead
     * @param array $config
     *
     * @return bool
     */
    public function pushLead($lead, $config = [])
    {
        $config     = $this->mergeConfigToFeatureSettings($config);
        $mappedData = $this->populateLeadData($lead, $config);

        if (empty($mappedData)) {
            return false;
        } elseif (empty($mappedData['EMAIL'])) {
            return false;
        } elseif (!isset($config['list_settings'])) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $email = $mappedData['EMAIL'];
                unset($mappedData['EMAIL']);

                $options                 = [];
                $options['status']       = $config['list_settings']['doubleOptin'] ? 'pending' : 'subscribed';
                $options['send_welcome'] = $config['list_settings']['sendWelcome'];
                $listId                  = $config['list_settings']['list'];

                $this->getApiHelper()->subscribeLead($email, $listId, $mappedData, $options);

                return true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return false;
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        $settings                           = parent::getFormSettings();
        $settings['dynamic_contact_fields'] = true;

        return $settings;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getFormType()
    {
        return MailchimpType::class;
    }
}
