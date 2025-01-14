<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\BC;

use Mautic\PluginBundle\Entity\Integration;

trait BcIntegrationSettingsTrait
{
    /**
     * @deprecated Use setIntegrationConfiguration
     */
    public function setIntegrationSettings(Integration $integration): void
    {
        $this->setIntegrationConfiguration($integration);
    }

    /**
     * @deprecated Use getIntegrationConfiguration
     */
    public function getIntegrationSettings(): ?Integration
    {
        return $this->hasIntegrationConfiguration() ? $this->getIntegrationConfiguration() : null;
    }

    /**
     * @deprecated Implement ConfigFormFeaturesInterface instead
     */
    public function getSupportedFeatures(): array
    {
        return [];
    }

    /**
     * @deprecated Required by Mautic\PluginBundle\Helper\IntegrationHelper
     *
     * @return int
     */
    public function getPriority()
    {
        return 1;
    }
}
