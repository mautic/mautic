<?php

namespace MauticPlugin\MauticCitrixBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class GotoassistApi extends CitrixApi
{
    /**
     * @param string $operation
     * @param string $method
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function request($operation, array $parameters = [], $method = 'GET')
    {
        $settings = [
            'module'          => 'G2A',
            'method'          => $method,
            'parameters'      => $parameters,
        ];

        return parent::_request($operation, $settings, 'rest/v1');
    }
}
