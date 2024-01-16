<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\ValueNormalizer;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

interface ValueNormalizerInterface
{
    public function normalizeForMautic(string $value, $type): NormalizedValueDAO;

    public function normalizeForIntegration(NormalizedValueDAO $value);
}
