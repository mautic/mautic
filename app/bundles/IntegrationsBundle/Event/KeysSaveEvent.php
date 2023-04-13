<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\EventDispatcher\Event;

class KeysSaveEvent extends Event
{
    private Integration $integrationConfiguration;

    /**
     * @var array<mixed>
     */
    private array $oldKeys;

    /**
     * @var array<mixed>
     */
    private array $newKeys;

    /**
     * @param array<mixed> $keys
     */
    public function __construct(Integration $integrationConfiguration, array $keys)
    {
        $this->integrationConfiguration = $integrationConfiguration;
        $this->oldKeys                  = $keys;
        $this->newKeys                  = $integrationConfiguration->getApiKeys();
    }

    public function getIntegrationConfiguration(): Integration
    {
        return $this->integrationConfiguration;
    }

    /**
     * @return array<mixed>
     */
    public function getOldKeys(): array
    {
        return $this->oldKeys;
    }

    /**
     * @return array<mixed>
     */
    public function getNewKeys(): array
    {
        return $this->newKeys;
    }
}
