<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

class PluginIntegrationAuthCallbackUrlEvent extends AbstractPluginIntegrationEvent
{
    /**
     * @param string $callbackUrl
     */
    public function __construct(
        UnifiedIntegrationInterface $integration,
        private $callbackUrl,
    ) {
        $this->integration = $integration;
    }

    /**
     * @return string
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }

    /**
     * @param string $callbackUrl
     */
    public function setCallbackUrl($callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;

        $this->stopPropagation();
    }
}
