<?php
/*
 * @package     Cronfig Mautic Bundle
 * @copyright   2019 Cronfig.io. All rights reserved
 * @author      Jan Linhart
 * @link        http://cronfig.io
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Api;

use GuzzleHttp\Psr7\Request;
use Http\Adapter\Guzzle6\Client;
use Guzzle\Http\Message\RequestInterface;
use MauticPlugin\MarketplaceBundle\Exception\ApiException;
use Psr\Log\LoggerInterface;

class Connection
{
    public const URL = 'https://packagist.org/';
    /**
     * @var Client
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Client $httpClient,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * @return array
     *
     * @throws ApiException
     */
    public function getPlugins(): array
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive',
            'User-Agent' => 'Jan\'s minimal GraphQL client',
        ];

        $url = $this->buildFulUrl('search.json?type=mautic-plugin');

        $this->logger->debug('About to query the Packagist API: '.$url);

        $request = new Request(RequestInterface::GET, $url, $headers);
        $response = $this->httpClient->sendRequest($request);
        $body = (string) $response->getBody();

        if ($response->getStatusCode() >= 300) {
            throw new ApiException($body, $response->getStatusCode());
        }

        $payload = json_decode($body, true);

        $this->logger->debug('Successful Packagist API response', ['payload' => $payload]);

        return $payload;
    }

    private function buildFulUrl(string $endpoint): string
    {
        return self::URL.$endpoint;
    }
}
