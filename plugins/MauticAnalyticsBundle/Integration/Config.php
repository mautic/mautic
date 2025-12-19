<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAnalyticsBundle\Integration;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;

class Config
{
    public function __construct(private IntegrationsHelper $integrationsHelper)
    {
    }

    public function isPublished(): bool
    {
        try {
            $integration = $this->getIntegrationEntity();

            return (bool) $integration->getIsPublished() ?: false;
        } catch (IntegrationNotFoundException) {
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
        } catch (IntegrationNotFoundException) {
            return [];
        }
    }

    public function isAutoAssetTrackingEnabled(): bool
    {
        $settings = $this->getFeatureSettings();

        return !empty($settings['integration']['auto_asset_tracking']);
    }

    public function isAutoVideoTrackingEnabled(): bool
    {
        $settings = $this->getFeatureSettings();

        return !empty($settings['integration']['auto_video_tracking']);
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getIntegrationEntity(): Integration
    {
        $integrationObject = $this->integrationsHelper->getIntegration(MauticAnalyticsBundleIntegration::NAME);

        return $integrationObject->getIntegrationConfiguration();
    }
}
