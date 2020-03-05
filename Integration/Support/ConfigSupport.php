<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Integration\Support;

use MauticPlugin\GrapesJsBuilderBundle\Integration\GrapesJsBuilderIntegration;
use MauticPlugin\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;

class ConfigSupport extends GrapesJsBuilderIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
