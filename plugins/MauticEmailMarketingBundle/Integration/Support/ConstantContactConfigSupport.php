<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticEmailMarketingBundle\Form\Type\ConstantContactKeysType;
use MauticPlugin\MauticEmailMarketingBundle\Integration\ConstantContactIntegration;

final class ConstantContactConfigSupport extends ConstantContactIntegration implements ConfigFormInterface, ConfigFormAuthInterface, ConfigFormFeaturesInterface
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
        return ConstantContactKeysType::class;
    }

    /**
     * @return array<string, string>
     */
    public function getSupportedFeatures(): array
    {
        return [
            ConfigFormFeaturesInterface::FEATURE_PUSH_LEAD => 'mautic.integration.form.feature.push_lead',
        ];
    }
}
