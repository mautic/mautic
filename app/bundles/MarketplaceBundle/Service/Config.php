<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class Config
{
    public const MARKETPLACE_ENABLED           = 'marketplace_enabled';
    public const MARKETPLACE_WEBSITE_URL       = 'marketplace_website_url';

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

    public function isComposerEnabled(): bool
    {
        return $this->coreParametersHelper->get('composer_updates', false);
    }
}
