<?php
namespace MauticAddon\MauticCrmBundle\Crm\Salesforce\Api\Object;

use MauticAddon\MauticCrmBundle\Api\CrmApi;

class Lead extends CrmApi
{
    protected $version = 'v20.0';
    protected $object  = 'lead';
    /**
     * @return mixed
     */
    public function getInfo()
    {
        $tokenData = $this->auth->getAccessTokenData();

        $request_url = sprintf('%s/services/data/%s/sobjects/%s/describe',$tokenData['instance_url'], $this->version, $this->object);

        $request = $this->auth->makeRequest($request_url);

        return $request;
    }

    /**
     * Insert Salesforce sObject
     *
     * @param array $data
     *
     * @return mixed
     */
    public function create(array $data)
    {
        $tokenData = $this->auth->getAccessTokenData();

        $request_url = sprintf('%s/services/data/%s/sobjects/%s',$tokenData['instance_url'], $this->version, $this->object);

        $request = $this->auth->makeRequest($request_url, $data, 'POST');

        if (!empty($response['errors'])) {
            throw new ErrorException(implode(', ', $response['errors']));
        }

        return $request;
    }
}