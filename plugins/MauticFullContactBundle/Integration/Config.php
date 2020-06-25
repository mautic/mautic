<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Integration;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Exception\InvalidValueException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticFullContactBundle\MauticFullContactBundle;

class Config
{
    /**
     * @var IntegrationsHelper
     */
    private $integrationsHelper;

    /**
     * Config constructor.
     */
    public function __construct(IntegrationsHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    public function isPublished(): bool
    {
        try {
            $integration = $this->getIntegrationEntity();

            return (bool) $integration->getIsPublished() ?: false;
        } catch (IntegrationNotFoundException $e) {
            return false;
        }
    }

    public function isConfigured(): bool
    {
        $apiKeys = $this->getApiKeys();

        return !empty($apiKeys['api_key']);
    }

    /**
     * @return string[]
     */
    public function getApiKeys(): array
    {
        try {
            $integration = $this->getIntegrationEntity();

            return $integration->getApiKeys() ?: [];
        } catch (IntegrationNotFoundException $e) {
            return [];
        }
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getIntegrationEntity(): Integration
    {
        $integrationObject = $this->integrationsHelper->getIntegration(FullContactIntegration::NAME);

        return $integrationObject->getIntegrationConfiguration();
    }

    /**
     * @return mixed[]
     */
    public function getFeatureSettings(): array
    {
        try {
            $integration = $this->getIntegrationEntity();

            return $integration->getFeatureSettings() ?: [];
        } catch (IntegrationNotFoundException $e) {
            return [];
        }
    }

    /**
     * @return string[]
     */
    public function getMappedFields($entity): array
    {
        $featureSettings = $this->getFeatureSettings();

        return $featureSettings['sync']['fieldMappings'][$entity] ?? [];
    }
}
