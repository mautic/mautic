<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Mapping;

interface MappedFieldInfoInterface
{
    public function getName(): string;

    public function getLabel(): string;

    public function showAsRequired(): bool;

    public function hasTooltip(): bool;

    public function getTooltip(): string;

    public function isBidirectionalSyncEnabled(): bool;

    public function isToIntegrationSyncEnabled(): bool;

    public function isToMauticSyncEnabled(): bool;
}
