<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BC\BcIntegrationSettingsTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;

abstract class BasicIntegration implements IntegrationInterface
{
    use BcIntegrationSettingsTrait;
    use ConfigurationTrait;

    public function getDisplayName(): string
    {
        return $this->getName();
    }
}
