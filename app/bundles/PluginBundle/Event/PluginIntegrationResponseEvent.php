<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\AbstractIntegration;
use Psr\Http\Message\ResponseInterface;

class PluginIntegrationResponseEvent extends AbstractPluginIntegrationEvent
{
    public function __construct(
        AbstractIntegration $integration,
        private readonly ResponseInterface $response,
    ) {
        $this->integration = $integration;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
