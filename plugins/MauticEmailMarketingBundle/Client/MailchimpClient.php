<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Client;

use MauticPlugin\MauticEmailMarketingBundle\Exception\ApiException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Mailchimp Marketing API 3.0.
 */
class MailchimpClient
{
    private const string VERSION = '3.0';

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
        $response = $this->request($keys, 'GET', 'lists', ['limit' => 100]);

        $lists = [];
        foreach ($response['lists'] ?? [] as $list) {
            $lists[(string) $list['id']] = (string) $list['name'];
        }

        return $lists;
    }

    /**
     * @param array<string, mixed> $keys
     *
     * @return array<string, array{label: string, required: bool}> by merge tag, including EMAIL
     *
     * @throws ApiException
     */
    public function getMergeFields(array $keys, string $listId): array
    {
        $response = $this->request($keys, 'GET', 'lists/'.$listId.'/merge-fields');

        $fields = [];
        foreach ($response['merge_fields'] ?? [] as $field) {
            $fields[(string) $field['tag']] = [
                'label'    => (string) $field['name'],
                'required' => (bool) $field['required'],
            ];
        }

        $fields['EMAIL'] = [
            'label'    => 'Email',
            'required' => true,
        ];

        return $fields;
    }

    /**
     * @param array<string, mixed>  $keys
     * @param array<string, string> $mergeFields
     *
     * @throws ApiException
     */
    public function subscribe(array $keys, string $listId, string $email, array $mergeFields, bool $doubleOptin, bool $sendWelcome): void
    {
        $parameters = [
            'status'       => $doubleOptin ? 'pending' : 'subscribed',
            'send_welcome' => $sendWelcome,
            'id'           => $listId,
        ];
        if ([] !== $mergeFields) {
            $parameters['merge_fields'] = $mergeFields;
        }
        $parameters['email_address'] = $email;

        // A contact already on the list stays as it is, as with the former integration
        $this->request($keys, 'POST', 'lists/'.$listId.'/members', json: $parameters, acceptedErrors: ['Member Exists']);
    }

    /**
     * @param array<string, mixed>      $keys
     * @param array<string, int|string> $query
     * @param array<string, mixed>|null $json
     * @param string[]                  $acceptedErrors titles of error responses that do not fail the request
     *
     * @return array<string, mixed>
     *
     * @throws ApiException
     */
    private function request(array $keys, string $method, string $endpoint, array $query = [], ?array $json = null, array $acceptedErrors = []): array
    {
        if (!empty($keys['password'])) {
            // An API key carries its data center after the dash
            $parts = explode('-', (string) $keys['password']);
            if (2 !== count($parts)) {
                throw new ApiException('Invalid key');
            }

            $apiUrl        = 'https://'.$parts[1].'.api.mailchimp.com';
            $authorization = 'Basic '.base64_encode(($keys['username'] ?? '').':'.$keys['password']);
        } elseif (!empty($keys['access_token']) && !empty($keys['api_endpoint'])) {
            // Connected through the former OAuth2 flow; Mailchimp access tokens do not expire
            $apiUrl        = (string) $keys['api_endpoint'];
            $authorization = 'OAuth '.$keys['access_token'];
        } else {
            throw new ApiException('Mailchimp is not configured.');
        }

        $options = [
            'headers' => ['Authorization' => $authorization],
            'query'   => $query,
        ];
        if (null !== $json) {
            $options['json'] = $json;
        }

        try {
            $response = $this->httpClient->request($method, sprintf('%s/%s/%s', $apiUrl, self::VERSION, $endpoint), $options);
            $data     = $response->toArray(false);
        } catch (ExceptionInterface $e) {
            throw new ApiException($e->getMessage(), 0, $e);
        }

        if (!empty($data['status']) && 'error' === $data['status']) {
            throw new ApiException((string) ($data['error'] ?? 'Mailchimp returned an error.'));
        }

        if (!empty($data['errors']) && is_array($data['errors'])) {
            throw new ApiException(implode(' ', array_map(static fn (array $error): string => (string) $error['message'], $data['errors'])));
        }

        // Errors come back as problem details
        if ($response->getStatusCode() >= 400 && !in_array($data['title'] ?? null, $acceptedErrors, true)) {
            throw new ApiException((string) ($data['detail'] ?? $data['title'] ?? 'Mailchimp returned HTTP '.$response->getStatusCode().'.'));
        }

        return $data;
    }
}
