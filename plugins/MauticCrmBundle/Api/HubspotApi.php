<?php
namespace MauticPlugin\MauticCrmBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class HubspotApi extends CrmApi
{
    private $module = 'contacts';

    protected $requestSettings = array(
        'encode_parameters' => 'json'
    );

    protected function request($operation, $parameters = array(), $method = 'GET')
    {
        $hapikey   = $this->integration->getHubSpotApiKey();
        $url       = sprintf('%s/%s/%s/?hapikey=%s', $this->integration->getApiUrl(), $this->module, $operation, $hapikey);
        $request   = $this->integration->makeRequest($url, $parameters, $method, $this->requestSettings);

        if( isset($request["status"]) && $request["status"] == "error" )
        {
            throw new ApiErrorException($request["message"]);
        }

        return $request;
    }

    /**
     * @return mixed
     */
    public function getLeadFields()
    {
        return $this->request('v2/properties');
    }

    /**
     * Creates Hubspot lead
     *
     * @param array $data
     *
     * @return mixed
     */
    public function createLead(array $data)
    {
        $field_key = array_search("email", array_column($data['properties'], 'property'));
        $email = $data['properties'][$field_key]['value'];

        return $this->request('v1/contact/createOrUpdate/email/'.$email, $data, 'POST');
    }
}