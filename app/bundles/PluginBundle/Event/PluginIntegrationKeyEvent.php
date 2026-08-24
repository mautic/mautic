<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

class PluginIntegrationKeyEvent extends AbstractPluginIntegrationEvent
{
    public function __construct(
        UnifiedIntegrationInterface $integration,
        private ?array $keys = null,
    ) {
        $this->integration = $integration;
    }

    /**
     * Get the keys array.
     *
     * @return array<string, mixed>|null
     */
    public function getKeys(): ?array
    {
        return $this->keys;
    }

    /**
     * @param array<string, mixed> $keys
     */
    public function setKeys(array $keys): void
    {
        $this->keys = $keys;
    }
}
