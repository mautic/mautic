<?php

declare(strict_types=1);

namespace MauticPlugin\MauticTagManagerBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class TagManagerIntegration extends BasicIntegration implements BasicInterface
{
    public const PLUGIN_NAME = 'TagManager';

    public function getName(): string
    {
        return self::PLUGIN_NAME;
    }

    public function getDisplayName(): string
    {
        return 'Tag Manager';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticTagManagerBundle/Assets/img/tagmanager.png';
    }
}
