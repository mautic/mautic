<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class ZohoApi extends CrmApi
{
    /**
     * @param string $operation
     * @param string $method
     * @param bool   $json
     * @param array  $settings
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    protected function request($operation, array $parameters = [], $method = 'GET', $json = false, $settings = [])
    {
        $tokenData = $this->integration->getKeys();

        $url = sprintf('%s/%s', $tokenData['api_domain'].'/crm/v2', $operation);

        if (!isset($settings['headers'])) {
            $settings['headers'] = [];
        }
        $settings['headers']['Authorization'] = 'Zoho-oauthtoken '.$tokenData['access_token'];

        if ($json) {
            $settings['Content-Type']      = 'application/json';
            $settings['encode_parameters'] = 'json';
        }

        $response = $this->integration->makeRequest($url, $parameters, $method, $settings);

        if (isset($response['status']) && 'error' === $response['status']) {
            throw new ApiErrorException($response['message']);
        }

        return $response;
    }

    /**
     * @param string $object
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function getLeadFields($object = 'Leads')
    {
        if ('company' == $object) {
            $object = 'Accounts'; // Zoho object name
        }

        return $this->request('settings/fields?module='.$object, [], 'GET');
    }

    /**
     * @param string $object
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function createLead(array $data, $object = 'Leads')
    {
        $parameters['data'] = $data;

        return $this->request($object, $parameters, 'POST', true);
    }

    /**
     * @param string $object
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function updateLead(array $data, $object = 'Leads')
    {
        $parameters['data'] = $data;

        return $this->request($object, $parameters, 'PUT', true);
    }

    /**
     * @param string $object
     * @param null   $id
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function getLeads(array $params, $object, $id = null)
    {
        if (!isset($params['selectColumns'])) {
            $params['selectColumns'] = 'All';
            $params['newFormat']     = 1;
        }

        $settings = [];
        if ($params['lastModifiedTime']) {
            $settings['headers'] = [
                'If-Modified-Since' => $params['lastModifiedTime'],
            ];
        }

        if ($id) {
            if (is_array($id)) {
                $params['id'] = implode(';', $id);
            } else {
                $params['id'] = $id;
            }

            $data = $this->request($object, $params, 'GET', false, $settings);
        } else {
            $data = $this->request($object, $params, 'GET', false, $settings);
        }

        return $data;
    }

    /**
     * @param null $id
     *
     * @return array
     *
     * @throws ApiErrorException
     */
    public function getCompanies(array $params, $id = null)
    {
        if (!isset($params['selectColumns'])) {
            $params['selectColumns'] = 'All';
        }

        $settings = [];
        if ($params['lastModifiedTime']) {
            $settings['headers'] = [
                'If-Modified-Since' => $params['lastModifiedTime'],
            ];
        }

        if ($id) {
            $params['id'] = $id;

            $data = $this->request('Accounts', $params, 'GET', false, $settings);
        } else {
            $data = $this->request('Accounts', $params, 'GET', false, $settings);
        }

        return $data;
    }

    /**
     * @param string $searchColumn
     * @param string $searchValue
     * @param string $object
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getSearchRecords($searchColumn, $searchValue, $object = 'Leads')
    {
        $parameters = [
            'criteria' => '('.$searchColumn.':equals:'.$searchValue.')',
        ];

        return $this->request($object.'/search', $parameters, 'GET', false);
    }
}
