<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Helper;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\NotificationBundle\Integration\OneSignalIntegration;

class OneSignalIntegrationHelper
{
    public function __construct(
        private readonly OneSignalIntegration $integration,
        private readonly IntegrationsHelper $integrationsHelper,
    ) {
    }

    /**
     * The integration with its settings loaded and API keys decrypted,
     * or null while OneSignal has no settings row yet.
     */
    public function getIntegration(): ?OneSignalIntegration
    {
        try {
            $this->integrationsHelper->getIntegrationConfiguration($this->integration);
        } catch (IntegrationNotFoundException) {
            return null;
        }

        return $this->integration;
    }

    public function getPublishedIntegration(): ?OneSignalIntegration
    {
        $integration = $this->getIntegration();

        return $integration?->getIntegrationConfiguration()->getIsPublished() ? $integration : null;
    }
}
