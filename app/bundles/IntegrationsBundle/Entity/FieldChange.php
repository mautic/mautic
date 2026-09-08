<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FieldChangeRepository::class)]
#[ORM\Table(name: 'sync_object_field_change_report')]
#[ORM\Index(columns: ['object_type', 'object_id', 'column_name'], name: 'object_composite_key')]
#[ORM\Index(columns: ['integration', 'object_type', 'object_id', 'column_name'], name: 'integration_object_composite_key')]
#[ORM\Index(columns: ['integration', 'object_type', 'modified_at'], name: 'integration_object_type_modification_composite_key')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class FieldChange
{
    /**
     * @var int
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(type: Types::STRING, length: 191)]
    private $integration;

    /**
     * @var int|string
     */
    #[ORM\Column(name: 'object_id', type: 'bigint', options: ['unsigned' => true])]
    private $objectId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'object_type', type: Types::STRING, length: 191)]
    private $objectType;

    /**
     * @var \DateTimeInterface
     */
    #[ORM\Column(name: 'modified_at', type: Types::DATETIME_MUTABLE)]
    private $modifiedAt;

    /**
     * @var string
     */
    #[ORM\Column(name: 'column_name', type: Types::STRING, length: 191)]
    private $columnName;

    /**
     * @var string
     */
    #[ORM\Column(name: 'column_type', type: Types::STRING, length: 191)]
    private $columnType;

    /**
     * @var string
     */
    #[ORM\Column(name: 'column_value', type: Types::TEXT)]
    private $columnValue;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    public function getIntegration(): string
    {
        return $this->integration;
    }

    /**
     * @param string $integration
     */
    public function setIntegration($integration): static
    {
        $this->integration = $integration;

        return $this;
    }

    public function setObjectId(int $id): self
    {
        $this->objectId = (string) $id;

        return $this;
    }

    public function getObjectId(): int
    {
        return (int) $this->objectId;
    }

    public function setObjectType(string $type): self
    {
        $this->objectType = $type;

        return $this;
    }

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function setModifiedAt(\DateTime $time): self
    {
        $this->modifiedAt = $time;

        return $this;
    }

    public function getModifiedAt(): \DateTimeInterface
    {
        return $this->modifiedAt;
    }

    public function setColumnName(string $name): self
    {
        $this->columnName = $name;

        return $this;
    }

    public function getColumnName(): string
    {
        return $this->columnName;
    }

    public function setColumnType(string $type): self
    {
        $this->columnType = $type;

        return $this;
    }

    public function getColumnType(): string
    {
        return $this->columnType;
    }

    public function setColumnValue(string $value): self
    {
        $this->columnValue = $value;

        return $this;
    }

    public function getColumnValue(): string
    {
        return $this->columnValue;
    }
}
