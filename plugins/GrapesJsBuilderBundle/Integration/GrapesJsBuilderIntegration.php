<?php

declare(strict_types=1);

namespace MauticPlugin\GrapesJsBuilderBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class GrapesJsBuilderIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const NAME         = 'grapesjsbuilder';
    public const DISPLAY_NAME = 'GrapesJS';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/GrapesJsBuilderBundle/Assets/img/grapesjsbuilder.png';
    }
}
