<?php
namespace MauticAddon\MauticCrmBundle\Crm\Zoho;

use MauticAddon\MauticCrmBundle\Api\CrmApi;
use MauticAddon\MauticCrmBundle\Api\Exception\ErrorException;

class Lead extends CrmApi
{
    private $module = 'Leads';

    protected function request($operation, $parameters = array(), $method = 'GET')
    {
        $tokenData = $this->integration->getKeys();
        $url       = sprintf('%s/%s/%s', $this->integration->getApiUrl(), $this->module, $operation);

        $parameters  = array_merge(array(
            'authtoken' => $tokenData['AUTHTOKEN'],
            'scope'     => 'crmapi'
        ), $parameters);

        $response = $this->integration->makeRequest($url, $parameters, $method);

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

    /**
     * List types
     *
     * @return mixed
     */
    public function getFields ()
    {
        return $this->request('getFields');
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function create ($data)
    {
        $parameters  = array(
            'xmlData'        => $data,
            'duplicateCheck' => 2 //update if exists
        );

        return $this->request('insertRecords', $parameters, 'POST');
    }
}