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

    /**
     * Supabase's anonymous key for the public marketplace. It is a publishable identifier, not a
     * secret — the marketplace site ships it to every browser, and what actually guards the data
     * is row-level security on the Supabase side.
     *
     * It lives here so a stock Mautic can browse the marketplace with no configuration. Point
     * MAUTIC_MARKETPLACE_API_KEY at your own instance to override it.
     */
    private const DEFAULT_API_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InBkbW5jYnBpbG54dHJod2FpdHRkIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTQ2NTM2MzcsImV4cCI6MjA3MDIyOTYzN30.zVl0eqXdkluLAMRGqf381peTk2TWyYkd01KRPseLlss';

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

    public function getApiBase(): string
    {
        return rtrim((string) $this->coreParametersHelper->get(self::MARKETPLACE_API_BASE), '/');
    }

    public function getApiKey(): string
    {
        $key = (string) $this->coreParametersHelper->get(self::MARKETPLACE_API_KEY);

        return '' !== $key ? $key : self::DEFAULT_API_KEY;
    }

    public function isComposerEnabled(): bool
    {
        return $this->coreParametersHelper->get('composer_updates', false);
    }
}
