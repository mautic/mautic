<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\DTO;

/**
 * This class represents tokens which provide links to objects which have been
 * synced from integrations into Mautic.
 */
class IntegrationObjectToken
{
    /**
     * @var string
     */
    private $objectName;

    /**
     * @var string
     */
    private $integration;

    private string $defaultValue = '';

    /**
     * @var string
     */
    private $linkText;

    /**
     * @var string
     */
    private $baseURL;

    public function __construct(
        private string $token,
    ) {
    }

    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $objectName
     */
    public function setObjectName($objectName): void
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

    /**
     * @param string $integration
     */
    public function setIntegration($integration): void
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

    /**
     * @param string $defaultValue
     */
    public function setDefaultValue($defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }

    /**
     * @param string $linkText
     */
    public function setLinkText($linkText): void
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

    /**
     * @param string $baseURL
     */
    public function setBaseURL($baseURL): void
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
