<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration;

/**
 * Class HubspotIntegration
 */
class HubspotIntegration extends CrmAbstractIntegration
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Hubspot';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            $this->getApiKey()   => 'mautic.hubspot.form.apikey'
        );
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return 'hapikey';
    }

    // *
    //  * Get the array key for the auth token
    //  *
    //  * @return string
    public function getAuthTokenKey()
    {
        return 'hapikey';
    }
    /**
     * Amend mapped lead data before pushing to CRM
     *
     * @param $mappedData
     */
    public function amendLeadDataBeforePush(&$mappedData)
    {
        $leadData = $mappedData;
        $mappedData = array();
        foreach($leadData as $field => $value) {
            $mappedData["properties"][] = array(
                "property" => $field,
                "value" => $value,
            );
        }
    }

    /**
     * @return array
     */
    public function getFormSettings()
    {
        return array(
            'requires_callback'      => false,
            'requires_authorization' => false
        );
    }
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'key';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return 'https://api.hubapi.com';
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = array())
    {
        $hubsFields = array();
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        try {
            if ($this->isAuthorized()) {
                $leadFields  = $this->getApiHelper()->getLeadFields();

                if (isset($leadFields)) {
                    foreach ($leadFields as $fieldInfo) {
                        $hubsFields[$fieldInfo['name']] = array(
                            'type'     => 'string',
                            'label'    => $fieldInfo['label']
                        );
                    }
                }
                // Email is Required for this kind of integration
                $hubsFields['email']['required'] = true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
            }
        }

        return $hubsFields;
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

    public function getHubSpotApiKey(){
        $tokenData = $this->getKeys();
        return $tokenData[$this->getAuthTokenKey()];
    }
}
