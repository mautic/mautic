<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class ZohoApi extends CrmApi
{
    private $module = 'Leads';

    protected function request($operation, $parameters = [], $method = 'GET')
    {
        $tokenData = $this->integration->getKeys();
        $url       = sprintf('%s/%s/%s', $this->integration->getApiUrl(), $this->module, $operation);

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
     * @return mixed
     */
    public function getLeadFields()
    {
        return $this->request('getFields');
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
     * gets Hubspot contact.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function getContacts($params = [])
    {
        return $this->request('getLeads', $params);
    }

    /**
     * gets Hubspot company.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function getCompanies($params, $id)
    {
        if ($id) {
            $params['id'] = $id;

            return $this->request('getCompany', $params);
        }

        return $this->request('getCompanies', $params);
    }
}
