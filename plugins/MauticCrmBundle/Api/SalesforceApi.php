<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;

/**
 * @property SalesforceIntegration $integration
 */
class SalesforceApi extends CrmApi
{
    protected $object          = 'Lead';
    protected $requestSettings = [
        'encode_parameters' => 'json',
    ];
    protected $apiRequestCounter = 0;

    public function __construct(CrmAbstractIntegration $integration)
    {
        parent::__construct($integration);

        $this->requestSettings['curl_options'] = [
            CURLOPT_SSLVERSION => defined('CURL_SSLVERSION_TLSv1_1') ? CURL_SSLVERSION_TLSv1_1 : CURL_SSLVERSION_TLSv1_1,
        ];
    }

    /**
     * @param        $operation
     * @param array  $elementData
     * @param string $method
     * @param bool   $retry
     * @param null   $object
     * @param null   $queryUrl
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function request($operation, $elementData = [], $method = 'GET', $retry = false, $object = null, $queryUrl = null)
    {
        if (!$object) {
            $object = $this->object;
        }

        $requestUrl = (!$queryUrl) ? sprintf($this->integration->getApiUrl().'/%s/%s', $object, $operation) : sprintf($queryUrl.'/%s', $operation);
        $settings   = $this->requestSettings;
        if ($method == 'PATCH') {
            $settings['headers'] = ['Sforce-Auto-Assign' => 'FALSE'];
        }

        // Wrap in a isAuthorized to refresh token if applicable
        $response = $this->integration->makeRequest($requestUrl, $elementData, $method, $settings);
        ++$this->apiRequestCounter;

        if (!empty($response['errors'])) {
            throw new ApiErrorException(implode(', ', $response['errors']));
        } elseif (is_array($response)) {
            $errors = [];
            foreach ($response as $r) {
                if (is_array($r) && !empty($r['errorCode']) && !empty($r['message'])) {
                    // Check for expired session then retry if we can refresh
                    if ($r['errorCode'] == 'INVALID_SESSION_ID' && !$retry) {
                        $refreshError = $this->integration->authCallback(['use_refresh_token' => true]);

                        if (empty($refreshError)) {
                            return $this->request($operation, $elementData, $method, true, $object, $queryUrl);
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
     * @param null|string $object
     *
     * @return mixed
     */
    public function getLeadFields($object = null)
    {
        if ($object == 'company') {
            $object = 'Account'; //salesforce object name
        }

        return $this->request('describe', [], 'GET', false, $object);
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function getPerson(array $data)
    {
        $config          = $this->integration->mergeConfigToFeatureSettings([]);
        $queryUrl        = $this->integration->getQueryUrl();
        $sfRecords = [
            'Contact' => [],
            'Lead'    => []
        ];

        //try searching for lead as this has been changed before in updated done to the plugin
        if (isset($config['objects']) && false !== array_search('Contact', $config['objects']) && !empty($data['Contact']['Email'])) {
            $findContact = 'select Id from Contact where email = \''.str_replace("'", "\'", $this->integration->cleanPushData($data['Contact']['Email'])).'\'';
            $response    = $this->request('query', ['q' => $findContact], 'GET', false, null, $queryUrl);

            if (!empty($response['records'])) {
                $sfRecords['Contact'] = $response['records'];
            }
        }

        if (!empty($data['Lead']['Email'])) {
            $findLead = 'select Id from Lead where email = \''.str_replace("'", "\'", $this->integration->cleanPushData($data['Lead']['Email'])).'\' and ConvertedContactId = NULL';
            $response = $this->request('query', ['q' => $findLead], 'GET', false, null, $queryUrl);

            if (!empty($response['records'])) {
                $sfRecords['Lead'] = $response['records'];
            }
        }

        return $sfRecords;
    }

    /**
     * Creates Salesforce lead.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function createLead(array $data)
    {
        $createdLeadData = [];

        if (isset($data['Email'])) {
            $createdLeadData = $this->createObject($data, 'Lead');
        }

        return $createdLeadData;
    }

    /**
     * @param array $data
     * @param       $sfObject
     *
     * @return mixed|string
     */
    public function createObject(array $data, $sfObject)
    {
        $objectData = $this->request('', $data, 'POST', false, $sfObject);
        $this->integration->getLogger()->debug('SALESFORCE: POST createObject '.$sfObject.' '.var_export($data, true).var_export($objectData, true));

        if (isset($objectData['id'])) {
            // Salesforce is inconsistent it seems
            $objectData['Id'] = $objectData['id'];
        }

        return $objectData;
    }

    /**
     * @param array $data
     * @param       $sfObject
     * @param       $sfObjectId
     *
     * @return array
     */
    public function updateObject(array $data, $sfObject, $sfObjectId)
    {
        $objectData = $this->request('', $data, 'PATCH', false, $sfObject.'/'.$sfObjectId);
        $this->integration->getLogger()->debug('SALESFORCE: PATCH updateObject '.$sfObject.' '.var_export($data, true).var_export($objectData, true));

        // Salesforce is inconsistent it seems
        $objectData['Id'] = $objectData['id'] = $sfObjectId;

        return $objectData;
    }

    /**
     * @param array $data
     *
     * @return mixed|string
     */
    public function syncMauticToSalesforce(array $data)
    {
        $queryUrl = $this->integration->getCompositeUrl();

        return $this->request('composite/', $data, 'POST', false, null, $queryUrl);
    }

    /**
     * @param array $activity
     * @param       $object
     *
     * @return array|mixed|string
     */
    public function createLeadActivity(array $activity, $object)
    {
        $config   = $this->integration->getIntegrationSettings()->getFeatureSettings();
        $contacts = $leads = [];

        $namespace           = (!empty($config['namespace'])) ? $config['namespace'].'__' : '';
        $mActivityObjectName = $namespace.'mautic_timeline__c';

        if (!empty($activity)) {
            foreach ($activity as $sfId => $records) {
                foreach ($records['records'] as $key => $record) {
                    $activityData['records'][$key] = [
                        'attributes' => [
                            'type'        => $mActivityObjectName,
                            'referenceId' => $record['id'].'-'.$sfId,
                        ],
                        $namespace.'ActivityDate__c' => $record['dateAdded']->format('c'),
                        $namespace.'Description__c'  => $record['description'],
                        'Name'                       => $record['name'],
                        $namespace.'Mautic_url__c'   => $records['leadUrl'],
                        $namespace.'ReferenceId__c'  => $record['id'].'-'.$sfId,
                    ];

                    if ($object === 'Lead') {
                        $activityData['records'][$key][$namespace.'WhoId__c'] = $sfId;
                    } elseif ($object === 'Contact') {
                        $activityData['records'][$key][$namespace.'contact_id__c'] = $sfId;
                    }
                }
            }

            if (!empty($activityData)) {
                //todo: log posted activities so that they don't get sent over again
                $queryUrl = $this->integration->getQueryUrl();
                $results  = $this->request(
                    'composite/tree/'.$mActivityObjectName,
                    $activityData,
                    'POST',
                    false,
                    null,
                    $queryUrl
                );

                return $results;
            }

            return [];
        }
    }

    /**
     * Get Salesforce leads.
     *
     * @param array  $query
     * @param string $object
     *
     * @return mixed
     */
    public function getLeads($query, $object)
    {
        $organizationCreatedDate = $this->getOrganizationCreatedDate();
        $queryUrl                = $this->integration->getQueryUrl();
        $ignoreConvertedLeads    = '';
        if (isset($query['start'])) {
            if (strtotime($query['start']) < strtotime($organizationCreatedDate)) {
                $query['start'] = date('c', strtotime($organizationCreatedDate.' +1 hour'));
            }
        }

        $fields = $this->integration->getIntegrationSettings()->getFeatureSettings();
        switch ($object) {
            case 'company':
            case 'Account':
              $fields = array_keys(array_filter($fields['companyFields']));
                break;
            default:
                $mixedFields = array_filter($fields['leadFields']);
                $fields      = [];
                foreach ($mixedFields as $sfField => $mField) {
                    if (strpos($sfField, '__'.$object) !== false) {
                        $fields[] = str_replace('__'.$object, '', $sfField);
                    }
                    if (strpos($sfField, '-'.$object) !== false) {
                        $fields[] = str_replace('-'.$object, '', $sfField);
                    }
                }
        }

        if (!empty($query['nextUrl'])) {
            $query  = str_replace('/services/data/v34.0/query', '', $query['nextUrl']);
            $result = $this->request('query'.$query, [], 'GET', false, null, $queryUrl);
        } elseif (!empty($fields) and isset($query['start'])) {
            $fields[] = 'Id';
            $fields   = implode(', ', array_unique($fields));

            $config = $this->integration->mergeConfigToFeatureSettings([]);
            if (isset($config['updateOwner']) && isset($config['updateOwner'][0]) && $config['updateOwner'][0] == 'updateOwner') {
                $fields = 'Owner.Name, Owner.Email, '.$fields;
            }
            if ($object == 'Lead') {
                $ignoreConvertedLeads = ' and ConvertedContactId = NULL';
            }

            $getLeadsQuery = 'SELECT '.$fields.' from '.$object.' where LastModifiedDate>='.$query['start'].' and LastModifiedDate<='.$query['end'].$ignoreConvertedLeads;
            $result        = $this->request('query', ['q' => $getLeadsQuery], 'GET', false, null, $queryUrl);
        } else {
            $result = $this->request('query', ['q' => $query], 'GET', false, null, $queryUrl);
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getOrganizationCreatedDate()
    {
        $cache = $this->integration->getCache();

        if (!$organizationCreatedDate = $cache->get('organization.created_date')) {
            $queryUrl                = $this->integration->getQueryUrl();
            $organization            = $this->request('query', ['q' => 'SELECT CreatedDate from Organization'], 'GET', false, null, $queryUrl);
            $organizationCreatedDate = $organization['records'][0]['CreatedDate'];
            $cache->set('organization.created_date', $organizationCreatedDate);
        }

        return $organizationCreatedDate;
    }

    /**
     * @return mixed|string
     */
    public function getCampaigns()
    {
        $campaignQuery = 'Select Id, Name from Campaign where isDeleted = false';
        $queryUrl      = $this->integration->getQueryUrl();

        $result = $this->request('query', ['q' => $campaignQuery], 'GET', false, null, $queryUrl);

        return $result;
    }

    /**
     * @param $campaignId
     *
     * @return mixed|string
     */
    public function getCampaignMembers($campaignId)
    {
        $campaignMembersQuery = "Select CampaignId, ContactId, LeadId, isDeleted from CampaignMember where CampaignId = '".trim($campaignId)."'";
        $queryUrl             = $this->integration->getQueryUrl();
        $result               = $this->request('query', ['q' => $campaignMembersQuery], 'GET', false, null, $queryUrl);

        return $result;
    }

    /**
     * @param $campaignId
     *
     * @return mixed|string
     */
    public function getCampaignMemberStatus($campaignId)
    {
        $campaignQuery = "Select Id, Label from CampaignMemberStatus where isDeleted = false and CampaignId='".$campaignId."'";
        $queryUrl      = $this->integration->getQueryUrl();

        $result = $this->request('query', ['q' => $campaignQuery], 'GET', false, null, $queryUrl);

        return $result;
    }

    /**
     * @return int
     */
    public function getRequestCounter()
    {
        $count                   = $this->apiRequestCounter;
        $this->apiRequestCounter = 0;

        return $count;
    }
}
