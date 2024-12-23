<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

class PluginIntegrationFormDisplayEvent extends AbstractPluginIntegrationEvent
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        UnifiedIntegrationInterface $integration,
        private array $settings,
    ) {
        $this->integration = $integration;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    public function setSettings(array $settings): void
    {
        $this->settings = $settings;

        $this->stopPropagation();
    }
}
