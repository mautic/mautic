<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\PluginBundle\Entity\Integration;
use Symfony\Contracts\EventDispatcher\Event;

final class KeysSaveEvent extends Event
{
    /**
     * @var array<string,string>
     */
    private array $newKeys;

    /**
     * @param array<string,string> $oldKeys
     */
    public function __construct(private Integration $integrationConfiguration, private array $oldKeys)
    {
        $this->newKeys = $integrationConfiguration->getApiKeys();
    }

    public function getIntegrationConfiguration(): Integration
    {
        return $this->integrationConfiguration;
    }

    /**
     * @return array<string,string>
     */
    public function getOldKeys(): array
    {
        return $this->oldKeys;
    }

    /**
     * @return array<string,string>
     */
    public function getNewKeys(): array
    {
        return $this->newKeys;
    }
}
