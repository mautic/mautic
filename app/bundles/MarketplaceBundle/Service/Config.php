<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Service;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class Config
{
    public const MARKETPLACE_ENABLED           = 'marketplace_enabled';
    public const MARKETPLACE_AUTH0_DOMAIN      = 'marketplace_auth0_domain';
    public const MARKETPLACE_AUTH0_CLIENT_ID   = 'marketplace_auth0_client_id';
    public const MARKETPLACE_REVIEWS_API_URL   = 'marketplace_reviews_api_url';

    public function __construct(
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public function marketplaceIsEnabled(): bool
    {
        return (bool) $this->coreParametersHelper->get(self::MARKETPLACE_ENABLED);
    }

    public function isComposerEnabled(): bool
    {
        return $this->coreParametersHelper->get('composer_updates', false);
    }

    public function getAuth0Domain(): string
    {
        return (string) $this->coreParametersHelper->get(self::MARKETPLACE_AUTH0_DOMAIN, '');
    }

    public function getAuth0ClientId(): string
    {
        return (string) $this->coreParametersHelper->get(self::MARKETPLACE_AUTH0_CLIENT_ID, '');
    }

    public function getReviewsApiUrl(): string
    {
        return (string) $this->coreParametersHelper->get(self::MARKETPLACE_REVIEWS_API_URL, '');
    }
}
