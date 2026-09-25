<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration;

use Mautic\PluginBundle\Entity\Integration;

trait ConfigurationTrait
{
    private ?Integration $integration = null;

    public function getIntegrationConfiguration(): Integration
    {
        if (null === $this->integration) {
            throw new \LogicException('Integration configuration has not been set. Guard with hasIntegrationConfiguration() first.');
        }

        return $this->integration;
    }

    public function setIntegrationConfiguration(Integration $integration): void
    {
        $this->integration = $integration;
    }

    /**
     * Check if Integration entity has been set to prevent PHP fatal error with using getIntegrationEntity.
     */
    public function hasIntegrationConfiguration(): bool
    {
        return null !== $this->integration;
    }
}
