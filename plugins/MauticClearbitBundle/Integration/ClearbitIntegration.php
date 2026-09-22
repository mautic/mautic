<?php

declare(strict_types=1);

namespace MauticPlugin\MauticClearbitBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class ClearbitIntegration extends BasicIntegration implements BasicInterface
{
    public function getName(): string
    {
        return 'Clearbit';
    }

    public function getDisplayName(): string
    {
        return 'Clearbit';
    }

    public function getIcon(): string
    {
        return 'plugins/MauticClearbitBundle/Assets/img/clearbit.png';
    }

    public function shouldAutoUpdate(): bool
    {
        $apiKeys = $this->getIntegrationSettings()?->getApiKeys() ?? [];

        return isset($apiKeys['auto_update']) && (bool) $apiKeys['auto_update'];
    }
}
