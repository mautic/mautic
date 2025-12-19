<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAnalyticsBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticAnalyticsBundle\Form\Type\ConfigFeaturesType;
use MauticPlugin\MauticAnalyticsBundle\Integration\MauticAnalyticsBundleIntegration;

class ConfigSupport extends MauticAnalyticsBundleIntegration implements ConfigFormInterface, ConfigFormFeatureSettingsInterface
{
    use DefaultConfigFormTrait;

    public function getFeatureSettingsConfigFormName(): string
    {
        return ConfigFeaturesType::class;
    }
}
