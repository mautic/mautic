<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Entity;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class FieldChange
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $objectId;

    /**
     * @var string
     */
    private $objectType;

    /**
     * @var \DateTime
     */
    private $modifiedAt;

    /**
     * @var string
     */
    private $columnName;

    /**
     * @var string
     */
    private $columnType;

    /**
     * @var string
     */
    private $columnValue;

    /**
     * @param ORM\ClassMetadata $metadata
     *
     * @return void
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder
            ->setTable('sync_object_field_change_report')
            ->setCustomRepositoryClass(FieldChangeRepository::class)
            ->addIndex(['object_type', 'object_id', 'column_name'], 'object_composite_key')
            ->addIndex(['object_type', 'modified_at'], 'object_type_modification_composite_key');

        $builder->addId();

        $builder
            ->createField('objectId', Type::INTEGER)
            ->columnName('object_id')
            ->build();

        $builder
            ->createField('objectType', Type::STRING)
            ->columnName('object_type')
            ->build();

        $builder
            ->createField('modifiedAt', Type::DATETIME)
            ->columnName('modified_at')
            ->build();

        $builder
            ->createField('columnName', Type::STRING)
            ->columnName('column_name')
            ->build();

        $builder
            ->createField('columnType', Type::STRING)
            ->columnName('column_type')
            ->build();

        $builder
            ->createField('columnValue', Type::STRING)
            ->columnName('column_value')
            ->build();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return FieldChange
     */
    public function setObjectId(int $id): FieldChange
    {
        $this->objectId = $id;

        return $this;
    }

    /**
     * @return int
     */
    public function getObjectId(): int
    {
        return $this->objectId;
    }

    /**
     * @param string $type
     *
     * @return FieldChange
     */
    public function setObjectType(string $type): FieldChange
    {
        $this->objectType = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getObjectType(): string
    {
        return $this->objectType;
    }

    /**
     * @param \DateTime $value
     *
     * @return FieldChange
     */
    public function setModifiedAt(\DateTime $time): FieldChange
    {
        $this->modifiedAt = $time;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getModifiedAt(): \DateTime
    {
        return $this->modifiedAt;
    }

    /**
     * @param string $name
     *
     * @return FieldChange
     */
    public function setColumnName(string $name): FieldChange
    {
        $this->columnName = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getColumnName(): string
    {
        return $this->columnName;
    }

    /**
     * @param string $type
     *
     * @return FieldChange
     */
    public function setColumnType(string $type): FieldChange
    {
        $this->columnType = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getColumnType(): string
    {
        return $this->columnType;
    }

    /**
     * @param string $value
     *
     * @return FieldChange
     */
    public function setColumnValue(string $value): FieldChange
    {
        $this->columnValue = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getColumnValue(): string
    {
        return $this->columnValue;
    }
}
