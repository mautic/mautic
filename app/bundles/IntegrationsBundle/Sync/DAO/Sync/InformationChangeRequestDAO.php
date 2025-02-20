<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync;

use Mautic\IntegrationsBundle\Sync\DAO\Value\NormalizedValueDAO;

class InformationChangeRequestDAO
{
    private ?\DateTimeInterface $possibleChangeDateTime = null;

    private ?\DateTimeInterface $certainChangeDateTime = null;

    /**
     * @param string $integration
     * @param string $objectName
     * @param mixed  $objectId
     * @param string $field
     */
    public function __construct(
        private $integration,
        private $objectName,
        private $objectId,
        private $field,
        private NormalizedValueDAO $newValue,
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
