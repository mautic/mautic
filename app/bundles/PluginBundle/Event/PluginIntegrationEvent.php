<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

/**
 * Class PluginIntegrationEvent.
 */
class PluginIntegrationEvent extends AbstractPluginIntegrationEvent
{
    public function __construct(UnifiedIntegrationInterface $integration)
    {
        $this->integration = $integration;
    }

    /**
     * @return Integration
     */
    public function getEntity()
    {
        return $this->integration->getIntegrationSettings();
    }

    public function setEntity(Integration $integration)
    {
        $this->integration->setIntegrationSettings($integration);
    }
}
