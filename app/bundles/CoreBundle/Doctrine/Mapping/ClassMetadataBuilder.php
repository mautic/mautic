<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Class ClassMetadataBuilder.
 *
 * Override Doctrine's builder classes to add support to orphanRemoval until the fix is incorporated into Doctrine release
 * See @link https://github.com/doctrine/doctrine2/pull/1326/
 */
class ClassMetadataBuilder extends \Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder
{
    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $cm
     */
    public function __construct(ClassMetadataInfo $cm)
    {
        parent::__construct($cm);

        // Default all Mautic entities to explicit
        $this->setChangeTrackingPolicyDeferredExplicit();
    }

    /**
     * Creates a ManyToOne Association Builder.
     *
     * Note: This method does not add the association, you have to call build() on the AssociationBuilder.
     *
     * @param string $name
     * @param string $targetEntity
     *
     * @return AssociationBuilder
     */
    public function createManyToOne($name, $targetEntity)
    {
        return new AssociationBuilder(
            $this,
            [
                'fieldName'    => $name,
                'targetEntity' => $targetEntity,
            ],
            ClassMetadata::MANY_TO_ONE
        );
    }

    /**
     * Creates a OneToOne Association Builder.
     *
     * @param string $name
     * @param string $targetEntity
     *
     * @return AssociationBuilder
     */
    public function createOneToOne($name, $targetEntity)
    {
        return new AssociationBuilder(
            $this,
            [
                'fieldName'    => $name,
                'targetEntity' => $targetEntity,
            ],
            ClassMetadata::ONE_TO_ONE
        );
    }

    /**
     * Creates a ManyToMany Association Builder.
     *
     * @param string $name
     * @param string $targetEntity
     *
     * @return ManyToManyAssociationBuilder
     */
    public function createManyToMany($name, $targetEntity)
    {
        return new ManyToManyAssociationBuilder(
            $this,
            [
                'fieldName'    => $name,
                'targetEntity' => $targetEntity,
            ],
            ClassMetadata::MANY_TO_MANY
        );
    }

    /**
     * Creates a one to many association builder.
     *
     * @param string $name
     * @param string $targetEntity
     *
     * @return OneToManyAssociationBuilder
     */
    public function createOneToMany($name, $targetEntity)
    {
        return new OneToManyAssociationBuilder(
            $this,
            [
                'fieldName'    => $name,
                'targetEntity' => $targetEntity,
            ],
            ClassMetadata::ONE_TO_MANY
        );
    }

    /**
     * Add Id column.
     */
    public function addId()
    {
        $this->createField('id', 'integer')
            ->isPrimaryKey()
            ->generatedValue()
            ->build();
    }

    /**
     * Add id, name, and description columns.
     *
     * @param string $nameColumn
     * @param string $descriptionColumn
     */
    public function addIdColumns($nameColumn = 'name', $descriptionColumn = 'description')
    {
        $this->addId();

        if ($nameColumn) {
            $this->createField($nameColumn, 'string')
                ->build();
        }

        if ($descriptionColumn) {
            $this->createField($descriptionColumn, 'text')
                ->nullable()
                ->build();
        }
    }

    /**
     * Add category to metadata.
     */
    public function addCategory()
    {
        $this->createManyToOne('category', 'Mautic\CategoryBundle\Entity\Category')
            ->addJoinColumn('category_id', 'id', true, false, 'SET NULL')
            ->build();
    }

    /**
     * Add publish up and down dates to metadata.
     */
    public function addPublishDates()
    {
        $this->createField('publishUp', 'datetime')
            ->columnName('publish_up')
            ->nullable()
            ->build();

        $this->createField('publishDown', 'datetime')
            ->columnName('publish_down')
            ->nullable()
            ->build();
    }

    /**
     * Added dateAdded column.
     *
     * @param bool|false $nullable
     */
    public function addDateAdded($nullable = false)
    {
        $dateAdded = $this->createField('dateAdded', 'datetime')
            ->columnName('date_added');

        if ($nullable) {
            $dateAdded->nullable();
        }

        $dateAdded->build();
    }

    /**
     * Add a lead column.
     *
     * @param bool|false $nullable
     * @param string     $onDelete
     * @param bool|false $isPrimaryKey
     * @param null       $inversedBy
     */
    public function addLead($nullable = false, $onDelete = 'CASCADE', $isPrimaryKey = false, $inversedBy = null)
    {
        $lead = $this->createManyToOne('lead', 'Mautic\LeadBundle\Entity\Lead');

        if ($isPrimaryKey) {
            $lead->isPrimaryKey();
        }

        if ($inversedBy) {
            $lead->inversedBy($inversedBy);
        }

        $lead
            ->addJoinColumn('lead_id', 'id', $nullable, false, $onDelete)
            ->build();
    }

    /**
     * Adds IP address.
     *
     * @param bool|false $nullable
     */
    public function addIpAddress($nullable = false)
    {
        $this->createManyToOne('ipAddress', 'Mautic\CoreBundle\Entity\IpAddress')
            ->cascadePersist()
            ->cascadeMerge()
            ->addJoinColumn('ip_id', 'id', $nullable)
            ->build();
    }

    /**
     * Add a nullable field.
     *
     * @param        $name
     * @param string $type
     * @param null   $columnName
     */
    public function addNullableField($name, $type = 'string', $columnName = null)
    {
        $field = $this->createField($name, $type)
            ->nullable();

        if ($columnName !== null) {
            $field->columnName($columnName);
        }

        $field->build();
    }

    /**
     * Add a field with a custom column name.
     *
     * @param            $name
     * @param            $type
     * @param            $columnName
     * @param bool|false $nullable
     */
    public function addNamedField($name, $type, $columnName, $nullable = false)
    {
        $field = $this->createField($name, $type)
            ->columnName($columnName);

        if ($nullable) {
            $field->nullable();
        }

        $field->build();
    }

    /**
     * Add partial index.
     *
     * @param array $columns
     * @param       $name
     * @param       $where
     *
     * @return $this
     */
    public function addPartialIndex(array $columns, $name, $where)
    {
        $cm = $this->getClassMetadata();

        if (!isset($cm->table['indexes'])) {
            $cm->table['indexes'] = [];
        }

        $cm->table['indexes'][$name] = ['
            columns'  => $columns,
            'options' => [
                'where' => $where,
            ],
        ];

        return $this;
    }
}
