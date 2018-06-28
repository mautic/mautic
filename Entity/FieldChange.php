<?php

namespace MauticPlugin\MauticIntegrationsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class FieldChange
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $objectId;

    /**
     * @var string
     */
    private $objectType;

    /**
     * @var DateTime
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
            ->setTable('object_field_change_report')
            ->setCustomRepositoryClass(FieldChangeRepository::class)
            ->addIndex(['object_id', 'object_type']);

        $builder->addId();
        
        $builder
            ->createField('objectId', 'integer')
            ->columnName('object_id')
            ->build();
        
        $builder
            ->createField('objectType', 'string')
            ->columnName('object_type')
            ->build();
        
        $builder
            ->createField('modifiedAt', 'datetime')
            ->columnName('modified_at')
            ->build();

        $builder
            ->createField('columnName', 'string')
            ->columnName('column_name')
            ->build();

        $builder
            ->createField('columnType', 'string')
            ->columnName('column_type')
            ->build();

        $builder
            ->createField('columnValue', 'string')
            ->columnName('column_value')
            ->build();
    }
}