<?php
namespace vTigerCRM\Api;

use MauticMapper\Uri\Uri;

class Object extends Api
{
    /**
     * List types
     *
     * @return mixed
     */
    public function listTypes()
    {
        $tokenData = $this->auth->getAccessTokenData();

        $request_url = Uri::clean_url(sprintf('%s/webservice.php',$tokenData['vtiger_url']));
        $parameters = array(
            'operation' => 'listtypes',
            'sessionName' => $tokenData['session_id']
        );

        $response = $this->auth->makeRequest($request_url, $parameters);

        return $response['result'];
    }

    /**
     * List types
     *
     * @return mixed
     */
    public function describe($type)
    {
        $tokenData = $this->auth->getAccessTokenData();

        $request_url = Uri::clean_url(sprintf('%s/webservice.php',$tokenData['vtiger_url']));
        $parameters = array(
            'operation' => 'describe',
            'sessionName' => $tokenData['session_id'],
            'elementType' => $type
        );

        $response = $this->auth->makeRequest($request_url, $parameters);

        return $response['result'];
    }


}