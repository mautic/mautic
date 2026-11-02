<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\Api;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class Client
{
    public const OPERATIONS = [
        'enrich_company' => ['GET', '/companies/enrich', 'Enrich company by domain'],
        'enrich_company_properties' => ['POST', '/companies/enrich', 'Enrich company by name or social profile'],
        'enrich_companies_batch' => ['POST', '/companies/enrich/batch', 'Enrich up to 50 companies'],
        'get_company' => ['GET', '/companies', 'Get company by ID'],
        'search_companies' => ['POST', '/companies/search', 'Search companies'],
        'count_companies' => ['POST', '/companies/search/count', 'Count matching companies'],
        'similar_companies' => ['POST', '/companies/similar', 'Find similar companies'],
        'count_similar' => ['POST', '/companies/similar/count', 'Count similar companies'],
        'workforce' => ['GET', '/companies/workforce', 'Company workforce'],
        'lookup_person' => ['POST', '/people/lookup', 'Look up person by existing email'],
        'get_person' => ['GET', '/people', 'Get person by ID'],
        'search_people' => ['POST', '/people/search', 'Search professional profiles'],
        'person_education' => ['GET', '/people/education', 'Get person education'],
        'autocomplete_companies' => ['GET', '/companies/autocomplete', 'Autocomplete company domains'],
        'preview_companies' => ['POST', '/companies/search/preview', 'Preview companies (plan access required)'],
        'preview_similar' => ['POST', '/companies/similar/preview', 'Preview similar companies (plan access required)'],
    ];

    public function __construct(private HttpClientInterface $httpClient)
    {
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     *
     * @return array{data: mixed, credits_consumed: ?float}
     */
    public function query(string $apiKey, string $operation, array $query = [], array $body = []): array
    {
        if (!isset(self::OPERATIONS[$operation])) {
            throw new \InvalidArgumentException('Choose a supported CompanyEnrich operation.');
        }
        if ('' === trim($apiKey) || preg_match('/[\r\n]/', $apiKey)) {
            throw new \InvalidArgumentException('Configure a CompanyEnrich API key in Plugins.');
        }

        $schema = json_decode(file_get_contents(__DIR__.'/operations.json'), true, 64, JSON_THROW_ON_ERROR)[$operation];
        $parameters = [];
        foreach ($schema['parameters'] as $parameter) {
            $parameters[$parameter['name']] = $parameter['schema'];
            if (($parameter['required'] ?? false) && !array_key_exists($parameter['name'], $query)) {
                throw new \InvalidArgumentException('Missing query parameter: '.$parameter['name']);
            }
        }
        Validator::check($query, ['type' => 'object', 'properties' => $parameters], 'query');
        if ($schema['body_schema']) {
            Validator::check($body, $schema['body_schema'], 'body');
        } elseif ([] !== $body) {
            throw new \InvalidArgumentException('This operation does not accept a request body.');
        }
        if ('workforce' === $operation && empty($query['id']) && empty($query['domain'])) {
            throw new \InvalidArgumentException('Provide a company domain or ID.');
        }
        if ('enrich_company_properties' === $operation && !array_filter($body, fn ($v): bool => is_string($v) && '' !== trim($v))) {
            throw new \InvalidArgumentException('Provide a company name or social identifier.');
        }
        if ('lookup_person' === $operation && !filter_var($body['email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Enter a valid existing email address.');
        }
        if (isset($query['domain'])) {
            $query['domain'] = self::domain($query['domain']);
        }
        if (isset($body['domains'])) {
            $body['domains'] = array_map(self::domain(...), $body['domains']);
        }
        // An uncached company returns a free 404; do not silently wait and spend again.
        if (in_array($operation, ['enrich_company', 'similar_companies', 'count_similar', 'preview_similar'], true) && !array_key_exists('waitForEnrichment', $query)) {
            $query['waitForEnrichment'] = false;
        }
        [$method, $path] = self::OPERATIONS[$operation];
        $parts = [];
        foreach ($query as $name => $value) {
            foreach (is_array($value) ? $value : [$value] as $item) {
                $parts[] = rawurlencode($name).'='.rawurlencode(is_bool($item) ? ($item ? 'true' : 'false') : (string) $item);
            }
        }
        $options = [
            'headers' => ['Authorization' => 'Bearer '.trim($apiKey), 'Accept' => 'application/json'],
            'max_redirects' => 0,
            'timeout' => 30,
            'max_duration' => 35,
        ];
        if ('POST' === $method) {
            $options['json'] = [] === $body ? new \stdClass() : $body;
        }
        try {
            $response = $this->httpClient->request($method, 'https://api.companyenrich.com'.$path.($parts ? '?'.implode('&', $parts) : ''), $options);
            $status = $response->getStatusCode();
            if ($status < 200 || $status >= 300) {
                $response->cancel();
                throw new \RuntimeException(match ($status) {
                    401, 403 => 'CompanyEnrich rejected the API key or account access.', 402 => 'The CompanyEnrich account has insufficient credits.', 404 => 'No matching CompanyEnrich record is currently available. No automatic retry was made.', 429 => 'CompanyEnrich rate limit reached. Try again later.', 400, 422 => 'CompanyEnrich rejected the input. Check the operation documentation and filters.', default => 'CompanyEnrich request failed (HTTP '.$status.'). No automatic retry was made.',
                });
            }
            $content = '';
            foreach ($this->httpClient->stream($response) as $chunk) {
                $content .= $chunk->getContent();
                if (strlen($content) > 4 * 1024 * 1024) {
                    $response->cancel();
                    throw new \RuntimeException('CompanyEnrich response is too large. Reduce the page size.');
                }
            }
            $data = json_decode($content, true, 64, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
            $cost = $response->getHeaders(false)['x-credit-cost'][0] ?? null;
            if (is_array($data) && isset($data['creditsConsumed'])) {
                $cost = $data['creditsConsumed'];
            }

            return ['data' => $data, 'credits_consumed' => is_numeric($cost) && is_finite((float) $cost) && (float) $cost >= 0 ? (float) $cost : null];
        } catch (TransportExceptionInterface) {
            throw new \RuntimeException('CompanyEnrich could not be reached. No automatic retry was made.');
        } catch (\JsonException) {
            throw new \RuntimeException('CompanyEnrich returned an invalid JSON response.');
        }
    }

    public static function domain(string $input): string
    {
        $input = trim($input);
        $parts = parse_url(str_contains($input, '://') ? $input : 'https://'.$input);
        $host = strtolower($parts['host'] ?? '');
        if (!$parts || !in_array($parts['scheme'] ?? '', ['https', 'http'], true) || isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || !str_contains($host, '.') || filter_var($host, FILTER_VALIDATE_IP) || !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new \InvalidArgumentException('Enter a valid company website domain.');
        }

        return $host;
    }
}
