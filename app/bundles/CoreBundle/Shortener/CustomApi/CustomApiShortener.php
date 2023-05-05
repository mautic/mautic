<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Shortener\CustomApi;

use GuzzleHttp\Client;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Psr\Log\LoggerInterface;

class CustomApiShortener
{
    private Client $client;

    private CoreParametersHelper $coreParametersHelper;

    private LoggerInterface $logger;

    public function __construct(CoreParametersHelper $coreParametersHelper, Client $client, LoggerInterface $logger)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->client               = $client;
        $this->logger               = $logger;
    }

    public function shortenUrl(string $url): string
    {
        $shortenerServiceUrl = $this->coreParametersHelper->get('link_shortener_url');
        if (!$shortenerServiceUrl) {
            return $url;
        }

        try {
            $response = $this->client->get("{$shortenerServiceUrl}".urlencode($url));

            if (200 === $response->getStatusCode()) {
                return rtrim((string) $response->getBody());
            } else {
                $this->logger->warning("URL shortener failed with code {$response->getStatusCode()}: {$response->getBody()}");
            }
        } catch (\Exception $exception) {
            $this->logger->error(
                $exception->getMessage(),
                ['exception' => $exception]
            );
        }

        return $url;
    }
}
