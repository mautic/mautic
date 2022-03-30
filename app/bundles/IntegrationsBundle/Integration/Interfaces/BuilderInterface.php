<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

interface BuilderInterface extends IntegrationInterface
{
    public function isSupported(string $featureName): bool;
}
