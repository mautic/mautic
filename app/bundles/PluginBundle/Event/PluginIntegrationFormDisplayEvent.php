<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

class PluginIntegrationFormDisplayEvent extends AbstractPluginIntegrationEvent
{
    /**
     * @var mixed[]
     */
    private array $settings;

    /**
     * @param mixed[] $settings
     */
    public function __construct(UnifiedIntegrationInterface $integration, array $settings)
    {
        $this->integration = $integration;
        $this->settings    = $settings;
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
