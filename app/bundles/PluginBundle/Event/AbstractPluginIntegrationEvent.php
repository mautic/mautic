<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class AbstractPluginIntegrationEvent.
 */
class AbstractPluginIntegrationEvent extends Event
{
    /**
     * @var AbstractIntegration
     */
    protected $integration;

    /**
     * Get the integration's name.
     *
     * @return mixed
     */
    public function getIntegrationName()
    {
        return $this->integration->getName();
    }

    /**
     * Get the integration object.
     *
     * @return AbstractIntegration
     */
    public function getIntegration()
    {
        return $this->integration;
    }
}
