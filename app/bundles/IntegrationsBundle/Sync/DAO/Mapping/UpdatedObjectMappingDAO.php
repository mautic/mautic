<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Mapping;

class UpdatedObjectMappingDAO
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var string
     */
    private $integrationObjectName;

    /**
     * @var mixed
     */
    private $integrationObjectId;

    /**
     * @var \DateTime
     */
    private $objectModifiedDate;

    /**
     * @var mixed
     */
    private $internalObjectId;

    /**
     * @param string $integration
     * @param string $integrationObjectName
     * @param mixed  $integrationObjectId
     * @param mixed  $internalObjectId
     */
    public function __construct(
        $integration,
        $integrationObjectName,
        $integrationObjectId,
        \DateTimeInterface $objectModifiedDate,
        $internalObjectId
    ) {
        $this->integration           = $integration;
        $this->integrationObjectName = $integrationObjectName;
        $this->integrationObjectId   = $integrationObjectId;
        $this->objectModifiedDate    = $objectModifiedDate instanceof \DateTimeImmutable ? new \DateTime(
            $objectModifiedDate->format('Y-m-d H:i:s'),
            $objectModifiedDate->getTimezone()
        ) : $objectModifiedDate;
        $this->internalObjectId = $internalObjectId;
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

    /**
     * @return int|null
     */
    public function getInternalObjectId()
    {
        return $this->internalObjectId;
    }
}
