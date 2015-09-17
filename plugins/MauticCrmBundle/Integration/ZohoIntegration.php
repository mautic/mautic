<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\CoreBundle\Helper\InputHelper;

/**
 * Class ZohoIntegration
 */
class ZohoIntegration extends CrmAbstractIntegration
{

    /**
     * Returns the name of the social integration that must match the name of the file
     *
     * @return string
     */
    public function getName ()
    {
        return 'Zoho';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields ()
    {
        return array(
            $this->getClientIdKey()     => 'mautic.zoho.form.email',
            $this->getClientSecretKey() => 'mautic.zoho.form.password'
        );
    }

    /**
     * @return string
     */
    public function getClientIdKey ()
    {
        return 'EMAIL_ID';
    }

    /**
     * @return string
     */
    public function getClientSecretKey ()
    {
        return 'PASSWORD';
    }

    /**
     * @return string
     */
    public function getAuthTokenKey ()
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
        return array(
            'requires_callback'      => false,
            'requires_authorization' => true
        );
    }

    /**
     * @return bool
     */
    public function authCallback($settings = array(), $parameters = array())
    {
        $request_url = 'https://accounts.zoho.com/apiauthtoken/nb/create';
        $parameters  = array(
            'SCOPE'    => 'ZohoCRM/crmapi',
            'EMAIL_ID' => $this->keys[$this->getClientIdKey()],
            'PASSWORD' => $this->keys[$this->getClientSecretKey()]
        );

        $response = $this->makeRequest($request_url, $parameters, 'GET', array('authorize_session' => true));

        if ($response['RESULT'] == 'FALSE') {
            return $this->factory->getTranslator()->trans("mautic.integration.error.genericerror", array(), "flashes");
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
                return array();
            }
        } else {
            return parent::parseCallbackResponse($data, $postAuthorization);
        }
    }

    /**
     * @return array
     */
    public function getAvailableLeadFields ($settings = array())
    {
        $zohoFields        = array();
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        try {
            if ($this->isAuthorized()) {
                $leadObject = $this->getApiHelper()->getLeadFields();

                if ($leadObject == null || (isset($leadObject['response']) && isset($leadObject['response']['error']))) {
                    return array();
                }

                $zohoFields = array();
                foreach ($leadObject['Leads']['section'] as $optgroup) {
                    //$zohoFields[$optgroup['dv']] = array();
                    if (!array_key_exists(0, $optgroup['FL']))
                        $optgroup['FL'] = array($optgroup['FL']);
                    foreach ($optgroup['FL'] as $field) {
                        if (!(bool)$field['isreadonly'] || in_array($field['type'], array('Lookup', 'OwnerLookup', 'Boolean'))) {
                            continue;
                        }
                        $key              = InputHelper::alphanum($field['dv']);
                        $zohoFields[$key] = array(
                            'type'     => 'string',
                            'label'    => $field['label'],
                            'dv'       => $field['dv'],
                            'required' => ($field['req'] == 'true')
                        );
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

        return $zohoFields;
    }

    /**
     * {@inheritdoc}
     *
     * @param $key
     * @param $field
     *
     * @return mixed
     */
    public function convertLeadFieldKey($key, $field)
    {
        return $field['dv'];
    }

    /**
     * {@inheritdoc}
     *
     * @param $lead
     * @param $config
     *
     * @return array
     */
    public function populateLeadData ($lead, $config = array())
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
}
