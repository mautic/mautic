<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\Interfaces\BuilderInterface;
use MauticPlugin\GrapesJsBuilderBundle\Integration\GrapesJsBuilderIntegration;

class BuilderSupport extends GrapesJsBuilderIntegration implements BuilderInterface
{
    private $featuresSupported = ['email', 'page'];

    public function isSupported(string $featureName): bool
    {
        return in_array($featureName, $this->featuresSupported);
    }
}
