<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use GuzzleHttp\ClientInterface;
use Mautic\CacheBundle\Cache\CacheProvider;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\MarketplaceBundle\DTO\Allowlist;
use Mautic\MarketplaceBundle\Exception\ApiException;

class Config
{
    public const MARKETPLACE_ENABLED                     = 'marketplace_enabled';
    public const MARKETPLACE_ALLOWLIST_URL               = 'marketplace_allowlist_url';
    public const MARKETPLACE_ALLOWLIST_CACHE_TTL_SECONDS = 'marketplace_allowlist_cache_ttl_seconds';
    public const MARKETPLACE_ALLOWLIST_CACHE_KEY         = 'marketplace_allowlist';

    private CoreParametersHelper $coreParametersHelper;
    private ClientInterface $httpClient;
    private Allowlist $allowlist;
    private CacheProvider $cache;

    public function __construct(CoreParametersHelper $coreParametersHelper, ClientInterface $httpClient, CacheProvider $cache)
    {
        $this->coreParametersHelper = $coreParametersHelper;
        $this->httpClient           = $httpClient;
        $this->cache                = $cache;
    }

    public function marketplaceIsEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get(self::MARKETPLACE_ENABLED);
    }

    public function getAllowlistUrl(): string
    {
        return $this->coreParametersHelper->get(self::MARKETPLACE_ALLOWLIST_URL);
    }

    public function getAllowList(): ?Allowlist
    {
        if (!empty($this->allowlist)) {
            return $this->allowlist;
        }

        $cache                 = $this->cache->getSimpleCache();
        $cachedAllowlistString = $cache->get(self::MARKETPLACE_ALLOWLIST_CACHE_KEY);

        if (!empty($cachedAllowlistString)) {
            return $this->parseAllowlistJson($cachedAllowlistString);
        }

        if (!empty($this->getAllowListUrl())) {
            $response  = $this->httpClient->request('GET', $this->getAllowlistUrl());
            $body      = (string) $response->getBody();

            if ($response->getStatusCode() >= 300) {
                throw new ApiException($body, $response->getStatusCode());
            }

            // Cache the allowlist for the given amount of seconds (3600 by default).
            $cache->set(
                self::MARKETPLACE_ALLOWLIST_CACHE_KEY,
                $body,
                (int) $this->coreParametersHelper->get(self::MARKETPLACE_ALLOWLIST_CACHE_TTL_SECONDS, 3600)
            );

            return $this->parseAllowlistJson($body);
        }

        return null;
    }

    private function parseAllowlistJson(string $payload): Allowlist
    {
        return Allowlist::fromArray(json_decode($payload, true));
    }
}
