<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration;

use Mautic\PluginBundle\Entity\Integration;

trait ConfigurationTrait
{
    /**
     * @var Integration
     */
    private $integration;

    public function getIntegrationConfiguration(): Integration
    {
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
        return !empty($this->integration);
    }
}
