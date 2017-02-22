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

        if (!$queryUrl) {
            $queryUrl   = $this->integration->getApiUrl();
            $requestUrl = sprintf($queryUrl.'/%s/%s', $object, $operation);
        } else {
            $requestUrl = sprintf($queryUrl.'/%s', $operation);
        }

        $response = $this->integration->makeRequest($requestUrl, $elementData, $method, $this->requestSettings);

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
     * Creates Salesforce lead.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function createLead(array $data, $lead)
    {
        //find lead
        $update = false;
        if (isset($data['Email'])) {
            $findLead = 'select Id from Lead where email = \''.$data['Email'].'\'';
            $queryUrl = $this->integration->getQueryUrl();
            $sfLead   = $this->request('query', ['q' => $findLead], 'GET', false, null, $queryUrl);

            $lead = $sfLead['records'];
            if (isset($lead[0]['Id'])) {
                $update = true;
            }
        }

        if ($update) {
            $createdLeadData = $this->request('', $data, 'PATCH', false, 'Lead/'.$lead[0]['Id']);
        } else {
            $createdLeadData = $this->request('', $data, 'POST', false, 'Lead');
        }

        //todo: check if push activities is selected in config

        return $createdLeadData;
    }

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
        $config = $this->integration->getIntegrationSettings()->getFeatureSettings();

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

                $newRecordData = [];
                if ($results['hasErrors']) {
                    foreach ($results['results'] as $result) {
                        if ($result['errors'][0]['statusCode'] == 'CANNOT_UPDATE_CONVERTED_LEAD') {
                            $references   = explode('-', $result['referenceId']);
                            $SF_leadIds[] = $references[1];

                            $leadIds = implode("','", $SF_leadIds);
                            $query   = 'select Id, ConvertedContactId from '.$object." where id in ('".$leadIds."')";

                            $contacts = $this->request('query', ['q' => $query], 'GET', false, null, $queryUrl);

                            foreach ($contacts['records'] as $contact) {
                                foreach ($activityData['records'] as $key => $record) {
                                    if ($record[$namespace.'WhoId__c'] == $contact['Id']) {
                                        unset($record[$namespace.'WhoId__c']);
                                        $record[$namespace.'contact_id__c'] = $contact['ConvertedContactId'];
                                        $newRecordData['records'][]         = $record;
                                        unset($activityData['records'][$key]);
                                    }
                                }
                            }
                        }
                    }
                    if (!empty($newRecordData)) {
                        $results = $this->request(
                            'composite/tree/'.$mActivityObjectName,
                            $newRecordData,
                            'POST',
                            false,
                            null,
                            $queryUrl
                        );
                    }
                }

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

        if (isset($query['start'])) {
            $queryUrl = $this->integration->getQueryUrl();

            if (strtotime($query['start']) < strtotime($organizationCreatedDate)) {
                $query['start'] = date('c', strtotime($organizationCreatedDate.' +1 hour'));
            }
        }

        $fields = $this->integration->getIntegrationSettings()->getFeatureSettings();
        switch ($object) {
            case 'company':
            case 'Account':
                $companyFieldsToUpdateInMautic = isset($fields['update_mautic_company']) ? array_keys($fields['update_mautic_company'], 0) : [];
                $fields                        = array_keys(array_filter(array_diff_key($fields['companyFields'], $companyFieldsToUpdateInMautic)));
                break;
            default:
                $fieldsToUpdateInMautic = isset($fields['update_mautic']) ? array_keys($fields['update_mautic'], 0) : [];
                $mixedFields            = array_filter(array_diff_key($fields['leadFields'], $fieldsToUpdateInMautic));
                $fields                 = [];
                foreach ($mixedFields as $sfField => $mField) {
                    if (strpos($sfField, '__'.$object) !== false) {
                        $fields[] = str_replace('__'.$object, '', $sfField);
                    }
                    if (strpos($sfField, ' - '.$object) !== false) {
                        $fields[] = str_replace(' - '.$object, '', $sfField);
                    }
                }
        }
        $result = [];
        if (!empty($fields) and isset($query['start'])) {
            $fields[] = 'Id';
            $fields   = implode(', ', $fields);

            $config = $this->integration->mergeConfigToFeatureSettings([]);
            if (isset($config['updateOwner']) && isset($config['updateOwner'][0]) && $config['updateOwner'][0] == 'updateOwner') {
                $fields = 'Owner.Name, Owner.Email, '.$fields;
            }

            $getLeadsQuery = 'SELECT '.$fields.' from '.$object.' where LastModifiedDate>='.$query['start'].' and LastModifiedDate<='.$query['end'];

            $result = $this->request('query', ['q' => $getLeadsQuery], 'GET', false, null, $queryUrl);
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
}
