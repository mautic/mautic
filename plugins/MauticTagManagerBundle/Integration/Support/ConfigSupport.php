<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticTagManagerBundle\Integration\TagManagerIntegration;

final class ConfigSupport extends TagManagerIntegration implements ConfigFormInterface
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
}
