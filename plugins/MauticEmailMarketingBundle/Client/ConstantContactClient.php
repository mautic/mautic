<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Client;

use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Constant Contact API v2.
 */
class ConstantContactClient
{
    private const string VERSION = 'v2';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @param array<string, mixed> $keys
     *
     * @return array<string, string> list name by list ID
     *
     * @throws ApiException
     */
    public function getLists(array $keys): array
    {
        $lists = [];
        foreach ($this->request($keys, 'GET', 'lists') as $list) {
            $lists[(string) $list['id']] = (string) $list['name'];
        }

        return $lists;
    }

    /**
     * @param array<string, mixed> $keys
     * @param array<string, mixed> $fields
     *
     * @throws ApiException
     */
    public function subscribe(array $keys, string $listId, string $email, array $fields, string $actionBy): void
    {
        $parameters = array_merge($fields, [
            'lists' => [
                ['id' => $listId],
            ],
            'email_addresses' => [
                ['email_address' => $email],
            ],
        ]);

        $this->request($keys, 'POST', 'contacts', ['action_by' => $actionBy], $parameters);
    }

    /**
     * @param array<string, mixed>      $keys
     * @param array<string, string>     $query
     * @param array<string, mixed>|null $json
     *
     * @return array<mixed>
     *
     * @throws ApiException
     */
    private function request(array $keys, string $method, string $endpoint, array $query = [], ?array $json = null): array
    {
        if (empty($keys['client_id']) || empty($keys['access_token'])) {
            throw new ApiException('Constant Contact is not configured.');
        }

        $options = [
            'headers' => ['Authorization' => 'Bearer '.$keys['access_token']],
            'query'   => ['api_key' => (string) $keys['client_id']] + $query,
        ];
        if (null !== $json) {
            $options['json'] = $json;
        }

        try {
            $response = $this->httpClient->request($method, sprintf('https://api.constantcontact.com/%s/%s', self::VERSION, $endpoint), $options);
            $data     = $response->toArray(false);
        } catch (ExceptionInterface $e) {
            throw new ApiException($e->getMessage(), 0, $e);
        }

        if (!empty($data[0]['error_message'])) {
            throw new ApiException(implode(' ', array_map(static fn (array $error): string => (string) $error['error_message'], $data)));
        }

        if ($response->getStatusCode() >= 400) {
            throw new ApiException('Constant Contact returned HTTP '.$response->getStatusCode().'.');
        }

        return $data;
    }
}
