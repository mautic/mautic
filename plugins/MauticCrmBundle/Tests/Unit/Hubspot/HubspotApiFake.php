<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Tests\Unit\Hubspot;

use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use PHPUnit\Framework\Assert;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

/**
 * A HubSpot API answering from queued responses per "METHOD path", with the contact and company properties built in.
 */
final class HubspotApiFake
{
    public const array CONTACT_PROPERTIES = [
        ['name' => 'email', 'label' => 'Email', 'type' => 'string', 'fieldType' => 'text'],
        ['name' => 'firstname', 'label' => 'First name', 'type' => 'string', 'fieldType' => 'text'],
        ['name' => 'interests', 'label' => 'Interests', 'type' => 'enumeration', 'fieldType' => 'checkbox'],
        ['name' => 'hs_analytics_source', 'label' => 'Original source', 'type' => 'enumeration', 'fieldType' => 'select', 'modificationMetadata' => ['readOnlyValue' => true]],
        ['name' => 'hs_secret', 'label' => 'Hidden', 'type' => 'string', 'hidden' => true],
    ];

    public const array COMPANY_PROPERTIES = [
        ['name' => 'name', 'label' => 'Company name', 'type' => 'string', 'fieldType' => 'text'],
        ['name' => 'domain', 'label' => 'Domain', 'type' => 'string', 'fieldType' => 'text'],
    ];

    /**
     * @var array<string, list<MockResponse>>
     */
    private array $responses = [];

    /**
     * @var list<array{route: string, body: mixed}>
     */
    private array $requests = [];

    /**
     * @param array<string, mixed> $body
     */
    public function queue(string $route, array $body, int $status = 200): self
    {
        $this->responses[$route][] = new MockResponse((string) json_encode($body), ['http_code' => $status]);

        return $this;
    }

    /**
     * @return list<mixed> the JSON bodies sent to the route, in order
     */
    public function sentTo(string $route): array
    {
        return array_values(array_map(
            static fn (array $request): mixed => $request['body'],
            array_filter($this->requests, static fn (array $request): bool => $request['route'] === $route)
        ));
    }

    public function client(Config $config): HubspotClient
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options): MockResponse {
            $route            = $method.' '.parse_url($url, PHP_URL_PATH);
            $this->requests[] = [
                'route' => $route,
                'body'  => isset($options['body']) && '' !== $options['body'] ? json_decode((string) $options['body'], true) : null,
            ];

            $properties = match ($route) {
                'GET /crm/v3/properties/contacts'  => self::CONTACT_PROPERTIES,
                'GET /crm/v3/properties/companies' => self::COMPANY_PROPERTIES,
                default                            => null,
            };
            if (null !== $properties) {
                return new MockResponse((string) json_encode(['results' => $properties]));
            }

            $response = array_shift($this->responses[$route]);

            return $response ?? Assert::fail('Unexpected request: '.$route);
        });

        return new HubspotClient($httpClient, $config);
    }
}
