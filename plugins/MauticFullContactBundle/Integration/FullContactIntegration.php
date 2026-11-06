<?php

declare(strict_types=1);

namespace MauticPlugin\MauticFullContactBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class FullContactIntegration extends BasicIntegration implements BasicInterface
{
    public function getName(): string
    {
        return 'FullContact';
    }

    public function getDisplayName(): string
    {
        return 'FullContact';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticFullContactBundle/Assets/img/fullcontact.png';
    }

    public function shouldAutoUpdate(): bool
    {
        $apiKeys = $this->getIntegrationSettings()?->getApiKeys() ?? [];

        return isset($apiKeys['auto_update']) && (bool) $apiKeys['auto_update'];
    }
}
