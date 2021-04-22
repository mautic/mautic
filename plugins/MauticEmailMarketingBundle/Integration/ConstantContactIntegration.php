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

use MauticPlugin\MauticEmailMarketingBundle\Form\Type\ConstantContactType;

/**
 * Class ConstantContactIntegration.
 */
class ConstantContactIntegration extends EmailAbstractIntegration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'ConstantContact';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Constant Contact';
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * Get the URL required to obtain an oauth2 access token.
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return 'https://oauth2.constantcontact.com/oauth2/oauth/token';
    }

    /**
     * Get the authentication/login URL for oauth2 access.
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return 'https://oauth2.constantcontact.com/oauth2/oauth/siteowner/authorize';
    }

    /**
     * Retrieves and stores tokens returned from oAuthLogin.
     *
     * @param array $settings
     * @param array $parameters
     *
     * @return bool|string false if no error; otherwise the error string
     */
    public function authCallback($settings = [], $parameters = [])
    {
        // Constanct Contact doesn't like POST
        $settings['method'] = 'GET';

        return parent::authCallback($settings, $parameters);
    }

    /**
     * @return array
     */
    public function getAvailableLeadFields($settings = [])
    {
        if (!$this->isAuthorized()) {
            return [];
        }

        $fields = [
            'email',
            'prefix_name',
            'first_name',
            'last_name',
            'company_name',
            'job_title',
            'address_line1',
            'address_line2',
            'address_city',
            'address_state',
            'address_country_code',
            'address_postal_code',
            'cell_phone',
            'fax',
            'work_phone',
            'home_phone',
        ];

        $leadFields = [];
        foreach ($fields as $f) {
            $leadFields[$f] = [
                'label'    => $this->translator->trans('mautic.constantcontact.field.'.$f),
                'type'     => 'string',
                'required' => ('email' == $f) ? true : false,
            ];
        }

        $c = 1;
        while ($c <= 15) {
            $leadFields['customfield_'.$c] = [
                'label'    => $this->translator->trans('mautic.constantcontact.customfield.'.$f),
                'type'     => 'string',
                'required' => false,
            ];
            ++$c;
        }

        return $leadFields;
    }

    /**
     * @param $lead
     */
    public function pushLead($lead, $config = [])
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        $mappedData = $this->populateLeadData($lead, $config);

        if (empty($mappedData)) {
            return false;
        } elseif (empty($mappedData['email'])) {
            return false;
        } elseif (!isset($config['list_settings'])) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $email = $mappedData['email'];
                unset($mappedData['email']);

                $addresses    = [];
                $customfields = [];
                foreach ($mappedData as $k => $v) {
                    if (0 === strpos($v, 'address_')) {
                        $addresses[str_replace('address_', '', $k)] = $v;
                        unset($mappedData[$k]);
                    } elseif (0 === strpos($v, 'customfield_')) {
                        $key            = str_replace('customfield_', 'CustomField', $k);
                        $customfields[] = [
                            'name'  => $key,
                            'value' => $v,
                        ];
                        unset($mappedData[$k]);
                    }
                }

                if (!empty($addresses)) {
                    $addresses['address_type'] = 'PERSONAL';
                    $mappedData['addresses']   = $addresses;
                }

                if (!empty($customfields)) {
                    $mappedData['custom_fields'] = $customfields;
                }

                $options              = [];
                $options['action_by'] = (!empty($config['list_settings']['sendWelcome'])) ? 'ACTION_BY_VISITOR' : 'ACTION_BY_OWNER';
                $listId               = $config['list_settings']['list'];

                $this->getApiHelper()->subscribeLead($email, $listId, $mappedData, $options);

                return true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|null
     */
    public function getFormType()
    {
        return ConstantContactType::class;
    }
}
