<?php
namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\LeadBundle\MauticLeadBundle;
use Mautic\PluginBundle\Exception\ApiErrorException;

class SalesforceApi extends CrmApi
{
    protected $object  = 'Lead';
    protected $requestSettings = array(
        'encode_parameters' => 'json'
    );

    public function request($operation, $elementData = array(), $method = 'GET', $retry = false, $object = null)
    {
        if(!$object){
            $object = $this->object;
        }
        $request_url = sprintf($this->integration->getApiUrl() . '/%s/%s', $object, $operation);
        $response = $this->integration->makeRequest($request_url, $elementData, $method, $this->requestSettings);

      if (!empty($response['errors'])) {
            throw new ApiErrorException(implode(', ', $response['errors']));
        } elseif (is_array($response)) {
            $errors = array();
            foreach ($response as $r) {
                if (is_array($r) && !empty($r['errorCode']) && !empty($r['message'])) {
                    // Check for expired session then retry if we can refresh
                    if ($r['errorCode'] == 'INVALID_SESSION_ID' && !$retry) {
                        $refreshError = $this->integration->authCallback(array('use_refresh_token' => true));

                        if (empty($refreshError)) {
                            return $this->request($operation, $elementData, $method, true);
                        }
                    }

                    $errors[] = $r['message'];
                }
            }

            if (!empty($errors)) {
                throw new ApiErrorException(implode(', ', $errors));
            }
        }

        return $response;
    }

    /**
     * @return mixed
     */
    public function getLeadFields()
    {
        return $this->request('describe');
    }

    /**
     * Creates Salesforce lead
     *
     * @param array $data
     *
     * @return mixed
     */
    public function createLead(array $data, $lead)
    {
        $createdLeadData =  $this->request('', $data, 'POST');
        //todo: check if push activities is selected in config

        return $createdLeadData;
    }

    public function createLeadActivity(array $salesForceLeadData, $lead)
    {
        $mActivityObjectName = 'Mautic_timeline__c';

        // get lead's point activity
        $pointsRepo = $this->integration->getLeadData('points', $lead);
        $leadUrl = $pointsRepo['leadUrl'];
        if(!empty($pointsRepo))
        {
            $deltaType = '';
            foreach ($pointsRepo as $pointActivity)
            {
                if($pointActivity['delta']>0)
                {
                    $deltaType = 'added';
                }
                else
                {
                    $deltaType = 'subtracted';
                }

                $activityData = array(
                    'ActivityDate__c'   => $pointActivity['dateAdded']->format('c'),
                    'Description__c'    => $pointActivity['type'].":".$pointActivity['eventName']." ".$deltaType." ".$pointActivity['actionName'],
                    'WhoId__c'          => $salesForceLeadData['id'],
                    'Name'           => 'Mautic TimeLine Activity',
                    'MauticLead__c'     => $lead->getId(),
                    'Mautic_url__c'     => $leadUrl
                );
                //todo: log posted activities so that they don't get sent over again
                $this->request('', $activityData, 'POST', false, $mActivityObjectName);
            }
        }
    }

    /**
     * Get Salesforce leads
     *
     * @param string $query
     *
     * @return mixed
     */
    public function getLeads($query, $object)
    {
        return $this->request('updated/', $query, 'GET', false, $object);
    }

    /**
     * Get Salesforce leads
     *
     * @param string $query
     *
     * @return mixed
     */
    public function getSalesForceLeadById($id, $params, $object)
    {
        return $this->request($id.'/',$params, 'GET', false,$object);
    }
}