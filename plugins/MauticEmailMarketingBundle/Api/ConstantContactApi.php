<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticEmailMarketingBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class ConstantContactApi extends EmailMarketingApi
{
    private $version = 'v2';

    protected function request($endpoint, $parameters = [], $method = 'GET', $query = [])
    {
        $url = sprintf('https://api.constantcontact.com/%s/%s?api_key=%s', $this->version, $endpoint, $this->keys['client_id']);

        $response = $this->integration->makeRequest($url, $parameters, $method, [
            'encode_parameters' => 'json',
            'append_auth_token' => true,
            'query'             => $query,
        ]);

        if (is_array($response) && !empty($response[0]['error_message'])) {
            $errors = [];
            foreach ($response as $error) {
                $errors[] = $error['error_message'];
            }

            throw new ApiErrorException(implode(' ', $errors));
        } else {
            return $response;
        }
    }

    /**
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function getLists()
    {
        return $this->request('lists');
    }

    /**
     * @param       $email
     * @param       $listId
     * @param array $fields
     * @param array $config
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function subscribeLead($email, $listId, $fields = [], $config = [])
    {
        $parameters = array_merge($fields, [
            'lists' => [
                ['id' => "$listId"],
            ],
            'email_addresses' => [
                ['email_address' => $email],
            ],
        ]);

        $query = [
            'action_by' => $config['action_by'],
        ];

        return $this->request('contacts', $parameters, 'POST', $query);
    }
}
