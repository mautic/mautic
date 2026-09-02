<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticTagManagerBundle\Integration\TagManagerIntegration;

final class ConfigSupport extends TagManagerIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
