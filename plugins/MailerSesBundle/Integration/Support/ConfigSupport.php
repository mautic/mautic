<?php

declare(strict_types=1);

namespace MauticPlugin\MailerSesBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MailerSesBundle\Integration\MailerSesIntegration;

class ConfigSupport extends MailerSesIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
