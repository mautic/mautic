<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCrmBundle\Integration;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Entity\IntegrationEntity;

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
        $config = $this->mergeConfigToFeatureSettings(array());

        if(isset($config['sandbox'][0]) and $config['sandbox'][0] === 'sandbox')
        {
            return 'https://test.salesforce.com/services/oauth2/token';
        }

        return 'https://login.salesforce.com/services/oauth2/token';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getAuthenticationUrl()
    {
        $config = $this->mergeConfigToFeatureSettings(array());

        if(isset($config['sandbox'][0]) and $config['sandbox'][0] === 'sandbox')
        {
            return 'https://test.salesforce.com/services/oauth2/authorize';
        }

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
        return sprintf('%s/services/data/v34.0/sobjects',$this->keys['instance_url']);
    }

    /**
     * @return string
     */
    public function getQueryUrl()
    {
        return sprintf('%s/services/data/v34.0',$this->keys['instance_url']);
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
    public function amendLeadDataBeforeMauticPopulate($data, $object)
    {
        $settings['feature_settings']['objects']=$object;
        $fields = array_keys($this->getAvailableLeadFields($settings));

        $params['fields']=implode(',',$fields);

        $internal = array('latestDateCovered' => $data['latestDateCovered']);
        $count = 0;

        if(isset($data['records'])){

            foreach($data['records'] as $record)
            {
                foreach ($record as $key=>$item){
                    $dataObject[$key."__".$object] = $item;
                }

                $dataObject["internal__".$object] = $internal;

                if($dataObject){
                    $lead =$this->getMauticLead($dataObject,true,null,null);
                    $integrationEntityRepo = $this->factory->getEntityManager()->getRepository('MauticPluginBundle:IntegrationEntity');
                    $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', $object, 'lead', $lead->getId());

                    if ($integrationId == null) {
                        $integrationEntity = new IntegrationEntity();
                        $integrationEntity->setDateAdded(new \DateTime());
                        $integrationEntity->setIntegration('Salesforce');
                        $integrationEntity->setIntegrationEntity($object);
                        $integrationEntity->setIntegrationEntityId($record['Id']);
                        $integrationEntity->setInternalEntity('lead');
                        $integrationEntity->setInternalEntityId($lead->getId());
                        $this->factory->getEntityManager()->persist($integrationEntity);
                        $this->factory->getEntityManager()->flush($integrationEntity);
                    } else {

                        $integrationEntity =  $integrationEntityRepo->getEntity($integrationId[0]['id']);
                        $integrationEntity->setLastSyncDate(new \DateTime());
                        $this->factory->getEntityManager()->persist($integrationEntity);
                        $this->factory->getEntityManager()->flush($integrationEntity);

                    }

                    $count++;
                }
                unset($data);
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

            $builder->add('sandbox', 'choice', array(
                'choices'     => array(
                    'sandbox'    => 'mautic.salesforce.integration.form.feature.sandbox'
                ),
                'expanded'    => true,
                'multiple'    => true,
                'label'       => 'mautic.plugins.salesforce.sandbox',
                'label_attr'  => array('class' => 'control-label'),
                'empty_value' => false,
                'required'    => false,
                'attr'       => array(
                    'onclick' => 'Mautic.postForm(mQuery(\'form[name="integration_details"]\'),\'\');'
                ),
            ));

            $builder->add('objects', 'choice' , array(
                'choices'     => array(
                    'Lead'    => 'Lead',
                    'Contact' => 'Contact',
                    'Activity'=> 'Activity'
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
     * @param array  $fields
     * @param array  $keys
     * @param string $object
     */
    public function cleanSalesForceData($fields, $keys, $object){
    $leadFields = array();

        foreach ($keys as $key){
            if(strstr($key,'__'.$object)){
                $newKey = str_replace('__'.$object,'',$key);
                $leadFields[$object][$newKey] = $fields['leadFields'][$key];
            }
        }

        return $leadFields;
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

        $object = 'Lead';//Salesforce objects, default is Lead

        $fields = array_keys($config['leadFields']);

        $leadFields = $this->cleanSalesForceData($config,$fields, $object);

        $mappedData[$object] = $this->populateLeadData($lead, array('leadFields' => $leadFields[$object]));
        $this->amendLeadDataBeforePush($mappedData[$object]);

        if (empty($mappedData[$object])) {
            return false;
        }

        try {
            if ($this->isAuthorized()) {
                $createdLeadData = $this->getApiHelper()->createLead($mappedData[$object], $lead);
                if ($createdLeadData['Id']){

                    $integrationEntityRepo = $this->factory->getEntityManager()->getRepository('MauticPluginBundle:IntegrationEntity');
                    $integrationId = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', $object, 'leads', $lead->getId());

                    if (empty($integrationId)) {
                        $integrationEntity = new IntegrationEntity();
                        $integrationEntity->setDateAdded(new \DateTime());
                        $integrationEntity->setIntegration('Salesforce');
                        $integrationEntity->setIntegrationEntity($object);
                        $integrationEntity->setIntegrationEntityId($createdLeadData['Id']);
                        $integrationEntity->setInternalEntity('Lead');
                        $integrationEntity->setInternalEntityId($lead->getId());
                    } else {
                        $integrationEntity =  $integrationEntityRepo->getEntity($integrationId[0]['id']);
                    }
                    $integrationEntity->setLastSyncDate(new \DateTime());
                    $this->factory->getEntityManager()->persist($integrationEntity);
                    $this->factory->getEntityManager()->flush($integrationEntity);

                }
                return true;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }
        return false;
    }

    /**
     * @param $lead
     */
    public function getLeads($params = array())
    {
        $executed = null;

        $config = $this->mergeConfigToFeatureSettings(array());

        $salesForceObjects[] = "Lead";

        if(isset($config['objects'])){
            $salesForceObjects = $config['objects'];
        }

        $query = $this->getFetchQuery($params);

        try {
            if ($this->isAuthorized()) {
                foreach ($salesForceObjects as $object){
                    $result = $this->getApiHelper()->getLeads($query, $object);

                    $executed+= $this->amendLeadDataBeforeMauticPopulate($result, $object);
                }
                return $executed;
            }
        } catch (\Exception $e) {
            $this->logIntegrationError($e);
        }

        return $executed;
    }

    public function pushLeadActivity($params = array())
    {
        $executed = null;

        $query = $this->getFetchQuery($params);

        $config = $this->mergeConfigToFeatureSettings(array());

        $salesForceObjects[] = "Lead";

        if(isset($config['objects'])){
            $salesForceObjects = $config['objects'];
        }

        foreach ($salesForceObjects as $object) {
            $integrationEntityRepo = $this->factory->getEntityManager()->getRepository(
                'MauticPluginBundle:IntegrationEntity'
            );
            $salesForceIds         = $integrationEntityRepo->getIntegrationsEntityId('Salesforce', $object, 'lead');

            $startDate = new \DateTime($query['start']);
            $endDate   = new \DateTime($query['end']);
            try {
                if ($this->isAuthorized()) {
                    if (!empty($salesForceIds)) {

                        foreach ($salesForceIds as $ids) {

                            $salesForceLeadData[$ids['integration_entity_id']]           = $this->getLeadData(
                                $startDate,
                                $endDate,
                                $ids['internal_entity_id']
                            );
                            $salesForceLeadData[$ids['integration_entity_id']]['id']     = $ids['integration_entity_id'];
                            $salesForceLeadData[$ids['integration_entity_id']]['leadId'] = $ids['internal_entity_id'];
                        }

                        $result = $this->getApiHelper()->createLeadActivity($salesForceLeadData, $object);
                    }

                    return $executed;
                }
            } catch (\Exception $e) {
                $this->logIntegrationError($e);
            }
        }
        return $executed;
    }

    /**
     * Create or update existing Mautic lead from the integration's profile data
     *
     * @param mixed      $data    Profile data from integration
     * @param bool|true  $persist Set to false to not persist lead to the database in this method
     * @param array|null $socialCache
     * @param mixed||null $identifiers
     *
     * @return Lead
     */
    public function getMauticLead($data, $persist = true, $socialCache = null, $identifiers = null)
    {
        if (is_object($data)) {
            // Convert to array in all levels
            $data = json_encode(json_decode($data), true);
        } elseif (is_string($data)) {
            // Assume JSON
            $data = json_decode($data, true);
        }
        $config = $this->mergeConfigToFeatureSettings(array());
        // Match that data with mapped lead fields
        $matchedFields = $this->populateMauticLeadData($data, $config);
       
        if (empty($matchedFields)) {

            return;
        }

        // Find unique identifier fields used by the integration
        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel           = $this->factory->getModel('lead');
        $uniqueLeadFields    = $this->factory->getModel('lead.field')->getUniqueIdentiferFields();
        $uniqueLeadFieldData = array();

        foreach ($matchedFields as $leadField => $value) {
            if (array_key_exists($leadField, $uniqueLeadFields) && !empty($value)) {
                $uniqueLeadFieldData[$leadField] = $value;
            }
        }

        // Default to new lead
        $lead = new Lead();
        $lead->setNewlyCreated(true);

        if (count($uniqueLeadFieldData)) {

            $existingLeads = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead')
                ->getLeadsByUniqueFields($uniqueLeadFieldData);

            if (!empty($existingLeads)) {
                $lead = array_shift($existingLeads);
                // Update remaining leads
                if (count($existingLeads)) {
                    foreach ($existingLeads as $existingLead) {
                        $existingLead->setLastActive(new \DateTime());
                    }
                }
            }
        }

        $leadModel->setFieldValues($lead, $matchedFields, false, false);

        // Update the social cache
        $leadSocialCache = $lead->getSocialCache();
        if (!isset($leadSocialCache[$this->getName()])) {
            $leadSocialCache[$this->getName()] = array();
        }
        $leadSocialCache[$this->getName()] = array_merge($leadSocialCache[$this->getName()], $socialCache);

        // Check for activity while here
        if (null !== $identifiers && in_array('public_activity', $this->getSupportedFeatures())) {
            $this->getPublicActivity($identifiers, $leadSocialCache[$this->getName()]);
        }

        $lead->setSocialCache($leadSocialCache);

        // Update the internal info integration object that has updated the record
        if(isset($data['internal'])){
            $internalInfo = $lead->getInternal();
            $internalInfo[$this->getName()] = $data['internal'];
            $lead->setInternal($internalInfo);
        }

        $lead->setLastActive(new \DateTime());

        if ($persist) {
            // Only persist if instructed to do so as it could be that calling code needs to manipulate the lead prior to executing event listeners
            $leadModel->saveEntity($lead, false);
        }

        return $lead;
    }

    public function getLeadData(\DateTime $startDate = null, \DateTime $endDate = null, $leadId)
    {
        $leadModel = $this->factory->getModel('lead');

        $baseURL = $this->factory->getParameter('site_url');

        $activity['leadUrl'] = $baseURL.$this->factory->getRouter()->generate('mautic_contact_action', array('objectAction' => 'view', 'objectId' => $leadId));

        $pointsRepo = $leadModel->getPointLogRepository();
        $activity['records'] = $pointsRepo->getLeadTimelineEvents($leadId);

        $emailModel = $this->factory->getModel('email');
        $emailRepo = $emailModel->getStatRepository();
        $emails = $emailRepo->getLeadStatsByDate($leadId, $startDate, $endDate);

        $i=count($activity['activities']);

        foreach ($emails as $row) {

            // Convert to local from UTC
            $activity['records'][$i]['eventName'] = $this->factory->getTranslator()->trans('mautic.email.sent');
            $activity['records'][$i]['actionName'] = $this->factory->getTranslator()->trans('mautic.email.form.internal.name').': '.$row['name'];
            $activity['records'][$i]['dateAdded'] = $row['dateSent'];
            $activity['records'][$i]['subject'] = $row['subject'];
            $activity['records'][$i]['id'] = 'emailStat'.$row['email_id'];
            $i++;
        }

        $formSubmissionModel = $this->factory->getModel('form.submission');

        /** @var \Mautic\FormBundle\Entity\SubmissionRepository $submissionRepository */
        $submissionRepo = $formSubmissionModel->getRepository();
        $submissions = $submissionRepo->getSubmissions(array ('leadId' => $leadId, 'fromDate' => $startDate, 'toDate' => $endDate));

        foreach ($submissions as $row) {
            $submission = $submissionRepo->getEntity($row['id']);
            // Convert to local from UTC
            $activity['records'][$i]['eventName'] = $this->factory->getTranslator()->trans('mautic.form.event.submitted');
            $activity['records'][$i]['actionName'] = $submission->getForm()->getName();

            $dtHelper = $this->factory->getDate($row['dateSubmitted'], 'Y-m-d H:i:s', 'UTC');
            $activity['records'][$i]['dateAdded'] = $dtHelper->getLocalDateTime();
            $activity['records'][$i]['id'] = 'formSubmission'.$row['id'];
            $i++;
        }
        return $activity;
    }
}
