<?php

namespace MauticPlugin\MauticCitrixBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class GotoassistApi extends CitrixApi
{

    /**
     * @param string $operation
     * @param array $parameters
     * @param string $method
     * @return mixed|string
     * @throws ApiErrorException
     */
    public function request($operation, array $parameters = [], $method = 'GET')
    {
        $parameters = array_merge($parameters, [
            'oauth_token' => $this->integration->getApiKey(),
            'sessionType' => 'screen_sharing',
        ]);
        $settings = [
            'module'          => 'G2A',
            'method'          => $method,
            'parameters'      => $parameters,
            'requestSettings' => [ 'auth_type' => 'none' ],
        ];
        return parent::_request($operation, $settings, 'rest/v1');
    }

}
