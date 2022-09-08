<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\PluginBundle\Entity\Integration;

class ConfigSaveEvent extends \Symfony\Contracts\EventDispatcher\Event
{
    /**
     * @var Integration
     */
    private $integrationConfiguration;

    public function __construct(Integration $integrationConfiguration)
    {
        $this->integrationConfiguration = $integrationConfiguration;
    }

    public function getIntegrationConfiguration(): Integration
    {
        return $this->integrationConfiguration;
    }

    public function getIntegration(): string
    {
        return $this->integrationConfiguration->getName();
    }
}
