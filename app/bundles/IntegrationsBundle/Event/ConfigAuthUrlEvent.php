<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\PluginBundle\Entity\Integration;
use Symfony\Contracts\EventDispatcher\Event;

final class ConfigAuthUrlEvent extends Event
{
    public function __construct(private Integration $integrationConfiguration, private string $authUrl)
    {
    }

    public function getIntegrationConfiguration(): Integration
    {
        return $this->integrationConfiguration;
    }

    public function getIntegration(): string
    {
        return $this->integrationConfiguration->getName();
    }

    public function getAuthUrl(): string
    {
        return $this->authUrl;
    }

    public function setAuthUrl(string $authUrl): void
    {
        $this->authUrl = $authUrl;
    }
}
