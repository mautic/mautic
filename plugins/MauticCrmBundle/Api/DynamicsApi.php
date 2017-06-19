<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class DynamicsApi extends CrmApi
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
    public function createLead($data, $object = 'Leads')
    {
        $parameters = [
            'xmlData'        => $data,
            'duplicateCheck' => 2, // update if exists
            'newFormat'      => 1,
        ];

        return $this->request('insertRecords', $parameters, 'POST', $object, false);
    }

    /**
     * gets Zoho leads.
     *
     * @param array  $params
     * @param string $object
     *
     * @return mixed
     */
    public function getLeads(array $params, $object)
    {
        if (!isset($params['selectColumns'])) {
            $params['selectColumns'] = 'All';
            $params['newFormat']     = 1;
        }

        $data = $this->request('getRecords', $params, 'GET', $object);
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
}
