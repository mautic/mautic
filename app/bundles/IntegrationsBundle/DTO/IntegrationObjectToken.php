<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\DTO;

/**
 * This class represents tokens which provide links to objects which have been
 * synced from integrations into Mautic.
 */
final class IntegrationObjectToken
{
    private ?string $objectName = null;

    private ?string $integration = null;

    private string $defaultValue = '';

    private ?string $linkText = null;

    private ?string $baseURL = null;

    public function __construct(
        private readonly string $token,
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setObjectName(string $objectName): void
    {
        $this->objectName = $objectName;
    }

    /**
     * @return string
     */
    public function getObjectName()
    {
        return $this->objectName;
    }

    public function setIntegration(string $integration): void
    {
        $this->integration = $integration;
    }

    /**
     * @return string
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    public function setDefaultValue(string $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }

    public function setLinkText(string $linkText): void
    {
        $this->linkText = $linkText;
    }

    /**
     * @return string
     */
    public function getLinkText()
    {
        return $this->linkText;
    }

    public function setBaseURL(string $baseURL): void
    {
        $this->baseURL = $baseURL;
    }

    /**
     * @return string
     */
    public function getBaseURL()
    {
        return $this->baseURL;
    }
}
