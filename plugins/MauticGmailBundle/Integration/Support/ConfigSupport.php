<?php

declare(strict_types=1);

namespace MauticPlugin\MauticGmailBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticGmailBundle\Form\Type\GmailKeysType;
use MauticPlugin\MauticGmailBundle\Integration\GmailIntegration;

final class ConfigSupport extends GmailIntegration implements ConfigFormInterface, ConfigFormAuthInterface
{
    use DefaultConfigFormTrait;

    public function getAuthConfigFormName(): string
    {
        return GmailKeysType::class;
    }
}
