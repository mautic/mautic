<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Order;

use Mautic\IntegrationsBundle\Entity\ObjectMapping;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\Report\FieldDAO as ReportFieldDAO;

class ObjectChangeDAO
{
    /**
     * @var FieldDAO[]
     */
    private array $fields = [];

    private ?ObjectMapping $objectMapping = null;

    /**
     * @var FieldDAO[]
     */
    private array $fieldsByState = [
        ReportFieldDAO::FIELD_CHANGED   => [],
        ReportFieldDAO::FIELD_UNCHANGED => [],
        ReportFieldDAO::FIELD_REQUIRED  => [],
    ];

    /**
     * @param string             $integration
     * @param string             $object
     * @param mixed              $objectId
     * @param string             $mappedObject   Name of the source object type
     * @param mixed              $mappedId       ID of the source object
     * @param \DateTimeInterface $changeDateTime Date\Time the object was last changed
     */
    public function __construct(
        private $integration,
        private $object,
        private $objectId,
        private $mappedObject,
        private $mappedId,
        private ?\DateTimeInterface $changeDateTime = null,
    ) {
    }

    public function getIntegration(): string
    {
        return $this->integration;
    }

    public function addField(FieldDAO $fieldDAO, string $state = ReportFieldDAO::FIELD_CHANGED): self
    {
        $this->fields[$fieldDAO->getName()]                = $fieldDAO;
        $this->fieldsByState[$state][$fieldDAO->getName()] = $fieldDAO;

        if (ReportFieldDAO::FIELD_REQUIRED === $state) {
            // Make this field also available to the unchanged fields array so the integration can get which
            // ever one it wants based on it's implementation (i.e. patch vs put)
            $this->fieldsByState[ReportFieldDAO::FIELD_UNCHANGED][$fieldDAO->getName()] = $fieldDAO;
        }

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
    public function setObjectId($objectId): void
    {
        $this->objectId = $objectId;
    }

    /**
     * @return mixed
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * Returns the name/type for the object in this system that is being synced to the other.
     *
     * @return string
     */
    public function getMappedObject()
    {
        return $this->mappedObject;
    }

    /**
     * Returns the ID for the object in this system that is being synced to the other.
     *
     * @return mixed
     */
    public function getMappedObjectId()
    {
        return $this->mappedId;
    }

    /**
     * @param string $name
     *
     * @return FieldDAO|null
     */
    public function getField($name)
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * Returns all fields whether changed, unchanged required.
     *
     * @return FieldDAO[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Returns only fields that we assume have been changed/modified.
     *
     * @return FieldDAO[]
     */
    public function getChangedFields(): array
    {
        return $this->fieldsByState[ReportFieldDAO::FIELD_CHANGED];
    }

    /**
     * Returns only fields that are required but were not updated.
     *
     * @return FieldDAO[]
     */
    public function getRequiredFields(): array
    {
        return $this->fieldsByState[ReportFieldDAO::FIELD_REQUIRED];
    }

    /**
     * Returns fields that were mapped that values were known even though the value was not updated. It does include FieldDAO::FIELD_REQUIRED fields.
     *
     * @return FieldDAO[]
     */
    public function getUnchangedFields(): array
    {
        return $this->fieldsByState[ReportFieldDAO::FIELD_UNCHANGED];
    }

    public function shouldSync(): bool
    {
        return !empty(count($this->fields));
    }

    public function getChangeDateTime(): \DateTimeInterface
    {
        return $this->changeDateTime;
    }

    /**
     * @return ObjectChangeDAO
     */
    public function setChangeDateTime(?\DateTimeInterface $changeDateTime = null)
    {
        if (null === $changeDateTime) {
            $changeDateTime = new \DateTime();
        }

        $this->changeDateTime = $changeDateTime;

        return $this;
    }

    public function setObjectMapping(ObjectMapping $objectMapping): void
    {
        $this->objectMapping = $objectMapping;
    }

    /**
     * This is set after the ObjectMapping entity has been persisted to the database with the updates from this object.
     */
    public function getObjectMapping(): ObjectMapping
    {
        return $this->objectMapping;
    }

    public function removeField(string $field): void
    {
        unset($this->fields[$field]);
        unset($this->fieldsByState[ReportFieldDAO::FIELD_CHANGED][$field]);
    }
}
