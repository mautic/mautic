<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\GrapesJsBuilderBundle\Integration\GrapesJsBuilderIntegration;

final class ConfigSupport extends GrapesJsBuilderIntegration implements ConfigFormInterface
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

    /**
     * Use the default.
     */
    public function getSyncConfigFormName(): ?string
    {
        return null;
    }
}
