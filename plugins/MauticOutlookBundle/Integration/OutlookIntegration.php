<?php

declare(strict_types=1);

namespace MauticPlugin\MauticOutlookBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class OutlookIntegration extends BasicIntegration implements BasicInterface
{
    public function getName(): string
    {
        return 'Outlook';
    }

    public function getDisplayName(): string
    {
        return 'Outlook';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticOutlookBundle/Assets/img/outlook.png';
    }
}
