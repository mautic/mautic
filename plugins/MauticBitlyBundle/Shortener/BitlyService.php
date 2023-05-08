<?php

declare(strict_types=1);

namespace MauticPlugin\MauticBitlyBundle\Shortener;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Mautic\CoreBundle\Shortener\ShortenerServiceInterface;
use MauticPlugin\MauticBitlyBundle\Integration\Config;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class BitlyService implements ShortenerServiceInterface
{
    private const BITLY_API_SHORTEN = 'https://api-ssl.bitly.com/v4/shorten';

    private Client $client;
    private Config $config;
    private LoggerInterface $logger;

    public function __construct(Client $client, Config $config, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->config = $config;
        $this->logger = $logger;
    }

    public function shortenUrl(string $url): string
    {
        if (false === $this->isEnabled()) {
            return $url;
        }

        try {
            $response = $this->client->request('POST', self::BITLY_API_SHORTEN, [
                'json' => [
                    'long_url' => $url,
                ],
                'headers' => [
                    'Authorization' => 'Bearer '.$this->config->getAcccessToken(),
                    'Content-Type'  => 'application/json',
                ],
            ]);
            $content = json_decode($response->getBody()->getContents(), true);

            return $content['link'] ?? $url;
        } catch (GuzzleException|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface $e) {
            $this->logger->error($e->getMessage());

            return $url;
        }
    }

    public function isEnabled(): bool
    {
        return $this->config->isPublished() && $this->config->isConfigured();
    }
}
