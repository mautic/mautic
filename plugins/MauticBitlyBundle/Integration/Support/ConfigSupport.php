<?php

declare(strict_types=1);

namespace MauticPlugin\MauticBitlyBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticBitlyBundle\Form\Type\ConfigAuthType;
use MauticPlugin\MauticBitlyBundle\Integration\BitlyBundleIntegration;

class ConfigSupport extends BitlyBundleIntegration implements ConfigFormInterface, ConfigFormAuthInterface
{
    use DefaultConfigFormTrait;

    public function getAuthConfigFormName(): string
    {
        return ConfigAuthType::class;
    }
}
