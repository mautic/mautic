<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Joomla\Http\Response;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Exception\ApiErrorException;

class DynamicsApi extends CrmApi
{
    /**
     * @return string
     */
    private function getUrl()
    {
        $keys = $this->integration->getKeys();

        return $keys['resource'].'/api/data/v8.2';
    }

    /**
     * @param $operation
     * @param array  $parameters
     * @param string $method
     * @param string $moduleobject
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    protected function request($operation, array $parameters = [], $method = 'GET', $moduleobject = 'contacts', $settings = [])
    {
        if ('company' === $moduleobject) {
            $moduleobject = 'accounts';
        }

        if ('' === $operation) {
            $operation = $moduleobject;
        }

        $url = sprintf('%s/%s', $this->getUrl(), $operation);

        if (isset($parameters['request_settings'])) {
            $settings = array_merge($settings, $parameters['request_settings']);
            unset($parameters['request_settings']);
        }

        $settings = array_merge($settings, [
            'encode_parameters' => 'json',
            'return_raw'        => 'true', // needed to get the HTTP status code in the response
            'request_timeout'   => 300,
        ]);

        /** @var Response $response */
        $response = $this->integration->makeRequest($url, $parameters, $method, $settings);

        if ('POST' === $method && (!is_object($response) || !in_array($response->code, [200, 204], true))) {
            throw new ApiErrorException('Dynamics CRM API error: '.json_encode($response));
        }

        if ('GET' === $method && is_object($response) && property_exists($response, 'body')) {
            return json_decode($response->body, true);
        }

        return $response;
    }

    /**
     * List types.
     *
     * @param string $object Zoho module name
     *
     * @return mixed
     */
    public function getLeadFields($object = 'contacts')
    {
        if ('company' === $object) {
            $object = 'accounts'; // Dynamics object name
        }

        $logicalName = rtrim($object, 's'); // singularize object name

        $operation  = sprintf('EntityDefinitions(LogicalName=\'%s\')/Attributes', $logicalName);
        $parameters = [
            '$filter' => 'Microsoft.Dynamics.CRM.NotIn(PropertyName=\'AttributeTypeName\',PropertyValues=["Virtual", "Uniqueidentifier", "Picklist", "Lookup", "Boolean", "Owner", "Customer"]) and IsValidForUpdate eq true and AttributeOf eq null and LogicalName ne \'parentcustomerid\'', // ignore system fields
            '$select' => 'RequiredLevel,LogicalName,DisplayName', // select only miningful columns
        ];

        return $this->request($operation, $parameters, 'GET', $object);
    }

    /**
     * @param $data
     * @param Lead $lead
     * @param $object
     *
     * @return Response
     */
    public function createLead($data, $lead, $object = 'contacts')
    {
        return $this->request('', $data, 'POST', $object);
    }

    /**
     * @param $data
     * @param $objectId
     *
     * @return Response
     */
    public function updateLead($data, $objectId)
    {
        $settings['headers']['If-Match'] = '*'; // prevent create new contact
        return $this->request(sprintf('contacts(%s)', $objectId), $data, 'PATCH', 'contacts', $settings);
    }

    /**
     * gets leads.
     *
     * @param array $params
     *
     * @return mixed
     */
    public function getLeads(array $params)
    {
        $data = $this->request('', $params, 'GET', 'contacts');

        return $data;
    }

    /**
     * gets companies.
     *
     * @param array  $params
     * @param string $id
     *
     * @return mixed
     */
    public function getCompanies(array $params, $id = null)
    {
        if ($id) {
            $operation = sprintf('accounts(%s)', $id);
            $data      = $this->request($operation, $params, 'GET');
        } else {
            $data = $this->request('', $params, 'GET', 'accounts');
        }

        return $data;
    }

    /**
     * Batch create leads.
     *
     * @param array  $data
     * @param string $object
     * @param bool   $isUpdate
     *
     * @return array
     */
    public function createLeads($data, $object = 'contacts', $isUpdate = false)
    {
        if (0 === count($data)) {
            return [];
        }

        $returnIds = [];

        $batchId  = substr(str_shuffle(uniqid('b', false)), 0, 6);
        $changeId = substr(str_shuffle(uniqid('c', false)), 0, 6);

        $settings['headers']['Content-Type'] = 'multipart/mixed;boundary=batch_'.$batchId;
        $settings['headers']['Accept']       = 'application/json';

        $odata = '--batch_'.$batchId.PHP_EOL;
        $odata .= 'Content-Type: multipart/mixed;boundary=changeset_'.$changeId.PHP_EOL.PHP_EOL;

        $contentId = 0;
        foreach ($data as $objectId => $lead) {
            ++$contentId;
            $odata .= '--changeset_'.$changeId.PHP_EOL;
            $odata .= 'Content-Type: application/http'.PHP_EOL;
            $odata .= 'Content-Transfer-Encoding:binary'.PHP_EOL;
            $odata .= 'Content-ID: '.$objectId.PHP_EOL.PHP_EOL;
//            $odata .= 'Content-ID: '.(++$contentId).PHP_EOL.PHP_EOL;
            $returnIds[$objectId] = $contentId;
            if (!$isUpdate) {
                $oid                  = $objectId;
                $objectId             = sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
                $returnIds[$objectId] = $oid; // save lead Id
            }
            $operation = sprintf('%s(%s)', $object, $objectId);
            $odata .= sprintf('PATCH %s/%s HTTP/1.1', $this->getUrl(), $operation).PHP_EOL;
            if ($isUpdate) {
                $odata .= 'If-Match: *'.PHP_EOL;
            } else {
                $odata .= 'If-None-Match: *'.PHP_EOL;
            }
            $odata .= 'Content-Type: application/json;type=entry'.PHP_EOL.PHP_EOL;
            $odata .= json_encode($lead).PHP_EOL;
        }
        $odata .= '--changeset_'.$changeId.'--'.PHP_EOL.PHP_EOL;

        $odata .= '--batch_'.$batchId.'--'.PHP_EOL;

        $settings['post_data']                  = $odata;
        $settings['curl_options'][CURLOPT_CRLF] = true;

        $response = $this->request('$batch', [], 'POST', $object, $settings);
        if ($isUpdate) {
            return $returnIds;
        }

        return $this->parseRawHttpResponse($response);
    }

    /**
     * @param array $data
     * @param $object
     *
     * @return array
     */
    public function updateLeads($data, $object = 'contacts')
    {
        return $this->createLeads($data, $object, true);
    }

    /**
     * @link https://stackoverflow.com/questions/5483851/manually-parse-raw-http-data-with-php
     *
     * @param Response $response
     *
     * @return array
     */
    public function parseRawHttpResponse(Response $response)
    {
        $a_data      = [];
        $input       = $response->body;
        $contentType = $response->headers['Content-Type'];
        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $contentType, $matches);
        $boundary = $matches[1];
        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        array_pop($a_blocks);
        // there is only one batchresponse
        $input                = array_pop($a_blocks);
        list($header, $input) = explode("\r\n\r\n", $input, 2);
        foreach (explode("\r\n", $header) as $r) {
            if (stripos($r, 'Content-Type:') === 0) {
                list($headername, $contentType) = explode(':', $r, 2);
            }
        }
        // grab multipart boundary from content type header
        preg_match('/boundary=(.*)$/', $contentType, $matches);
        $boundary = $matches[1];
        // split content by boundary and get rid of last -- element
        $a_blocks = preg_split("/-+$boundary/", $input);
        array_pop($a_blocks);
        // loop data blocks
        foreach ($a_blocks as $id => $block) {
            if (empty($block)) {
                continue;
            }
            if (false !== stripos($block, 'OData-EntityId:')) {
                preg_match('/Content-ID: (\d+)/', $block, $matches);
                $leadId = (count($matches) > 1) ? $matches[1] : 0;
                // OData-EntityId: https://virlatinus.crm.dynamics.com/api/data/v8.2/contacts(2725f27c-2058-e711-8111-c4346bac1938)
                preg_match('/OData-EntityId: .*\(([^\)]*)\)/', $block, $matches);
                $oid          = (count($matches) > 1) ? $matches[1] : '00000000-0000-0000-0000-000000000000';
                $a_data[$oid] = $leadId;
            }
        }

        return $a_data;
    }
}
