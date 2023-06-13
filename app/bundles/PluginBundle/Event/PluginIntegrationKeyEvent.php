<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

/**
 * Class PluginIntegrationKeyEvent.
 */
class PluginIntegrationKeyEvent extends AbstractPluginIntegrationEvent
{
    public function __construct(UnifiedIntegrationInterface $integration, private array $keys = null)
    {
        $this->integration = $integration;
    }

    /**
     * Get the keys array.
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Set new keys array.
     */
    public function setKeys(array $keys)
    {
        $this->keys = $keys;
    }
}
