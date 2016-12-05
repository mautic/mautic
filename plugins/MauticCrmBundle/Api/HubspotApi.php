<?php

namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\EmailBundle\Helper\MailHelper;
use Mautic\PluginBundle\Exception\ApiErrorException;

class HubspotApi extends CrmApi
{
    private $module = 'contacts';

    protected $requestSettings = [
        'encode_parameters' => 'json',
    ];

    protected function request($operation, $parameters = [], $method = 'GET', $object = 'contacts')
    {
        $hapikey = $this->integration->getHubSpotApiKey();
        $url     = sprintf('%s/%s/%s/?hapikey=%s', $this->integration->getApiUrl(), $object, $operation, $hapikey);
        $request = $this->integration->makeRequest($url, $parameters, $method);
        if (isset($request['status']) && $request['status'] == 'error') {
            $message = $request['message'];
            if (isset($request['validationResults'])) {
                $message .= " \n ".print_r($request['validationResults'], true);
            }
            throw new ApiErrorException($message);
        }

        return $request;
    }

    /**
     * @return mixed
     */
    public function getLeadFields($object = 'contacts')
    {
        if ($object == 'company') {
            $object = 'companies'; //hubspot company object name
        }

        return $this->request('v2/properties', [], 'GET', $object);
    }

    /**
     * Creates Hubspot lead.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function createLead(array $data, $lead)
    {
        /*
         * As Hubspot integration requires a valid email
         * If the email is not valid we don't proceed with the request
         */
        $email = $data['email'];
        //Check if the is a valid email
        MailHelper::validateEmail($email);
        //Format data for request
        $formattedLeadData = $this->integration->formatLeadDataForCreateOrUpdate($data, $lead);

        return $this->request('v1/contact/createOrUpdate/email/'.$email, $formattedLeadData, 'POST');
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
        return $this->request('v1/lists/recently_updated/contacts/recent?', $params, 'GET', 'contacts');
    }

    /**
     * gets Hubspot company.
     *
     * @param array $data
     *
     * @return mixed
     */
    public function getCompanies($params = [])
    {
        return $this->request('v2/companies/recent/modified', $params, 'GET', 'companies');
    }
}
