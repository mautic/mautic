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

use Mautic\CoreBundle\Helper\InputHelper;

/**
 * Class ZohoIntegration.
 */
class ZohoIntegration extends CrmAbstractIntegration
{
    /**
     * Returns the name of the social integration that must match the name of the file.
     *
     * @return string
     */
    public function getName()
    {
        return 'Zoho';
    }

    /**
     * @return array
     */
    public function getSupportedFeatures()
    {
        return ['push_lead'];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return [
            $this->getClientIdKey()     => 'mautic.zoho.form.email',
            $this->getClientSecretKey() => 'mautic.zoho.form.password',
        ];
    }

    /**
     * @return string
     */
    public function getClientIdKey()
    {
        return 'EMAIL_ID';
    }

    /**
     * @return string
     */
    public function getClientSecretKey()
    {
        return 'PASSWORD';
    }

    /**
     * @return string
     */
    public function getAuthTokenKey()
    {
        return 'AUTHTOKEN';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://crm.zoho.com/crm/private/json';
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
     * @return bool
     */
    public function authCallback($settings = [], $parameters = [])
    {
        $request_url = 'https://accounts.zoho.com/apiauthtoken/nb/create';
        $parameters  = [
            'SCOPE'    => 'ZohoCRM/crmapi',
            'EMAIL_ID' => $this->keys[$this->getClientIdKey()],
            'PASSWORD' => $this->keys[$this->getClientSecretKey()],
        ];

        $response = $this->makeRequest($request_url, $parameters, 'GET', ['authorize_session' => true]);

        if ($response['RESULT'] == 'FALSE') {
            return $this->factory->getTranslator()->trans('mautic.zoho.auth_error', ['%cause%' => (isset($response['CAUSE']) ? $response['CAUSE'] : 'UNKNOWN')]);
        }

        return $this->extractAuthKeys($response);
    }

    /**
     * {@inheritdoc}
     *
     * @param string $data
     * @param bool   $postAuthorization
     *
     * @return mixed
     */
    public function parseCallbackResponse($data, $postAuthorization = false)
    {
        if ($postAuthorization) {
            /*
            #
            #Wed Feb 29 03:07:33 PST 2012
            AUTHTOKEN=bad18eba1ff45jk7858b8ae88a77fa30
            RESULT=TRUE
            */
            preg_match_all('(\w*=\w*)', $data, $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $string_attribute) {
                    $parts                 = explode('=', $string_attribute);
                    $attributes[$parts[0]] = $parts[1];
                }

                return $attributes;
            } else {
                return [];
            }
        } else {
            return parent::parseCallbackResponse($data, $postAuthorization);
        }
    }

    /**
     * @return array
     */
    public function getAvailableLeadFields($settings = [])
    {
        if ($fields = parent::getAvailableLeadFields($settings)) {
            return $fields;
        }

        $zohoFields        = [];
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        try {
            if ($this->isAuthorized()) {
                $leadObject = $this->getApiHelper()->getLeadFields();

                if ($leadObject == null || (isset($leadObject['response']) && isset($leadObject['response']['error']))) {
                    return [];
                }

                $zohoFields = [];
                foreach ($leadObject['Leads']['section'] as $optgroup) {
                    //$zohoFields[$optgroup['dv']] = array();
                    if (!array_key_exists(0, $optgroup['FL'])) {
                        $optgroup['FL'] = [$optgroup['FL']];
                    }
                    foreach ($optgroup['FL'] as $field) {
                        if (!(bool) $field['isreadonly'] || in_array($field['type'], ['Lookup', 'OwnerLookup', 'Boolean'])) {
                            continue;
                        }

                        $zohoFields[$this->getFieldKey($field['dv'])] = [
                            'type'     => 'string',
                            'label'    => $field['label'],
                            'dv'       => $field['dv'],
                            'required' => ($field['req'] == 'true'),
                        ];
                    }
                }
            }
        } catch (ErrorException $exception) {
            $this->logIntegrationError($exception);

            if (!$silenceExceptions) {
                throw $exception;
            }

            return false;
        }

        $this->cache->set('leadFields', $zohoFields);

        return $zohoFields;
    }

    /**
     * {@inheritdoc}
     *
     * @param $key
     * @param $field
     *
     * @return array
     */
    public function convertLeadFieldKey($key, $field)
    {
        return [$this->getFieldKey($field['dv']), $field['dv']];
    }

    /**
     * @param $lead
     * @param $config
     *
     * @return array
     */
    public function populateLeadData($lead, $config = [])
    {
        $mappedData = parent::populateLeadData($lead, $config);

        $xmlData = '<Leads>';
        $xmlData .= '<row no="1">';
        foreach ($mappedData as $name => $value) {
            $xmlData .= sprintf('<FL val="%s"><![CDATA[%s]]></FL>', $name, $value);
        }
        $xmlData .= '</row>';
        $xmlData .= '</Leads>';

        return $xmlData;
    }

    /**
     * @param $dv
     *
     * @return string
     */
    protected function getFieldKey($dv)
    {
        return InputHelper::alphanum(InputHelper::transliterate($dv));
    }
}
