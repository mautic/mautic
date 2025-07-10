<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Report;

class RelationDAO
{
    private ?int $relObjectInternalId = null;

    public function __construct(
        private readonly string $objectName,
        private readonly string $relFieldName,
        private readonly string $relObjectName,
        private readonly string $objectIntegrationId,
        private readonly string $relObjectIntegrationId,
    ) {
    }

    public function getObjectName(): string
    {
        return $this->objectName;
    }

    public function getRelObjectName(): string
    {
        return $this->relObjectName;
    }

    public function getRelFieldName(): string
    {
        return $this->relFieldName;
    }

    public function getObjectIntegrationId(): string
    {
        return $this->objectIntegrationId;
    }

    public function getRelObjectIntegrationId(): string
    {
        return $this->relObjectIntegrationId;
    }

    public function getRelObjectInternalId(): ?int
    {
        return $this->relObjectInternalId;
    }

    public function setRelObjectInternalId(int $relObjectInternalId): void
    {
        $this->relObjectInternalId = $relObjectInternalId;
    }
}
