<?php
namespace Mautic\ZohoBundle\Api\Api;

class Object extends Api
{
    /**
     * List types
     *
     * @return mixed
     */
    public function getFields($module)
    {
        $tokenData = $this->auth->getAccessTokenData();

        $request_url = sprintf('%s%s/getFields',$tokenData['endpoint_url'],$module);
        $parameters = array(
            'authtoken' => $tokenData['authtoken'],
            'scope' => 'crmapi'
        );

        $response = $this->auth->makeRequest($request_url, $parameters);

        return $response;
    }
}