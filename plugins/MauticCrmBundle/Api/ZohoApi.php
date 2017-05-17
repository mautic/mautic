<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class ZohoApi extends CrmApi
{
    /**
     * @param $operation
     * @param array  $parameters
     * @param string $method
     * @param string $object
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    protected function request($operation, array $parameters = [], $method = 'GET', $object = 'contacts')
    {
        $module = 'Leads';
        if ($object === 'company') {
            $module = 'Accounts'; // Zoho company object name
        }
        $tokenData = $this->integration->getKeys();
        $url       = sprintf('%s/%s/%s', $this->integration->getApiUrl(), $module, $operation);

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

        // this will keep the response array with the standard key names for companies and contacts
        if (isset($response[$module])) {
            $response[$object] = $response[$module];
            unset($response[$module]);
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
        return $this->request('getFields', [], 'GET', $object);
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function createLead($data)
    {
        $parameters = [
            'xmlData'        => $data,
            'duplicateCheck' => 2, //update if exists
        ];

        return $this->request('insertRecords', $parameters, 'POST');
    }

    /**
     * gets Zoho contacts.
     *
     * @param array  $params
     * @param string $id
     *
     * @return mixed
     */
    public function getContacts(array $params, $id)
    {
        if (!isset($params['selectColumns'])) {
            $params['selectColumns'] = 'All';
        }
        if ($id) {
            $params['id'] = $id;

            return $this->request('getRecordById', $params);
        }

        return $this->request('getRecords', $params);
    }

    /**
     * gets Zoho companies.
     *
     * @param array  $params
     * @param string $id
     *
     * @return mixed
     */
    public function getCompanies(array $params, $id)
    {
        if (!isset($params['selectColumns'])) {
            $params['selectColumns'] = 'All';
        }
        if ($id) {
            $params['id'] = $id;

            return $this->request('getRecordById', $params, 'GET', 'company');
        }

        return $this->request('getRecords', $params, 'GET', 'company');
    }
}
