<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Mautic\MarketplaceBundle\Exception\ApiException;
use Mautic\MarketplaceBundle\Service\Config;
use Psr\Log\LoggerInterface;

class Connection
{
    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly Config $config,
    ) {
    }

    /**
     * @throws ApiException
     */
    public function getPlugins(int $page, int $limit, string $query = '', ?string $type = null): array
    {
        $queryParams = [
            'page'  => $page,
            'limit' => $limit,
        ];

        if ('' !== $query) {
            $queryParams['query'] = $query;
        }

        if (null !== $type && '' !== $type) {
            $queryParams['type'] = $type;
        }

        $url = $this->config->getRegistryUrl().'/api/registry/v1/packages?'.http_build_query($queryParams);

        return $this->makeRequest($url);
    }

    /**
     * @return mixed[]
     *
     * @throws ApiException
     */
    public function getPackage(string $pluginName): array
    {
        $url = $this->config->getRegistryUrl().'/api/registry/v1/packages/'.implode('/', array_map(rawurlencode(...), explode('/', $pluginName, 2)));

        return $this->makeRequest($url);
    }

    /**
     * @return mixed[]
     *
     * @throws ApiException
     */
    public function makeRequest(string $url): array
    {
        $this->logger->debug('About to query the marketplace API: '.$url);

        $request = new Request('GET', $url, $this->getHeaders());

        try {
            $response = $this->httpClient->send($request);
        } catch (GuzzleException $e) {
            throw new ApiException($e->getMessage(), $e->getCode(), $e);
        }

        $body = (string) $response->getBody();

        if ($response->getStatusCode() >= 300) {
            throw new ApiException($body, $response->getStatusCode());
        }

        $payload = json_decode($body, true);

        $this->logger->debug('Successful Packagist API response', ['payload' => $payload]);

        return $payload;
    }

    /**
     * @return array<string, string>
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type'    => 'application/json',
            'Accept'          => 'application/json',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection'      => 'keep-alive',
            'User-Agent'      => 'Mautic Marketplace',
        ];
    }
}
