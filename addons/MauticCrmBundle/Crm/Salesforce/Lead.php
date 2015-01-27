<?php
namespace MauticAddon\MauticCrmBundle\Crm\Salesforce;

use MauticAddon\MauticCrmBundle\Api\CrmApi;
use MauticAddon\MauticCrmBundle\Api\Exception\ErrorException;

class Lead extends CrmApi
{
    protected $object  = 'Lead';
    protected $requestSettings = array(
        'encode_parameters' => 'json'
    );

    public function request($operation, $elementData = array(), $method = 'GET')
    {
        $request_url = sprintf($this->integration->getApiUrl() . '/%s/%s', $this->object, $operation);

        $response = $this->integration->makeRequest($request_url, $elementData, $method, $this->requestSettings);

        if (!empty($response['errors'])) {
            throw new ErrorException(implode(', ', $response['errors']));
        } elseif (is_array($response)) {
            $errors = array();
            foreach ($response as $r) {
                if (is_array($r) && !empty($r['errorCode']) && !empty($r['message'])) {
                    $errors[] = $r['message'];
                }
            }

            if (!empty($errors)) {
                throw new ErrorException(implode(', ', $errors));
            }
        }

        return $response;
    }

    /**
     * @return mixed
     */
    public function getInfo()
    {
        return $this->request('describe');
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
        return $this->request('', $data, 'POST');
    }
}