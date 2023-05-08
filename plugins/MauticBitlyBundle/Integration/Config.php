<?php

declare(strict_types=1);

namespace MauticPlugin\MauticBitlyBundle\Integration;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticBitlyBundle\Form\Type\ConfigAuthType;

class Config
{
    private \Mautic\IntegrationsBundle\Helper\IntegrationsHelper $integrationsHelper;

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
     * @throws IntegrationNotFoundException
     */
    public function getIntegrationEntity(): Integration
    {
        $integrationObject = $this->integrationsHelper->getIntegration(BitlyBundleIntegration::NAME);

        return $integrationObject->getIntegrationConfiguration();
    }

    public function isConfigured(): bool
    {
        $apiKeys = $this->getApiKeys();

        return !empty($apiKeys[ConfigAuthType::ACCESS_TOKEN]);
    }

    public function getAcccessToken(): string
    {
        return $this->getApiKeys()[ConfigAuthType::ACCESS_TOKEN] ?? '';
    }

    /**
     * @return string[]
     */
    private function getApiKeys(): array
    {
        try {
            $integration = $this->getIntegrationEntity();

            return $integration->getApiKeys() ?: [];
        } catch (IntegrationNotFoundException $e) {
            return [];
        }
    }
}
