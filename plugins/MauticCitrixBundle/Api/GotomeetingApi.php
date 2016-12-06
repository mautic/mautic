<?php

namespace MauticPlugin\MauticCitrixBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class GotomeetingApi extends CitrixApi
{
    /**
     * @param string $operation
     * @param array  $parameters
     * @param string $method
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function request($operation, array $parameters = [], $method = 'GET')
    {
        $settings = [
            'module'     => 'G2M',
            'method'     => $method,
            'parameters' => $parameters,
        ];

        if (preg_match('/start$/', $operation)) {
            $settings['requestSettings'] = [
                'auth_type' => 'none',
                'headers'   => [
                    'Authorization' => 'OAuth oauth_token='.$this->integration->getApiKey(),
                ],
            ];
        }

        return parent::_request($operation, $settings);
    }
}
