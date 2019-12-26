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

    public function __construct(
        Client $httpClient,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->logger     = $logger;
    }

    /**
     * @return array
     *
     * @throws ApiException
     */
    public function getPlugins(): array
    {
        return $this->makeRequest('https://packagist.org/search.json?type=mautic-plugin');
    }

    /**
     * @return array
     *
     * @throws ApiException
     */
    public function getPlugin(string $pluginName): array
    {
        return $this->makeRequest("https://repo.packagist.org/p/{$pluginName}.json");
    }

    /**
     * @throws ApiException
     */
    public function download(string $sourceUrl, string $destinationPath): void
    {
        $request = new Request(RequestInterface::GET, $sourceUrl, $this->getHeaders());

        if (false === file_put_contents($destinationPath, $this->httpClient->send($request)->getBody()->getContents())) {
            throw new \Exception("Could not save the package zip file into {$destinationPath}. Check the permissions.");
        }
    }

    public function makeRequest(string $url): array
    {
        $this->logger->debug('About to query the Packagist API: '.$url);

        $request  = new Request(RequestInterface::GET, $url, $this->getHeaders());
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
