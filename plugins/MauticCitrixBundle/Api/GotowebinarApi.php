<?php

namespace MauticPlugin\MauticCitrixBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class GotowebinarApi extends CitrixApi
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
            'module'          => 'G2W',
            'method'          => $method,
            'parameters'      => $parameters,
            'requestSettings' => [
              'headers' => [
                  'Accept' => 'application/json;charset=UTF-8',
              ],
            ],
        ];

        return parent::_request($operation, $settings,
            sprintf('rest/organizers/%s', $this->integration->getOrganizerKey()));
    }
}
