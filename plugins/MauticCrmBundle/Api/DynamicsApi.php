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
            'request_timeout'   => 30,
        ]);

        /** @var Response $response */
        $response = $this->integration->makeRequest($url, $parameters, $method, $settings);

        if ('POST' === $method && (!is_object($response) || 204 !== $response->code)) {
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
            '$filter' => 'Microsoft.Dynamics.CRM.NotIn(PropertyName=\'AttributeTypeName\',PropertyValues=["Virtual", "Uniqueidentifier", "Picklist", "Lookup", "Boolean", "Owner", "Customer"]) and IsValidForUpdate eq true and AttributeOf eq null', // ignore system fields
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
}
