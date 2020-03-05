<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Integration;

use MauticPlugin\IntegrationsBundle\Integration\BasicIntegration;
use MauticPlugin\IntegrationsBundle\Integration\ConfigurationTrait;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class GrapesJsBuilderIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const NAME         = 'grapesjsbuilder';
    public const DISPLAY_NAME = 'GrapesJS';

    /**
     * @return string
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return string
     */
    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/GrapesJsBuilderBundle/Assets/img/grapesjsbuilder.png';
    }
}
