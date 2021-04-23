<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Api;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Mautic\MarketplaceBundle\Exception\ApiException;
use Psr\Log\LoggerInterface;

class Connection
{
    private ClientInterface $httpClient;

    private LoggerInterface $logger;

    public function __construct(ClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger     = $logger;
    }

    /**
     * @throws ApiException
     */
    public function getPlugins(int $page, int $limit, string $query = ''): array
    {
        return $this->makeRequest("https://packagist.org/search.json?page={$page}&per_page={$limit}&type=mautic-plugin&q={$query}");
    }

    /**
     * @throws ApiException
     */
    public function getPackage(string $pluginName): array
    {
        return $this->makeRequest("https://packagist.org/packages/{$pluginName}.json");
    }

    public function makeRequest(string $url): array
    {
        $this->logger->debug('About to query the Packagist API: '.$url);

        $request  = new Request('GET', $url, $this->getHeaders());
        $response = $this->httpClient->send($request);
        $body     = (string) $response->getBody();

        if ($response->getStatusCode() >= 300) {
            throw new ApiException($body, $response->getStatusCode());
        }

        $payload = json_decode($body, true);

        $this->logger->debug('Successful Packagist API response', ['payload' => $payload]);

        return $payload;
    }

    private function getHeaders(): array
    {
        return  [
            'Content-Type'    => 'application/json',
            'Accept'          => 'application/json',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection'      => 'keep-alive',
            'User-Agent'      => 'Mautic Marketplace',
        ];
    }
}
