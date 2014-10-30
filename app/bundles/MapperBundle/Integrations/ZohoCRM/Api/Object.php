<?php
namespace ZohoCRM\Api;

use MauticMapper\Uri\Uri;

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

        $request_url = Uri::clean_url(sprintf('%s/%s/getFields',$tokenData['endpoint_url'],$module));
        $parameters = array(
            'authtoken' => $tokenData['authtoken'],
            'scope' => 'crmapi'
        );

        $response = $this->auth->makeRequest($request_url, $parameters);

        return $response;
    }
}