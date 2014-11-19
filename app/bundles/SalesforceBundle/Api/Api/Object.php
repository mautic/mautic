<?php
namespace Mautic\SalesforceBundle\Api\Api;

class Object extends Api
{
    /**
     * @param $sObject
     * @return mixed
     */
    public function getInfo($sObject)
    {
        $tokenData = $this->auth->getAccessTokenData();
        $request_url = sprintf('%s/services/data/%s/sobjects/%s/describe',$tokenData['instance_url'], $this->version, $sObject);

        $request = $this->auth->makeRequest($request_url);

        return $request['response'];
    }

    /**
     * Insert Salesforce sObject
     *
     * @param $sObject
     * @param array $data
     * @return mixed
     * @throws RuntimeException
     */
    public function insert($sObject, array $data)
    {
        $tokenData = $this->auth->getAccessTokenData();

        $postData = json_encode($data);
        $request_url = sprintf('%s/services/data/%s/sobjects/%s',$tokenData['instance_url'], $this->version, $sObject);

        $settings = array(
            'header' => array(
                'Content-type: application/json'
            )
        );

        $request = $this->auth->makeRequest($request_url, $postData, 'POST', $settings);

        return $request['response'];
    }
}