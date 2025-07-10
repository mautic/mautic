<?php

namespace Mautic\PluginBundle\Event;

use Mautic\PluginBundle\Integration\UnifiedIntegrationInterface;
use Psr\Http\Message\ResponseInterface;

class PluginIntegrationRequestEvent extends AbstractPluginIntegrationEvent
{
    private ?ResponseInterface $response = null;

    /**
     * @param mixed[] $parameters
     * @param string  $method
     * @param mixed[] $settings
     * @param string  $authType
     */
    public function __construct(
        UnifiedIntegrationInterface $integration,
        private $url,
        private $parameters,
        private $headers,
        private $method,
        private $settings,
        private $authType,
    ) {
        $this->integration = $integration;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @return string
     */
    public function getAuthType()
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

    /**
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }
}
