<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration;

use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use Mautic\PluginBundle\Entity\Integration;

abstract class BasicIntegration implements IntegrationInterface
{
    use ConfigurationTrait;

    public function getDisplayName(): string
    {
        return $this->getName();
    }

    public function setIntegrationSettings(Integration $integration): void
    {
        $this->setIntegrationConfiguration($integration);
    }

    public function getIntegrationSettings(): ?Integration
    {
        return $this->hasIntegrationConfiguration() ? $this->getIntegrationConfiguration() : null;
    }

    /**
     * @return string[]
     */
    public function getSupportedFeatures(): array
    {
        return [];
    }

    public function getPriority(): int
    {
        return 1;
    }
}
