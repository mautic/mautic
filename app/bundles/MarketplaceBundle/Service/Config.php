<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class Config
{
    public const MARKETPLACE_ENABLED                     = 'marketplace_enabled';

    public const MARKETPLACE_ALLOWLIST_URL               = 'marketplace_allowlist_url';

    public const MARKETPLACE_ALLOWLIST_CACHE_TTL_SECONDS = 'marketplace_allowlist_cache_ttl_seconds';

    public function __construct(
        private CoreParametersHelper $coreParametersHelper
    ) {
    }

    public function marketplaceIsEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get(self::MARKETPLACE_ENABLED);
    }

    public function getAllowlistUrl(): string
    {
        return $this->coreParametersHelper->get(self::MARKETPLACE_ALLOWLIST_URL);
    }

    public function getAllowlistCacheTtlSeconds(): int
    {
        return (int) $this->coreParametersHelper->get(self::MARKETPLACE_ALLOWLIST_CACHE_TTL_SECONDS, 3600);
    }

    public function isComposerEnabled(): bool
    {
        return $this->coreParametersHelper->get('composer_updates', false);
    }
}
