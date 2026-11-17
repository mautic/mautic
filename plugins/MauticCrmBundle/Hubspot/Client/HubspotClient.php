<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Hubspot\Client;

use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use MauticPlugin\MauticCrmBundle\Hubspot\Exception\HubspotApiException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * HubSpot CRM v3 API, authenticated with a private app access token.
 */
class HubspotClient
{
    public const string CONTACTS  = 'contacts';

    public const string COMPANIES = 'companies';

    /**
     * Most records one batch request accepts.
     */
    public const int BATCH_SIZE = 100;

    private const string API_URL = 'https://api.hubapi.com';

    /**
     * The property holding the last modification date of each object.
     */
    private const array LAST_MODIFIED_PROPERTY = [
        self::CONTACTS  => 'lastmodifieddate',
        self::COMPANIES => 'hs_lastmodifieddate',
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly Config $config,
    ) {
    }

    /**
     * @return list<array<string, mixed>> the object's property definitions
     */
    public function getProperties(string $object): array
    {
        return array_values($this->request('GET', sprintf('/crm/v3/properties/%s', $object))['results'] ?? []);
    }

    /**
     * Objects modified in the time frame, oldest first.
     *
     * @param string[] $properties
     *
     * @return array{results: list<array<string, mixed>>, after: ?string}
     */
    public function searchModified(string $object, array $properties, ?\DateTimeInterface $from, ?\DateTimeInterface $to, ?string $after = null): array
    {
        $lastModified = self::LAST_MODIFIED_PROPERTY[$object];

        $filters = [];
        if (null !== $from) {
            $filters[] = ['propertyName' => $lastModified, 'operator' => 'GTE', 'value' => $this->toMilliseconds($from)];
        }
        if (null !== $to) {
            $filters[] = ['propertyName' => $lastModified, 'operator' => 'LTE', 'value' => $this->toMilliseconds($to)];
        }

        $body = [
            'filterGroups' => [] === $filters ? [] : [['filters' => $filters]],
            'sorts'        => [['propertyName' => $lastModified, 'direction' => 'ASCENDING']],
            'properties'   => array_values(array_unique([...$properties, $lastModified])),
            'limit'        => self::BATCH_SIZE,
        ];
        if (null !== $after) {
            $body['after'] = $after;
        }

        $response = $this->request('POST', sprintf('/crm/v3/objects/%s/search', $object), $body);

        return [
            'results' => array_values($response['results'] ?? []),
            'after'   => isset($response['paging']['next']['after']) ? (string) $response['paging']['next']['after'] : null,
        ];
    }

    /**
     * @param string[] $ids
     * @param string[] $properties
     *
     * @return list<array<string, mixed>>
     */
    public function read(string $object, array $ids, array $properties): array
    {
        $results = [];
        foreach (array_chunk(array_values($ids), self::BATCH_SIZE) as $chunk) {
            $response = $this->request('POST', sprintf('/crm/v3/objects/%s/batch/read', $object), [
                'inputs'     => array_map(static fn (string $id): array => ['id' => $id], $chunk),
                'properties' => array_values($properties),
            ]);
            array_push($results, ...array_values($response['results'] ?? []));
        }

        return $results;
    }

    /**
     * Creates contacts or updates the ones that already have the email.
     *
     * @param array<string, array<string, string>> $propertiesByEmail
     *
     * @return array{results: list<array<string, mixed>>, errors: list<array<string, mixed>>}
     */
    public function upsertContactsByEmail(array $propertiesByEmail): array
    {
        $inputs = [];
        foreach ($propertiesByEmail as $email => $properties) {
            $inputs[] = ['idProperty' => 'email', 'id' => (string) $email, 'properties' => $properties];
        }

        return $this->batch(sprintf('/crm/v3/objects/%s/batch/upsert', self::CONTACTS), $inputs);
    }

    /**
     * @param array<int|string, array<string, string>> $propertiesById numeric IDs become integer keys
     *
     * @return array{results: list<array<string, mixed>>, errors: list<array<string, mixed>>}
     */
    public function update(string $object, array $propertiesById): array
    {
        $inputs = [];
        foreach ($propertiesById as $id => $properties) {
            $inputs[] = ['id' => (string) $id, 'properties' => $properties];
        }

        return $this->batch(sprintf('/crm/v3/objects/%s/batch/update', $object), $inputs);
    }

    /**
     * @param array<string, string> $properties
     *
     * @return array<string, mixed> the created object
     */
    public function create(string $object, array $properties): array
    {
        return $this->request('POST', sprintf('/crm/v3/objects/%s', $object), ['properties' => $properties]);
    }

    /**
     * @param list<array<string, mixed>> $inputs
     *
     * @return array{results: list<array<string, mixed>>, errors: list<array<string, mixed>>}
     */
    private function batch(string $path, array $inputs): array
    {
        $results = [];
        $errors  = [];
        foreach (array_chunk($inputs, self::BATCH_SIZE) as $chunk) {
            // A partly failed batch answers 207 with the failures in "errors"
            $response = $this->request('POST', $path, ['inputs' => $chunk]);
            array_push($results, ...array_values($response['results'] ?? []));
            array_push($errors, ...array_values($response['errors'] ?? []));
        }

        return ['results' => $results, 'errors' => $errors];
    }

    /**
     * @param array<string, mixed>|null $json
     *
     * @return array<string, mixed>
     *
     * @throws HubspotApiException
     */
    private function request(string $method, string $path, ?array $json = null): array
    {
        $token = $this->config->getAccessToken();
        if (null === $token) {
            throw new HubspotApiException('HubSpot is not configured: the private app access token is missing.');
        }

        $options = ['headers' => ['Authorization' => 'Bearer '.$token]];
        if (null !== $json) {
            $options['json'] = $json;
        }

        try {
            $response = $this->httpClient->request($method, self::API_URL.$path, $options);
            $status   = $response->getStatusCode();
            $data     = '' === $response->getContent(false) ? [] : $response->toArray(false);
        } catch (ExceptionInterface $e) {
            throw new HubspotApiException($e->getMessage(), 0, $e);
        }

        if ($status >= 400) {
            throw new HubspotApiException(is_string($data['message'] ?? null) ? $data['message'] : sprintf('HubSpot answered with HTTP %d.', $status), $status);
        }

        return $data;
    }

    private function toMilliseconds(\DateTimeInterface $dateTime): string
    {
        return (string) ($dateTime->getTimestamp() * 1000);
    }
}
