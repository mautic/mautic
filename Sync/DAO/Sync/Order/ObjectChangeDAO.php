<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Order;

use MauticPlugin\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO as ReportFieldDAO;

/**
 * Class ObjectChangeDAO
 */
class ObjectChangeDAO
{
    /**
     * @var string
     */
    private $integration;

    /**
     * @var string
     */
    private $object;

    /**
     * @var mixed
     */
    private $objectId;

    /**
     * @var string
     */
    private $mappedObject;

    /**
     * @var mixed
     */
    private $mappedId;

    /**
     * @var \DateTimeInterface
     */
    private $changeDateTime;

    /**
     * @var FieldDAO[]
     */
    private $fields = [];

    /**
     * @var FieldDAO[]
     */
    private $fieldsByState = [
        ReportFieldDAO::FIELD_CHANGED => [],
        ReportFieldDAO::FIELD_UNCHANGED => [],
        ReportFieldDAO::FIELD_REQUIRED => [],
    ];

    /**
     * ObjectChangeDAO constructor.
     *
     * @param string $integration
     * @param string $object
     * @param mixed  $objectId
     * @param mixed  $mappedId     ID of the source object
     * @param string $mappedObject Name of the source object type
     */
    public function __construct($integration, $object, $objectId, $mappedObject, $mappedId)
    {
        $this->integration  = $integration;
        $this->object       = $object;
        $this->objectId     = $objectId;
        $this->mappedId     = $mappedId;
        $this->mappedObject = $mappedObject;
    }

    /**
     * @return string
     */
    public function getIntegration(): string
    {
        return $this->integration;
    }

    /**
     * @param FieldDAO $fieldDAO
     * @param string   $state
     *
     * @return ObjectChangeDAO
     */
    public function addField(FieldDAO $fieldDAO, string $state = ReportFieldDAO::FIELD_CHANGED): ObjectChangeDAO
    {
        $this->fields[$fieldDAO->getName()]                = $fieldDAO;
        $this->fieldsByState[$state][$fieldDAO->getName()] = $fieldDAO;

        return $this;
    }

    /**
     * @return string
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param mixed $objectId
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;
    }

    /**
     * @return int
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Returns the name/type for the object in this system that is being synced to the other
     *
     * @return string
     */
    public function getMappedObject()
    {
        return $this->mappedObject;
    }

    /**
     * Returns the ID for the object in this system that is being synced to the other
     *
     * @return mixed|null
     */
    public function getMappedObjectId()
    {
        return $this->mappedId;
    }

    /**
     * @param string $name
     *
     * @return FieldDAO
     */
    public function getField($name)
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        return null;
    }

    /**
     * @return FieldDAO[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return FieldDAO[]
     */
    public function getChangedFields(): array
    {
        return $this->fieldsByState[ReportFieldDAO::FIELD_CHANGED];
    }

    /**
     * @return FieldDAO[]
     */
    public function getUnchangedFields(): array
    {
        return $this->fieldsByState[ReportFieldDAO::FIELD_UNCHANGED];
    }

    /**
     * @return FieldDAO[]
     */
    public function getRequiredFields(): array
    {
        return $this->fieldsByState[ReportFieldDAO::FIELD_REQUIRED];
    }

    /**
     * @return bool
     */
    public function shouldSync(): bool
    {
        return !empty(count($this->fields));
    }

    /**
     * @return \DateTimeInterface
     */
    public function getChangeDateTime(): \DateTimeInterface
    {
        return $this->changeDateTime;
    }

    /**
     * @param \DateTimeInterface $changeDateTime
     *
     * @return ObjectChangeDAO
     */
    public function setChangeDateTime(\DateTimeInterface $changeDateTime = null)
    {
        if (null === $changeDateTime) {
            $changeDateTime = new \DateTime();
        }

        $this->changeDateTime = $changeDateTime;

        return $this;
    }
}
