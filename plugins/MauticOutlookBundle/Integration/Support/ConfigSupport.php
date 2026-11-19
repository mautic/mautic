<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOutlookBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticOutlookBundle\Form\Type\OutlookKeysType;
use MauticPlugin\MauticOutlookBundle\Integration\OutlookIntegration;

final class ConfigSupport extends OutlookIntegration implements ConfigFormInterface, ConfigFormAuthInterface
{
    use DefaultConfigFormTrait;

    public function getAuthConfigFormName(): string
    {
        return OutlookKeysType::class;
    }

    public function getConfigFormContentTemplate(): string
    {
        return '@MauticOutlook/Integration/config_form.html.twig';
    }
}
