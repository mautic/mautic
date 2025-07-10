<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\PluginBundle\Entity\Integration;
use Symfony\Contracts\EventDispatcher\Event;

class KeysDecryptionEvent extends Event
{
    public function __construct(
        private Integration $integrationConfiguration,
        private array $keys,
    ) {
    }

    public function getIntegrationConfiguration(): Integration
    {
        return $this->integrationConfiguration;
    }

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function setKeys(array $keys): void
    {
        $this->keys = $keys;
    }
}
