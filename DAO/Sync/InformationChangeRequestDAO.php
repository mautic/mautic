<?php

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync;

/**
 * Class InformationChangeRequestDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Sync
 */
class InformationChangeRequestDAO
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var string
     */
    private $entity;

    /**
     * @var int
     */
    private $entityId;

    /**
     * @var string
     */
    private $field;

    /**
     * @var mixed
     */
    private $newValue;

    /**
     * @var int|null
     */
    private $possibleChangeTimestamp = null;

    /**
     * @var int|null
     */
    private $certainChangeTimestamp = null;

    /**
     * InformationChangeRequestDAO constructor.
     * @param string $integration
     * @param string $entity
     * @param int    $entityId
     * @param string $field
     * @param mixed  $newValue
     */
    public function __construct($integration, $entity, $entityId, $field, $newValue)
    {
        $this->integration = $integration;
        $this->entity = $entity;
        $this->entityId = $entityId;
        $this->field = $field;
        $this->newValue = $newValue;
    }

    /**
     * @return string
     */
    public function getIntegration()
    {
        return $this->integration;
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return mixed
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * @return int|null
     */
    public function getPossibleChangeTimestamp()
    {
        return $this->possibleChangeTimestamp;
    }

    /**
     * @param int|null $possibleChangeTimestamp
     * @return InformationChangeRequestDAO
     */
    public function setPossibleChangeTimestamp($possibleChangeTimestamp)
    {
        $this->possibleChangeTimestamp = $possibleChangeTimestamp;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getCertainChangeTimestamp()
    {
        return $this->certainChangeTimestamp;
    }

    /**
     * @param int|null $certainChangeTimestamp
     * @return InformationChangeRequestDAO
     */
    public function setCertainChangeTimestamp($certainChangeTimestamp)
    {
        $this->certainChangeTimestamp = $certainChangeTimestamp;
        return $this;
    }
}
