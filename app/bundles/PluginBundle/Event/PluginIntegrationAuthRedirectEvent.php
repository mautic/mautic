<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;

class PluginIntegrationAuthRedirectEvent extends AbstractPluginIntegrationEvent
{
    /**
     * @param string $authUrl
     */
    public function __construct(
        UnifiedIntegrationInterface $integration,
        private $authUrl,
    ) {
        $this->integration = $integration;
    }

    /**
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->authUrl;
    }

    /**
     * @param string $authUrl
     */
    public function setAuthUrl($authUrl): void
    {
        $this->authUrl = $authUrl;

        $this->stopPropagation();
    }
}
