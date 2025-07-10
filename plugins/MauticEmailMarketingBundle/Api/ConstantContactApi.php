<?php

namespace MauticPlugin\MauticEmailMarketingBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

class ConstantContactApi extends EmailMarketingApi
{
    private string $version = 'v2';

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
