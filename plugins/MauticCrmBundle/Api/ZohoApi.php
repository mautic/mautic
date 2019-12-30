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
    protected function request($operation, array $parameters = [], $method = 'GET', $moduleobject = 'Leads', $json = false)
    {
        $tokenData = $this->integration->getKeys();

        $url = sprintf('%s/%s', $tokenData['api_domain'].'/crm/v2', $operation, $moduleobject);

        $settings['headers']['Authorization'] = 'Zoho-oauthtoken '.$tokenData['access_token'];
        if ($operation == 'Leads/search' || $operation == 'Contacts/search' || $operation == 'Accounts/search') {
            $settings['headers']['If-Modified-Since'] = date('c');
        }

        if ($json === true) {
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

        return $this->request('settings/fields?module='.$object, [], 'GET', $object);
    }

    /**
     * @param array  $data
     * @param string $object
     *
     * @return mixed
     */
    public function createLead(array $data, $object = 'Leads')
    {
        $parameters['data'] = $data;

        return $this->request($object, $parameters, 'POST', $object, true);
    }

    /**
     * @param array  $data
     * @param string $object
     *
     * @return mixed
     */
    public function updateLead(array $data, $object = 'Leads')
    {
        $parameters['data'] = $data;

        return $this->request($object, $parameters, 'PUT', $object, true);
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

            $data = $this->request($object, $params, 'GET', $object);
        } else {
            $data = $this->request($object, $params, 'GET', $object);
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

            $data = $this->request('Accounts', $params, 'GET', 'Accounts');
        } else {
            $data = $this->request('Accounts', $params, 'GET', 'Accounts');
        }

        return $data;
    }

    /**
     * @param        $searchColumn
     * @param        $searchValue
     * @param string $object
     *
     * @return mixed|string
     */
    public function getSearchRecords($searchColumn, $searchValue, $object = 'Leads')
    {
        $parameters = [
            'criteria' => '('.$searchColumn.':equals:'.$searchValue.')',
        ];

        return $this->request($object.'/search', $parameters, 'GET', $object, false);
    }
}
