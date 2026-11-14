<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use Mautic\SmsBundle\Form\Type\TwilioFeatureSettingsType;
use Mautic\SmsBundle\Form\Type\TwilioKeysType;
use Mautic\SmsBundle\Integration\TwilioIntegration;

final class ConfigSupport extends TwilioIntegration implements ConfigFormInterface, ConfigFormAuthInterface, ConfigFormFeatureSettingsInterface
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
        return TwilioKeysType::class;
    }

    public function getFeatureSettingsConfigFormName(): string
    {
        return TwilioFeatureSettingsType::class;
    }
}
