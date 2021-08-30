<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\PluginBundle\Exception\ApiErrorException;

class HubspotApi extends CrmApi
{
    protected $requestSettings = [
        'encode_parameters' => 'json',
    ];

    protected function request($operation, $parameters = [], $method = 'GET', $object = 'contacts')
    {
        $hapikey = $this->integration->getHubSpotApiKey();
        $url     = sprintf('%s/%s/%s/?hapikey=%s', $this->integration->getApiUrl(), $object, $operation, $hapikey);
        $request = $this->integration->makeRequest($url, $parameters, $method, $this->requestSettings);
        if (isset($request['status']) && 'error' == $request['status']) {
            $message = $request['message'];
            if (isset($request['validationResults'])) {
                $message .= " \n ".print_r($request['validationResults'], true);
            }
            if (isset($request['validationResults'][0]['error']) && 'PROPERTY_DOESNT_EXIST' == $request['validationResults'][0]['error']) {
                $this->createProperty($request['validationResults'][0]['name']);
                $this->request($operation, $parameters, $method, $object);
            } else {
                throw new ApiErrorException($message);
            }
        }

        return $request;
    }

    /**
     * @return mixed
     */
    public function getLeadFields($object = 'contacts')
    {
        if ('company' == $object) {
            $object = 'companies'; //hubspot company object name
        }

        return $this->request('v2/properties', [], 'GET', $object);
    }

    /**
     * Creates Hubspot lead.
     *
     * @return mixed
     */
    public function createLead(array $data, $lead, $updateLink = false)
    {
        /*
         * As Hubspot integration requires a valid email
         * If the email is not valid we don't proceed with the request
         */
        $email  = $data['email'];
        $result = [];
        //Check if the is a valid email
        MailHelper::validateEmail($email);
        //Format data for request
        $formattedLeadData = $this->integration->formatLeadDataForCreateOrUpdate($data, $lead, $updateLink);
        if ($formattedLeadData) {
            $result = $this->request('v1/contact/createOrUpdate/email/'.$email, $formattedLeadData, 'POST');
        }

        return $result;
    }

    /**
     * gets Hubspot contact.
     *
     * @return mixed
     */
    public function getContacts($params = [])
    {
        return $this->request('v1/lists/recently_updated/contacts/recent?', $params, 'GET', 'contacts');
    }

    /**
     * gets Hubspot company.
     *
     * @return mixed
     */
    public function getCompanies($params, $id)
    {
        if ($id) {
            return $this->request('v2/companies/'.$id, $params, 'GET', 'companies');
        }

        return $this->request('v2/companies/recent/modified', $params, 'GET', 'companies');
    }

    /**
     * @param        $propertyName
     * @param string $object
     *
     * @return mixed|string
     */
    public function createProperty($propertyName, $object = 'properties')
    {
        return $this->request('v1/contacts/properties', ['name' => $propertyName,  'groupName' => 'contactinformation', 'type' => 'string'], 'POST', $object);
    }
}
