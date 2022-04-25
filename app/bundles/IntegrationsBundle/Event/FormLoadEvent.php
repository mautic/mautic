<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Event;

use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\EventDispatcher\Event;

class FormLoadEvent extends Event
{
    /**
     * @var Integration
     */
    private $integrationConfiguration;

    public function __construct(Integration $integration)
    {
        $this->integrationConfiguration = $integration;
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
