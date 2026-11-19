<?php

declare(strict_types=1);

namespace MauticPlugin\MauticGmailBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class GmailIntegration extends BasicIntegration implements BasicInterface
{
    public function getName(): string
    {
        return 'Gmail';
    }

    public function getDisplayName(): string
    {
        return 'Gmail';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticGmailBundle/Assets/img/gmail.png';
    }
}
