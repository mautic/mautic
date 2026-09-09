<?php

namespace MauticPlugin\MauticEmailMarketingBundle\Api;

use Mautic\PluginBundle\Exception\ApiErrorException;

final class ConstantContactApi extends EmailMarketingApi
{
    private string $version = 'v2';

    /**
     * @param array<string, mixed> $query
     */
    private function request(string $endpoint, array $parameters = [], string $method = 'GET', array $query = [])
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
        }

        return $response;
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
     * @param array                $fields
     * @param array<string, mixed> $config
     *
     * @return mixed|string
     *
     * @throws ApiErrorException
     */
    public function subscribeLead($email, $listId, $fields = [], array $config = [])
    {
        $parameters = array_merge($fields, [
            'lists' => [
                ['id' => "{$listId}"],
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
