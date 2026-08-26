<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

final class InformationChangeRequestDAO
{
    private ?\DateTimeInterface $possibleChangeDateTime = null;

    private ?\DateTimeInterface $certainChangeDateTime = null;

    /**
     * @param mixed $objectId
     */
    public function __construct(
        private readonly string $integration,
        private readonly string $objectName,
        private mixed $objectId,
        private readonly string $field,
        private readonly NormalizedValueDAO $newValue,
    ) {
    }

    public function getIntegration(): string
    {
        return $this->integration;
    }

    /**
     * @return mixed
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    public function getObject(): string
    {
        return $this->objectName;
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getNewValue(): NormalizedValueDAO
    {
        return $this->newValue;
    }

    public function getPossibleChangeDateTime(): ?\DateTimeInterface
    {
        return $this->possibleChangeDateTime;
    }

    public function setPossibleChangeDateTime(?\DateTimeInterface $possibleChangeDateTime = null): self
    {
        $this->possibleChangeDateTime = $possibleChangeDateTime;

        return $this;
    }

    public function getCertainChangeDateTime(): ?\DateTimeInterface
    {
        return $this->certainChangeDateTime;
    }

    public function setCertainChangeDateTime(?\DateTimeInterface $certainChangeDateTime = null): self
    {
        $this->certainChangeDateTime = $certainChangeDateTime;

        return $this;
    }
}
