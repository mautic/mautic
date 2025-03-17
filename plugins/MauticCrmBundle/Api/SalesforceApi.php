<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCrmBundle\Api\Salesforce\Exception\RetryRequestException;
use MauticPlugin\MauticCrmBundle\Api\Salesforce\Helper\RequestUrl;
use MauticPlugin\MauticCrmBundle\Integration\CrmAbstractIntegration;
use MauticPlugin\MauticCrmBundle\Integration\SalesforceIntegration;

/**
 * @property SalesforceIntegration $integration
 */
class SalesforceApi extends CrmApi
{
    /**
     * This regular expression parses missing field's name from the error message.
     *
     * @var string
     */
    public const REGEXP_MISSING_FIELD = "/ERROR\sat\sRow.+No\ssuch\scolumn\s'([^']+)'\son\sentity\s'([^']+)'/m";

    protected $object          = 'Lead';

    protected $requestSettings = [
        'encode_parameters' => 'json',
    ];

    protected $apiRequestCounter   = 0;

    protected $requestCounter      = 1;

    protected $maxLockRetries      = 3;

    private bool $optOutFieldAccessible = true;

    public function __construct(CrmAbstractIntegration $integration)
    {
        parent::__construct($integration);

        $this->requestSettings['curl_options'] = [
            CURLOPT_SSLVERSION => defined('CURL_SSLVERSION_TLSv1_2') ? CURL_SSLVERSION_TLSv1_2 : 6,
        ];
    }

    /**
     * @param array  $elementData
     * @param string $method
     * @param bool   $isRetry
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function request($operation, $elementData = [], $method = 'GET', $isRetry = false, $object = null, $queryUrl = null)
    {
        if (!$object) {
            $object = $this->object;
        }

        $requestUrl = RequestUrl::get($this->integration->getApiUrl(), $queryUrl, $operation, $object);

        $settings   = $this->requestSettings;
        if ('PATCH' == $method) {
            $settings['headers'] = ['Sforce-Auto-Assign' => 'FALSE'];
        }

        // Query commands can have long wait time while SF builds response as the offset increases
        $settings['request_timeout'] = 300;

        // Wrap in a isAuthorized to refresh token if applicable
        $response = $this->integration->makeRequest($requestUrl, $elementData, $method, $settings);
        ++$this->apiRequestCounter;

        try {
            $this->analyzeResponse($response, $isRetry);
        } catch (RetryRequestException) {
            return $this->request($operation, $elementData, $method, true, $object, $queryUrl);
        }

        return $response;
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getLeadFields($object = null)
    {
        if ('company' == $object) {
            $object = 'Account'; // salesforce object name
        }

        return $this->request('describe', [], 'GET', false, $object);
    }

    /**
     * @throws ApiErrorException
     */
    public function getPerson(array $data): array
    {
        $config    = $this->integration->mergeConfigToFeatureSettings([]);
        $queryUrl  = $this->integration->getQueryUrl();
        $sfRecords = [
            'Contact' => [],
            'Lead'    => [],
        ];

        // try searching for lead as this has been changed before in updated done to the plugin
        if (isset($config['objects']) && false !== array_search('Contact', $config['objects']) && !empty($data['Contact']['Email'])) {
            $fields      = $this->integration->getFieldsForQuery('Contact');
            unset($fields[array_search('HasOptedOutOfEmail', $fields)]);
            $fields[]    = 'Id';
            $fields      = implode(', ', array_unique($fields));
            $findContact = 'select '.$fields.' from Contact where email = \''.$this->escapeQueryValue($data['Contact']['Email']).'\'';
            $response    = $this->request('query', ['q' => $findContact], 'GET', false, null, $queryUrl);

            if (!empty($response['records'])) {
                $sfRecords['Contact'] = $response['records'];
            }
        }

        if (!empty($data['Lead']['Email'])) {
            $fields   = $this->integration->getFieldsForQuery('Lead');
            unset($fields[array_search('HasOptedOutOfEmail', $fields)]);
            $fields[] = 'Id';
            $fields   = implode(', ', array_unique($fields));
            $findLead = 'select '.$fields.' from Lead where email = \''.$this->escapeQueryValue($data['Lead']['Email']).'\' and ConvertedContactId = NULL';
            $response = $this->request('queryAll', ['q' => $findLead], 'GET', false, null, $queryUrl);

            if (!empty($response['records'])) {
                $sfRecords['Lead'] = $response['records'];
            }
        }

        return $sfRecords;
    }

    /**
     * @throws ApiErrorException
     */
    public function getCompany(array $data): array
    {
        $config    = $this->integration->mergeConfigToFeatureSettings([]);
        $queryUrl  = $this->integration->getQueryUrl();
        $sfRecords = [
            'Account' => [],
        ];

        $appendToQuery = '';

        // try searching for lead as this has been changed before in updated done to the plugin
        if (isset($config['objects']) && false !== array_search('company', $config['objects']) && !empty($data['company']['Name'])) {
            $fields = $this->integration->getFieldsForQuery('Account');

            if (!empty($data['company']['BillingCountry'])) {
                $appendToQuery .= ' and BillingCountry =  \''.$this->escapeQueryValue($data['company']['BillingCountry']).'\'';
            }
            if (!empty($data['company']['BillingCity'])) {
                $appendToQuery .= ' and BillingCity =  \''.$this->escapeQueryValue($data['company']['BillingCity']).'\'';
            }
            if (!empty($data['company']['BillingState'])) {
                $appendToQuery .= ' and BillingState =  \''.$this->escapeQueryValue($data['company']['BillingState']).'\'';
            }

            $fields[] = 'Id';
            $fields   = implode(', ', array_unique($fields));
            $query    = 'select '.$fields.' from Account where Name = \''.$this->escapeQueryValue($data['company']['Name']).'\''.$appendToQuery;
            $response = $this->request('queryAll', ['q' => $query], 'GET', false, null, $queryUrl);

            if (!empty($response['records'])) {
                $sfRecords['company'] = $response['records'];
            }
        }

        return $sfRecords;
    }

    /**
     * @return array|mixed|string
     *
     * @throws ApiErrorException
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
     * @return mixed|string
     *
     * @throws ApiErrorException
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
     * @return mixed|string
     *
     * @throws ApiErrorException
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
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function syncMauticToSalesforce(array $data)
    {
        $queryUrl = $this->integration->getCompositeUrl();

        return $this->request('composite/', $data, 'POST', false, null, $queryUrl);
    }

    /**
     * @return array<mixed>
     *
     * @throws ApiErrorException
     */
    public function createLeadActivity(array $activity, $object): array
    {
        $config              = $this->integration->getIntegrationSettings()->getFeatureSettings();
        $namespace           = (!empty($config['namespace'])) ? $config['namespace'].'__' : '';
        $mActivityObjectName = $namespace.'mautic_timeline__c';
        $activityData        = [];

        if (!empty($activity)) {
            foreach ($activity as $sfId => $records) {
                foreach ($records['records'] as $record) {
                    $body = [
                        $namespace.'ActivityDate__c' => $record['dateAdded']->format('c'),
                        $namespace.'Description__c'  => $record['description'],
                        'Name'                       => substr($record['name'], 0, 80),
                        $namespace.'Mautic_url__c'   => $records['leadUrl'],
                        $namespace.'ReferenceId__c'  => $record['id'].'-'.$sfId,
                    ];

                    if ('Lead' === $object) {
                        $body[$namespace.'WhoId__c'] = $sfId;
                    } elseif ('Contact' === $object) {
                        $body[$namespace.'contact_id__c'] = $sfId;
                    }

                    $activityData[] = [
                        'method'      => 'POST',
                        'url'         => '/services/data/v38.0/sobjects/'.$mActivityObjectName,
                        'referenceId' => $record['id'].'-'.$sfId,
                        'body'        => $body,
                    ];
                }
            }

            if (!empty($activityData)) {
                $request              = [];
                $request['allOrNone'] = 'false';
                $chunked              = array_chunk($activityData, 25);
                $results              = [];
                foreach ($chunked as $chunk) {
                    // We can only submit 25 at a time
                    if ($chunk) {
                        $request['compositeRequest'] = $chunk;
                        $result                      = $this->syncMauticToSalesforce($request);
                        $results[]                   = $result;
                        $this->integration->getLogger()->debug('SALESFORCE: Activity response '.var_export($result, true));
                    }
                }

                return $results;
            }
        }

        return [];
    }

    /**
     * Get Salesforce leads.
     *
     * @param mixed  $query  String for a SOQL query or array to build query
     * @param string $object
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getLeads($query, $object)
    {
        $queryUrl = $this->integration->getQueryUrl();

        if (defined('MAUTIC_ENV') && MAUTIC_ENV === 'dev') {
            // Easier for testing
            $this->requestSettings['headers']['Sforce-Query-Options'] = 'batchSize=200';
        }

        if (!is_array($query)) {
            return $this->request('queryAll', ['q' => $query], 'GET', false, null, $queryUrl);
        }

        if (!empty($query['nextUrl'])) {
            return $this->request(null, [], 'GET', false, null, $query['nextUrl']);
        }

        $organizationCreatedDate = $this->getOrganizationCreatedDate();
        $fields                  = $this->integration->getFieldsForQuery($object);
        if (!empty($fields) && isset($query['start'])) {
            if (strtotime($query['start']) < strtotime($organizationCreatedDate)) {
                $query['start'] = date('c', strtotime($organizationCreatedDate.' +1 hour'));
            }

            $fields[] = 'Id';

            return $this->requestQueryAllAndHandle($queryUrl, $fields, $object, $query);
        }

        return [
            'totalSize' => 0,
            'records'   => [],
        ];
    }

    /**
     * Perform queryAll request and retry if HasOptedOutOfEmail is not accessible.
     *
     * @param array<mixed> $fields
     * @param array<mixed> $query
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    private function requestQueryAllAndHandle(string $queryUrl, array $fields, string $object, array $query): mixed
    {
        $config = $this->integration->mergeConfigToFeatureSettings([]);
        if (isset($config['updateOwner']) && isset($config['updateOwner'][0]) && 'updateOwner' == $config['updateOwner'][0]) {
            $fields[] = 'Owner.Name';
            $fields[] = 'Owner.Email';
        }
        $fields = array_unique($fields);

        $ignoreConvertedLeads = ('Lead' == $object) ? ' and ConvertedContactId = NULL' : '';
        if (!$this->isOptOutFieldAccessible()) { // If not opt-out is supported; unset it
            unset($fields[array_search('HasOptedOutOfEmail', $fields)]);
        }

        $baseQuery = 'SELECT %s from '.$object.' where SystemModStamp>='.$query['start'].' and SystemModStamp<='.$query['end'].' and isDeleted = false'
            .$ignoreConvertedLeads;

        return $this->handleQueryAll($baseQuery, $fields, $queryUrl);
    }

    /**
     * @return bool|mixed
     *
     * @throws ApiErrorException
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
     *
     * @throws ApiErrorException
     */
    public function getCampaigns()
    {
        $campaignQuery = 'Select Id, Name from Campaign where isDeleted = false';
        $queryUrl      = $this->integration->getQueryUrl();

        return $this->request('query', ['q' => $campaignQuery], 'GET', false, null, $queryUrl);
    }

    /**
     * @param mixed $modifiedSince
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCampaignMembers($campaignId, $modifiedSince = null, $queryUrl = null)
    {
        $defaultSettings = $this->requestSettings;

        // Control batch size to prevent URL too long errors when fetching contact details via SOQL and to control Doctrine RAM usage for
        // Mautic IntegrationEntity objects
        $this->requestSettings['headers']['Sforce-Query-Options'] = 'batchSize=200';

        if (null === $queryUrl) {
            $queryUrl = $this->integration->getQueryUrl().'/query';
        }

        $query = "Select CampaignId, ContactId, LeadId, isDeleted from CampaignMember where CampaignId = '".trim($campaignId)."'";
        if ($modifiedSince) {
            $query .= ' and SystemModStamp >= '.$modifiedSince;
        }

        $results = $this->request(null, ['q' => $query], 'GET', false, null, $queryUrl);

        $this->requestSettings = $defaultSettings;

        return $results;
    }

    /**
     * @throws ApiErrorException
     */
    public function checkCampaignMembership($campaignId, $object, array $people): array
    {
        $campaignMembers = [];
        if (!empty($people)) {
            $idField = "{$object}Id";
            $query   = "Select Id, $idField from CampaignMember where CampaignId = '".$campaignId
                ."' and $idField in ('".implode("','", $people)."')";

            $foundCampaignMembers = $this->request('query', ['q' => $query], 'GET', false, null, $this->integration->getQueryUrl());
            if (!empty($foundCampaignMembers['records'])) {
                foreach ($foundCampaignMembers['records'] as $member) {
                    $campaignMembers[$member[$idField]] = $member['Id'];
                }
            }
        }

        return $campaignMembers;
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCampaignMemberStatus($campaignId)
    {
        $campaignQuery = "Select Id, Label from CampaignMemberStatus where isDeleted = false and CampaignId='".$campaignId."'";
        $queryUrl      = $this->integration->getQueryUrl();

        return $this->request('query', ['q' => $campaignQuery], 'GET', false, null, $queryUrl);
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

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCompaniesByName(array $names, $requiredFieldString)
    {
        $names     = array_map([$this, 'escapeQueryValue'], $names);
        $queryUrl  = $this->integration->getQueryUrl();
        $findQuery = 'select Id, '.$requiredFieldString.' from Account where isDeleted = false and Name in (\''.implode("','", $names).'\')';

        return $this->request('query', ['q' => $findQuery], 'GET', false, null, $queryUrl);
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getCompaniesById(array $ids, $requiredFieldString)
    {
        $findQuery = 'select isDeleted, Id, '.$requiredFieldString.' from Account where  Id in (\''.implode("','", $ids).'\')';
        $queryUrl  = $this->integration->getQueryUrl();

        return $this->request('queryAll', ['q' => $findQuery], 'GET', false, null, $queryUrl);
    }

    /**
     * @param mixed $response
     * @param bool  $isRetry
     *
     * @throws ApiErrorException
     * @throws RetryRequestException
     */
    private function analyzeResponse($response, $isRetry): void
    {
        if (is_array($response)) {
            if (!empty($response['errors'])) {
                throw new ApiErrorException(implode(', ', $response['errors']));
            }

            if (isset($response['error']['message'])) {
                throw new ApiErrorException($response['error']['message']);
            }

            foreach ($response as $lineItem) {
                if (!is_array($lineItem)) {
                    continue;
                }
                $lineItemForInvalidSession              = $lineItem;
                $lineItemForInvalidSession['errorCode'] = 'INVALID_SESSION_ID';
                if (!empty($lineItemForInvalidSession['message']) && str_contains($lineItemForInvalidSession['message'], '"errorCode":"INVALID_SESSION_ID"') && $error = $this->processError($lineItemForInvalidSession, $isRetry)) {
                    $errors[] = $error;
                    continue;
                }

                if (!empty($lineItem['errorCode']) && $error = $this->processError($lineItem, $isRetry)) {
                    $errors[] = $error;
                }
            }

            if (!empty($errors)) {
                throw new ApiErrorException(implode(', ', $errors));
            }
        }
    }

    /**
     * @return string|false
     *
     * @throws ApiErrorException
     * @throws RetryRequestException
     */
    private function processError(array $error, $isRetry)
    {
        switch ($error['errorCode']) {
            case 'INVALID_SESSION_ID':
                $this->revalidateSession($isRetry);
                break;
            case 'UNABLE_TO_LOCK_ROW':
                $this->checkIfLockedRequestShouldBeRetried();
                break;
        }

        if (!empty($error['message'])) {
            return $error['message'];
        }

        return false;
    }

    /**
     * @throws ApiErrorException
     * @throws RetryRequestException
     */
    private function revalidateSession($isRetry): void
    {
        if ($refreshError = $this->integration->authCallback(['use_refresh_token' => true])) {
            throw new ApiErrorException($refreshError);
        }

        if (!$isRetry) {
            throw new RetryRequestException();
        }
    }

    /**
     * @throws RetryRequestException
     */
    private function checkIfLockedRequestShouldBeRetried(): bool
    {
        // The record is locked so let's wait a a few seconds and retry
        if ($this->requestCounter < $this->maxLockRetries) {
            sleep($this->requestCounter * 3);
            ++$this->requestCounter;

            throw new RetryRequestException();
        }

        $this->requestCounter = 1;

        return false;
    }

    /**
     * @return array<mixed>
     */
    private function parseMissingField(string $errorMessage)
    {
        $matches = [];
        preg_match(self::REGEXP_MISSING_FIELD, $errorMessage, $matches);

        return isset($matches[1]) ? [$matches[1], $matches[2]] : [null, null];
    }

    /**
     * @return bool|float|mixed|string
     */
    private function escapeQueryValue($value)
    {
        // SF uses backslashes as escape delimeter
        // Remember that PHP uses \ as an escape. Therefore, to replace a single backslash with 2, must use 2 and 4
        $value = str_replace('\\', '\\\\', $value);

        // Apply general formatting/cleanup
        $value = $this->integration->cleanPushData($value);

        // Escape single quotes
        $value = str_replace("'", "\'", $value);

        return $value;
    }

    public function isOptOutFieldAccessible(): bool
    {
        return $this->optOutFieldAccessible;
    }

    public function setOptOutFieldAccessible(bool $optOutFieldAccessible): SalesforceApi
    {
        $this->optOutFieldAccessible = $optOutFieldAccessible;

        return $this;
    }

    /**
     * @param array<string> $fields
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function handleQueryAll(string $baseQuery, array $fields, string $queryUrl, int $tries = 0, bool $isRetry = false): mixed
    {
        if (10 === $tries) {
            $this->integration->logIntegrationError(new \Exception(
                sprintf('Maximum tries exceeded for handling missing field scenarios')
            ));
        }
        try {
            $leadsQuery = sprintf($baseQuery, join(', ', $fields));
            $response   = $this->request('queryAll', ['q' => $leadsQuery], 'GET', $isRetry, null, $queryUrl);
        } catch (ApiErrorException $e) {
            list($missingField, $entityType) = $this->parseMissingField($e->getMessage());
            if (!$missingField) {
                throw $e;
            }
            if ('HasOptedOutOfEmail' == $missingField) {
                // Unset field as it is not accessible
                unset($fields[array_search('HasOptedOutOfEmail', $fields)]);

                // Disable the use of the HasOptedOutOfEmail field for future requests
                $this->setOptOutFieldAccessible(false);

                // Notify all admins of this error
                $this->integration->upsertUnreadAdminsNotification(
                    $this->integration->getTranslator()->trans('mautic.salesforce.error.opt-out_permission.header'),
                    $this->integration->getTranslator()->trans('mautic.salesforce.error.opt-out_permission.message')
                );
            } else {
                $entityManager   = $this->integration->getEntityManager();
                $entity          = $this->integration->getIntegrationSettings();
                $featureSettings = $entity->getFeatureSettings();

                $field = $missingField.'__'.$entityType;

                if (isset($featureSettings['leadFields'][$field])) {
                    unset($featureSettings['leadFields'][$field]);

                    // Remove the missing field from mapping
                    $entity->setFeatureSettings($featureSettings);
                    $entityManager->persist($entity);
                    $entityManager->flush();

                    // Remove the missing field from the request
                    $missingFieldIndex = array_search($missingField, $fields);
                    if (false !== $missingFieldIndex) {
                        unset($fields[$missingFieldIndex]);
                    }
                }
            }

            $response = $this->handleQueryAll($baseQuery, $fields, $queryUrl, ++$tries, true);
        }

        return $response;
    }
}
