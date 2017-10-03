<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class ZohoApi extends CrmApi
{
    /**
     * @param        $operation
     * @param array  $parameters
     * @param string $method
     * @param string $moduleobject
     * @param bool   $isJson
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    protected function request($operation, array $parameters = [], $method = 'GET', $moduleobject = 'Leads', $isJson = true)
    {
        $tokenData = $this->integration->getKeys();
        $url       = sprintf('%s/%s/%s', $this->integration->getApiUrl($isJson), $moduleobject, $operation);

        $parameters = array_merge([
            'authtoken' => $tokenData['AUTHTOKEN'],
            'scope'     => 'crmapi',
        ], $parameters);

        $response = $this->integration->makeRequest($url, $parameters, $method);

        if (!empty($response['response']['error'])) {
            $response = $response['response'];
            $errorMsg = $response['error']['message'].' ('.$response['error']['code'].')';
            if (isset($response['uri'])) {
                $errorMsg .= '; '.$response['uri'];
            }
            throw new ApiErrorException($errorMsg);
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
    public function getLeadFields($object = 'Leads')
    {
        if ($object == 'company') {
            $object = 'Accounts'; // Zoho object name
        }

        return $this->request('getFields', [], 'GET', $object);
    }

    /**
     * @param $data
     * @param $object
     *
     * @return array
     */
    public function createLead($data, $lead = null, $object = 'Leads')
    {
        $parameters = [
            'xmlData'        => $data,
            'duplicateCheck' => 2, // update if exists
            'newFormat'      => 2, // To include fields with "null" values while inserting data from your CRM account
            'version'        => 4, // This will trigger duplicate check functionality for multiple records.
        ];

        return $this->request('insertRecords', $parameters, 'POST', $object, false);
    }

    /**
     * @param $data
     * @param $object
     *
     * @return array
     */
    public function updateLead($data, $lead = null, $object = 'Leads')
    {
        $parameters = [
            'xmlData'   => $data,
            'newFormat' => 2, // To include fields with "null" values while inserting data from your CRM account
            'version'   => 4, // This will trigger duplicate check functionality for multiple records.
        ];

        return $this->request('updateRecords', $parameters, 'POST', $object, false);
    }

    /**
     * gets Zoho leads.
     *
     * @param array     $params
     * @param string    $object
     * @param array|int $id
     *
     * @return mixed
     */
    public function getLeads(array $params, $object, $id = null)
    {
        if (!isset($params['selectColumns'])) {
            $params['selectColumns'] = 'All';
            $params['newFormat']     = 1;
        }

        if ($id) {
            if (is_array($id)) {
                $params['id'] = implode(';', $id);
            } else {
                $params['id'] = $id;
            }

            $data = $this->request('getRecordById', $params, 'GET', $object);
        } else {
            $data = $this->request('getRecords', $params, 'GET', $object);
        }
        if (isset($data['response'], $data['response']['result'])) {
            $data = $data['response']['result'];
        }

        return $data;
    }

    /**
     * gets Zoho companies.
     *
     * @param array  $params
     * @param string $id
     *
     * @return mixed
     */
    public function getCompanies(array $params, $id = null)
    {
        if (!isset($params['selectColumns'])) {
            $params['selectColumns'] = 'All';
        }

        if ($id) {
            $params['id'] = $id;

            $data = $this->request('getRecordById', $params, 'GET', 'Accounts');
        } else {
            $data = $this->request('getRecords', $params, 'GET', 'Accounts');
        }

        if (isset($data['response'], $data['response']['result'])) {
            $data = $data['response']['result'];
        }

        return $data;
    }

    public function getSearchRecords($selectColumns, $searchColumn, $searchValue, $object = 'Leads')
    {
        $parameters = [
            'selectColumns' => 'All',
            'searchColumn'  => $searchColumn, // search by email
            'searchValue'   => $searchValue, // email value
            'newFormat'     => 2,
        ];

        return $this->request('getSearchRecordsByPDC', $parameters, 'GET', $object, true);
    }
}
