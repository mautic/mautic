<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      WebMecanik
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticWebinarBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class WebikeoApi extends WebinarApi
{
    /**
     * @param        $endpoint
     * @param array  $parameters
     * @param string $method
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function request($endpoint, $parameters = [], $method = 'GET')
    {
        $apiUrl = $this->integration->getApiUrl();

        $url = sprintf('%s/%s', $apiUrl, $endpoint);

        $response = $this->integration->makeRequest($url, $parameters, $method, ['encode_parameters' => 'json']);
        $errors   = [];
        if (is_array($response) && isset($response['code'])) {
            //get errors
            if ($response['code'] == 401) {
                $refreshError = $this->integration->authCallback(['use_refresh_token' => true]);

                if (empty($refreshError)) {
                    return $this->request($endpoint, $parameters, $method);
                } else {
                    $errors[] = $refreshError;
                }
            }
            $errors[] = $response['message'];
            if (!empty($errors)) {
                throw new ApiErrorException(implode(' ', $errors));
            }
        }

        return $response;
    }

    /**
     * @param array $filters
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getWebinars($filters = [])
    {
        return $this->request('webinars', $filters, 'GET');
    }

    public function getSubscriptions($webinarId, $filters = [])
    {
        if ($webinarId) {
            return $this->request('webinars/'.$webinarId.'/subscriptions', $filters, 'GET');
        }

        return [];
    }

    public function subscribeContact($webinarId, $contactData)
    {
        return $this->request('webinars/'.$webinarId.'/subscriptions', $contactData, 'POST');
    }
}
