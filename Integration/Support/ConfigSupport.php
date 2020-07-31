<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\GrapesJsBuilderBundle\Integration\GrapesJsBuilderIntegration;

class ConfigSupport extends GrapesJsBuilderIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
