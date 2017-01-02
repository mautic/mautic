<?php

namespace MauticPlugin\MauticCitrixBundle\Api;

use Joomla\Http\Response;
use Mautic\PluginBundle\Exception\ApiErrorException;
use MauticPlugin\MauticCitrixBundle\Integration\CitrixAbstractIntegration;

class CitrixApi
{
    protected $integration;

    public function __construct(CitrixAbstractIntegration $integration)
    {
        $this->integration = $integration;
    }

    /**
     * @param string $operation
     * @param array  $settings
     * @param string $route
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    protected function _request($operation, array $settings, $route = 'rest')
    {
        $requestSettings = [
            'encode_parameters'   => 'json',
            'return_raw'          => 'true', // needed to get the HTTP status code in the response
            'override_auth_token' => 'oauth_token='.$this->integration->getApiKey(),
        ];

        if (array_key_exists('requestSettings', $settings) && is_array($settings['requestSettings'])) {
            $requestSettings = array_merge($requestSettings, $settings['requestSettings']);
        }

        $url = sprintf(
            '%s/%s/%s/%s',
            $this->integration->getApiUrl(),
            $settings['module'],
            $route,
            $operation
        );
        /** @var Response $request */
        $request = $this->integration->makeRequest(
            $url,
            $settings['parameters'],
            $settings['method'],
            $requestSettings
        );
        $status  = $request->code;
        $message = '';

        switch ($status) {
            case 200:
                // request ok
                break;
            case 201:
                // POST ok
                break;
            case 204:
                // PUT/DELETE ok
                break;
            case 400:
                $message = 'Bad request';
                break;
            case 403:
                $message = 'Token invalid';
                break;
            case 404:
                $message = 'The requested object does not exist';
                break;
            case 409:
                $message = 'The user is already registered';
                break;
            default:
                $message = $request->body;
                break;
        }

        if ('' !== $message) {
            throw new ApiErrorException($message);
        }

        return $this->integration->parseCallbackResponse($request->body);
    }
}
