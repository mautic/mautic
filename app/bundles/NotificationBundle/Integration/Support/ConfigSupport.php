<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\NotificationBundle\Form\Type\OneSignalFeatureSettingsType;
use Mautic\NotificationBundle\Form\Type\OneSignalKeysType;
use Mautic\NotificationBundle\Integration\OneSignalIntegration;

final class ConfigSupport extends OneSignalIntegration implements ConfigFormInterface, ConfigFormAuthInterface, ConfigFormFeaturesInterface, ConfigFormFeatureSettingsInterface
{
    /**
     * Use the default.
     */
    public function getConfigFormName(): ?string
    {
        return null;
    }

    /**
     * Use the default.
     */
    public function getConfigFormContentTemplate(): ?string
    {
        return null;
    }

    public function getAuthConfigFormName(): string
    {
        return OneSignalKeysType::class;
    }

    public function getFeatureSettingsConfigFormName(): string
    {
        return OneSignalFeatureSettingsType::class;
    }

    /**
     * Slug => label map; IntegrationConfigType flips it into the feature checkbox choices.
     *
     * @return array<string, string>
     */
    public function getSupportedFeatures(): array
    {
        $features = [];
        foreach (parent::getSupportedFeatures() as $feature) {
            $features[$feature] = 'mautic.integration.form.feature.'.$feature;
        }

        return $features;
    }
}
