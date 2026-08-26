<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

final class PluginIntegrationEvent extends AbstractPluginIntegrationEvent
{
    public function __construct(UnifiedIntegrationInterface $integration)
    {
        $this->integration = $integration;
    }

    public function getEntity(): ?Integration
    {
        return $this->integration->getIntegrationSettings();
    }

    public function setEntity(Integration $integration): void
    {
        $this->integration->setIntegrationSettings($integration);
    }
}
