<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

final readonly class FieldMappingDAO
{
    public function __construct(
        private string $internalObject,
        private string $internalField,
        private string $integrationObject,
        private string $integrationField,
        private string $syncDirection,
        private bool $isRequired,
    ) {
    }

    public function getInternalObject(): string
    {
        return $this->internalObject;
    }

    public function getInternalField(): string
    {
        return $this->internalField;
    }

    public function getIntegrationObject(): string
    {
        return $this->integrationObject;
    }

    public function getIntegrationField(): string
    {
        return $this->integrationField;
    }

    public function getSyncDirection(): string
    {
        return $this->syncDirection;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }
}
