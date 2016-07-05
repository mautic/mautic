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
        $salesForceobjects = array();

        if(isset($settings['feature_settings']['objects'])) {
            $salesForceobjects = $settings['feature_settings']['objects'];
        }

        try {
            if ($this->isAuthorized()) {
                if(!empty($salesForceobjects)){
                foreach ($salesForceobjects as $sfObject){
                    $leadObject[$sfObject]  = $this->getApiHelper()->getLeadFields($sfObject);
                    if (!empty($leadObject) && isset($leadObject[$sfObject]['fields'])) {

                        foreach ($leadObject[$sfObject]['fields'] as $fieldInfo) {
                            if (!$fieldInfo['updateable'] || !isset($fieldInfo['name']) || in_array($fieldInfo['type'], array('reference', 'boolean'))) {
                                continue;
                            }

                            $salesFields[$fieldInfo['name'].' - '.$sfObject] = array(
                                'type'     => 'string',
                                'label'    => $sfObject.' - '.$fieldInfo['label'],
                                'required' => (empty($fieldInfo['nillable']) && !in_array($fieldInfo['name'], array('Status')))
                            );
                        }
                    }
                }
                }else{
                    $leadObject  = $this->getApiHelper()->getLeadFields('Lead');
                    if (!empty($leadObject) && isset($leadObject['fields'])) {

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

    /**
     * @param \Mautic\PluginBundle\Integration\Form|FormBuilder $builder
     * @param array                                             $data
     * @param string                                            $formArea
     */
    public function appendToForm(&$builder, $data, $formArea)
    {
        if ($formArea == 'features') {
            $name = strtolower($this->getName());

            $builder->add('objects', 'choice' , array(
                'choices'     => array(
                    'Lead'    => 'Lead',
                    'Contact' => 'Contact'
                ),
                'expanded'    => true,
                'multiple'    => true,
                'label'       => 'mautic.plugins.salesforce.choose.objects.to.pull.from',
                'label_attr'  => array('class' => 'control-label'),
                'empty_value' => false,
                'required'    => false,
                'attr'        => array(
                    'class'   => 'form-control not-chosen'
                )
            ));

        }
    }
    /**
     * @param $lead
     */
    public function pushLead($lead, $config = array())
    {
        $config = $this->mergeConfigToFeatureSettings($config);

        if (empty($config['leadFields'])) {
            return array();
        }

        if (empty($config['objects'])) {
            $objects = ['Lead'];//Salesforce objects, default is Lead
        }
        else{
            $objects = $config['objects'];
        }

        $fields = array_keys($config['leadFields']);
        foreach ($objects as $object){
            foreach ($fields as $key){
                if(strstr($key,'__'.$object)){
                    $newKey = str_replace('__'.$object,'',$key);
                    $leadFields[$object][$newKey] = $config['leadFields'][$key];
                }
            }
            $mappedData[$object] = $this->populateLeadData($lead, array('leadFields' => $leadFields[$object]));
            $this->factory->getLogger()->addError(print_r($mappedData[$object],true));

            $this->amendLeadDataBeforePush($mappedData[$object]);

            if (empty($mappedData[$object])) {
                return false;
            }

            try {
                if ($this->isAuthorized()) {
                    $this->getApiHelper()->createLead($mappedData[$object]);
                    return true;
                }
            } catch (\Exception $e) {
                $this->logIntegrationError($e);
            }
        }

        return false;
    }
}
