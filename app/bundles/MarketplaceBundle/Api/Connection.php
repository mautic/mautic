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
        private ClientInterface $httpClient,
        private LoggerInterface $logger,
        private Config $config
    ) {
    }

    /**
     * @throws ApiException
     */
    public function getPlugins(int $page, int $limit, string $query = ''): array
    {
        return $this->makeRequest(sprintf('%s/rest/v1/rpc/get_data_with_total?apikey=%s&_limit=%s&_offset=%s&_type=mautic-plugin&_query=%s',
            $this->config->getSupabaseUrl(),
            $this->config->getSupabaseApiKey(),
            $limit,
            ($page - 1) * $limit,
            $query
        ));
    }

    /**
     * @throws ApiException
     */
    public function getPackage(string $pluginName): array
    {
        return $this->makeRequest(sprintf('%s/rest/v1/rpc/get_pack?apikey=%s&packag_name=%s',
            $this->config->getSupabaseUrl(),
            $this->config->getSupabaseApiKey(),
            $pluginName,
        ));
    }

    public function makeRequest(string $url): array
    {
        $this->logger->debug('About to query the Packagist API: '.$url);

        $request  = new Request('GET', $url, $this->getHeaders());

        try {
            $response = $this->httpClient->send($request);
        } catch (GuzzleException $e) {
            throw new ApiException($e->getMessage(), $e->getCode());
        }

        $body = (string) $response->getBody();

        if ($response->getStatusCode() >= 300) {
            throw new ApiException($body, $response->getStatusCode());
        }

        $payload = json_decode($body, true);

        $this->logger->debug('Successful Packagist API response', ['payload' => $payload]);

        return $payload;
    }

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
