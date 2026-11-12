<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Helper;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;

/**
 * Decrypted API keys of an integration, for the services that cannot depend on IntegrationsHelper themselves.
 */
class IntegrationKeys
{
    public function __construct(
        private readonly IntegrationsHelper $integrationsHelper,
    ) {
    }

    /**
     * @return array<string, mixed> empty while the integration has no saved settings
     */
    public function get(string $integration): array
    {
        try {
            return $this->integrationsHelper->getIntegration($integration)->getIntegrationConfiguration()->getApiKeys();
        } catch (IntegrationNotFoundException) {
            return [];
        }
    }
}
