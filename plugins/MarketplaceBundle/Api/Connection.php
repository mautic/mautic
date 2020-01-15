<?php

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Api;

use Guzzle\Http\Message\RequestInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use MauticPlugin\MarketplaceBundle\Exception\ApiException;
use Psr\Log\LoggerInterface;

class Connection
{
    private $httpClient;
    private $logger;

    public function __construct(Client $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger     = $logger;
    }

    /**
     * @throws ApiException
     */
    public function getPlugins(): array
    {
        return $this->makeRequest('https://packagist.org/search.json?type=mautic-plugin');
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
