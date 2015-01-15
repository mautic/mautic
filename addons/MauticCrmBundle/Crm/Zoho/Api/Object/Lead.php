<?php
namespace MauticAddon\MauticCrmBundle\Crm\Zoho\Api\Object;

use MauticAddon\MauticCrmBundle\Api\CrmApi;
use MauticAddon\MauticCrmBundle\Api\Exception\ErrorException;

class Lead extends CrmApi
{
    private $module = 'Leads';

    /**
     * List types
     *
     * @return mixed
     */
    public function getFields ()
    {
        $tokenData = $this->auth->getAccessTokenData();

        $request_url = sprintf('%s%s/getFields', $tokenData['endpoint_url'], $this->module);
        $parameters  = array(
            'authtoken' => $tokenData['authtoken'],
            'scope'     => 'crmapi'
        );

        $response = $this->auth->makeRequest($request_url, $parameters);

        return $response;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function create ($data)
    {
        //https://crm.zoho.com/crm/private/xml/Leads/insertRecords
        $tokenData = $this->auth->getAccessTokenData();

        $request_url = sprintf('%s%s/insertRecords', $tokenData['endpoint_url'], $this->module);
        $parameters  = array(
            'authtoken'      => $tokenData['authtoken'],
            'scope'          => 'crmapi',
            'xmlData'        => $data,
            'duplicateCheck' => 2 //update if exists
        );

        $response = $this->auth->makeRequest($request_url, $parameters, 'POST');

        if (!empty($response['response']['error'])) {
            $response = $response['response'];
            $errorMsg = $response['error']['message'] . ' (' . $response['error']['code'] . ')';
            if (isset($response['uri'])) {
                $errorMsg .= '; ' . $response['uri'];
            }
            throw new ErrorException($errorMsg);
        }

        return $response;
    }
}