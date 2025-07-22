<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;

class UpdatedObjectMappingDAO
{
    private \DateTimeInterface $objectModifiedDate;

    private ?ObjectMapping $objectMapping = null;

    /**
     * @param string $integration
     * @param string $integrationObjectName
     * @param mixed  $integrationObjectId
     */
    public function __construct(
        private $integration,
        private $integrationObjectName,
        private $integrationObjectId,
        \DateTimeInterface $objectModifiedDate,
    ) {
        $this->objectModifiedDate    = $objectModifiedDate instanceof \DateTimeImmutable ? new \DateTime(
            $objectModifiedDate->format('Y-m-d H:i:s'),
            $objectModifiedDate->getTimezone()
        ) : $objectModifiedDate;
    }

    public function getIntegration(): string
    {
        return $this->integration;
    }

    public function getIntegrationObjectName(): string
    {
        return $this->integrationObjectName;
    }

    /**
     * @return mixed
     */
    public function getIntegrationObjectId()
    {
        return $this->integrationObjectId;
    }

    public function getObjectModifiedDate(): \DateTimeInterface
    {
        return $this->objectModifiedDate;
    }

    public function setObjectMapping(ObjectMapping $objectMapping): void
    {
        $this->objectMapping = $objectMapping;
    }

    /**
     * This is set after the ObjectMapping entity has been persisted to the database with the updates from this object.
     */
    public function getObjectMapping(): ?ObjectMapping
    {
        return $this->objectMapping;
    }
}
