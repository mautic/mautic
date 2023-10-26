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
     * @param string $integration
     * @param string $integrationObjectName
     * @param mixed  $integrationObjectId
     */
    public function __construct(
        $integration,
        $integrationObjectName,
        $integrationObjectId,
        \DateTimeInterface $objectModifiedDate
    ) {
        $this->integration           = $integration;
        $this->integrationObjectName = $integrationObjectName;
        $this->integrationObjectId   = $integrationObjectId;
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
}
