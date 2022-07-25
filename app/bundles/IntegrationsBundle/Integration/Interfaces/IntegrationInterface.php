<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

interface IntegrationInterface extends UnifiedIntegrationInterface
{
    /**
     * Return the integration's name.
     */
    public function getName(): string;

    public function getDisplayName(): string;

    public function hasIntegrationConfiguration(): bool;

    public function getIntegrationConfiguration(): Integration;

    /**
     * @return mixed
     */
    public function setIntegrationConfiguration(Integration $integration);
}
