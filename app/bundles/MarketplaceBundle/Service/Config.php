<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class Config
{
    public const MARKETPLACE_ENABLED           = 'marketplace_enabled';

    public const MARKETPLACE_WEBSITE_URL       = 'marketplace_website_url';

    public const MARKETPLACE_REGISTRY_URL      = 'marketplace_registry_url';

    public function __construct(
        private readonly CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function marketplaceIsEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get(self::MARKETPLACE_ENABLED);
    }

    public function getMarketplaceWebsiteUrl(): string
    {
        return $this->coreParametersHelper->get(self::MARKETPLACE_WEBSITE_URL);
    }

    /**
     * Base URL of the marketplace application, which fronts the package registry.
     *
     * Deliberately a different parameter from the old marketplace_api_base: that one pointed
     * straight at the storage backend, so a stale value carried over from an older install
     * would silently 404 against these routes.
     */
    public function getRegistryUrl(): string
    {
        return rtrim((string) $this->coreParametersHelper->get(self::MARKETPLACE_REGISTRY_URL), '/');
    }

    public function isComposerEnabled(): bool
    {
        return $this->coreParametersHelper->get('composer_updates', false);
    }
}
