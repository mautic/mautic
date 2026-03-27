<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class Config
{
    public const MARKETPLACE_ENABLED           = 'marketplace_enabled';
    public const MARKETPLACE_WEBSITE_URL       = 'marketplace_website_url';
    public const MARKETPLACE_API_BASE          = 'marketplace_api_base';
    public const MARKETPLACE_API_KEY           = 'marketplace_api_key';

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
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

    public function getApiBase(): string
    {
        return rtrim((string) $this->coreParametersHelper->get(self::MARKETPLACE_API_BASE), '/');
    }

    public function getApiKey(): string
    {
        return (string) $this->coreParametersHelper->get(self::MARKETPLACE_API_KEY);
    }

    public function isComposerEnabled(): bool
    {
        return $this->coreParametersHelper->get('composer_updates', false);
    }
}
