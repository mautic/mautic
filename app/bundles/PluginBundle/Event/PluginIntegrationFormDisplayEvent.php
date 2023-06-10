<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

/**
 * Class PluginIntegrationFormDisplayEvent.
 */
class PluginIntegrationFormDisplayEvent extends AbstractPluginIntegrationEvent
{
    public function __construct(UnifiedIntegrationInterface $integration, private array $settings)
    {
        $this->integration = $integration;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    public function setSettings(array $settings)
    {
        $this->settings = $settings;

        $this->stopPropagation();
    }
}
