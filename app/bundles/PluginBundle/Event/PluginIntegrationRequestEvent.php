<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;
use Psr\Http\Message\ResponseInterface;

class PluginIntegrationRequestEvent extends AbstractPluginIntegrationEvent
{
    private ?ResponseInterface $response = null;

    public function __construct(
        UnifiedIntegrationInterface $integration,
        private readonly string $url,
        private array $parameters,
        private array $headers,
        private readonly string $method,
        private readonly array $settings,
        private readonly string $authType,
    ) {
        $this->integration = $integration;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function getAuthType(): string
    {
        return $this->authType;
    }

    public function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
}
