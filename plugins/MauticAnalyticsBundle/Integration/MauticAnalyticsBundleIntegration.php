<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAnalyticsBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class MauticAnalyticsBundleIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const NAME         = 'mauticanalyticsbundle';
    public const DISPLAY_NAME = 'Mautic Analytics';

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
        return 'plugins/MauticAnalyticsBundle/Assets/img/analytics.png';
    }
}
