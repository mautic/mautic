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
 * Class SalesforceIntegration
 */
class SalesforceIntegration extends CrmAbstractIntegration
{

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName()
    {
        return 'Salesforce';
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
     * Get the array key for the auth token
     *
     * @return string
     */
    public function getAuthTokenKey ()
    {
        return 'access_token';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRequiredKeyFields()
    {
        return array(
            'client_id'      => 'mautic.integration.keyfield.consumerid',
            'client_secret'  => 'mautic.integration.keyfield.consumersecret'
        );
    }

    /**
     * Get the keys for the refresh token and expiry
     *
     * @return array
     */
    public function getRefreshTokenKeys ()
    {
        return array('refresh_token', '');
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAccessTokenUrl()
    {
        return 'https://login.salesforce.com/services/oauth2/token';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        return 'https://login.salesforce.com/services/oauth2/authorize';
    }

    /**
     * @return string
     */
    public function getAuthScope()
    {
        return 'api refresh_token';
    }

    /**
     * @return string
     */
    public function getApiUrl()
    {
        return sprintf('%s/services/data/v32.0/sobjects',$this->keys['instance_url']);
    }

    /**
     * {@inheritdoc}
     *
     * @param bool $inAuthorization
     */
    public function getBearerToken($inAuthorization = false)
    {
        if (!$inAuthorization) {
            return $this->keys[$this->getAuthTokenKey()];
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'oauth2';
    }

    /**
     * @return array|mixed
     */
    public function getAvailableLeadFields($settings = array())
    {
        $salesFields = array();
        $silenceExceptions = (isset($settings['silence_exceptions'])) ? $settings['silence_exceptions'] : true;
        try {
            if ($this->isAuthorized()) {
                $leadObject  = $this->getApiHelper()->getLeadFields();

                if ($leadObject != null && isset($leadObject['fields'])) {

                    foreach ($leadObject['fields'] as $fieldInfo) {
                        if (!$fieldInfo['updateable'] || !isset($fieldInfo['name']) || in_array($fieldInfo['type'], array('reference', 'boolean'))) {
                            continue;
                        }

                        $salesFields[$fieldInfo['name']] = array(
                            'type'     => 'string',
                            'label'    => $fieldInfo['label'],
                            'required' => (empty($fieldInfo['nillable']) && !in_array($fieldInfo['name'], array('Status')))
                        );
                    }
                }
            }
       } catch (\Exception $e) {
            $this->logIntegrationError($e);

            if (!$silenceExceptions) {
                throw $e;
           }
       }

        return $salesFields;
    }

    /**
     * {@inheritdoc}
     *
     * @param $section
     *
     * @return string
     */
    public function getFormNotes($section)
    {
        if ($section == 'authorization') {
            return array('mautic.salesforce.form.oauth_requirements', 'warning');
        }

        return parent::getFormNotes($section);
    }

    public function getFetchQuery($params){
        
        $dateRange=$params;
        return $dateRange;
    }

    /**
     * Amend mapped lead data before creating to Mautic
     *
     * @param $data
     */
    public function amendLeadDataBeforeMauticPopulate($data)
    {
        $fields = array_keys($this->getAvailableLeadFields());
        $params['fields']=implode(',',$fields);

        $internal = array('latestDateCovered' => $data['latestDateCovered']);
        $count = 0;
        if(isset($data['ids'])){
            foreach($data['ids'] as $salesforceId)
            {
                $data = $this->getApiHelper()->getSalesForceLeadById($salesforceId,$params);
                $data['internal'] = $internal;
                if($data){
                    $this->getMauticLead($data,true,null,null);
                    $count++;
                }
            }
        }
        return $count;
    }

}
