<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Entity\FieldPendingSync;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CommonEntity;

/**
 * Class FieldPendingSync.
 */
class FieldPendingSync extends CommonEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $entityId;

    /**
     * @var int
     */
    private $fieldMappingId;

    /**
     * @var int
     */
    private $changeTimestamp;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('field_pending_sync')
            ->setCustomRepositoryClass(FieldPendingSyncRepository::class);

        $builder->addId();

        $builder->createField('entityId', 'integer')
            ->columnName('entity_id')
            ->build();

        $builder->createField('fieldMappingId', 'integer')
            ->columnName('field_mapping_id')
            ->build();

        $builder->createField('changeTimestamp', 'integer')
            ->columnName('change_timestamp')
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
     * @return int
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param int $entityId
     *
     * @return FieldPendingSync
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return int
     */
    public function getFieldMappingId()
    {
        return $this->fieldMappingId;
    }

    /**
     * @param int $fieldMappingId
     *
     * @return FieldPendingSync
     */
    public function setFieldMappingId($fieldMappingId)
    {
        $this->fieldMappingId = $fieldMappingId;

        return $this;
    }

    /**
     * @return int
     */
    public function getChangeTimestamp()
    {
        return $this->changeTimestamp;
    }

    /**
     * @param int $changeTimestamp
     *
     * @return FieldPendingSync
     */
    public function setChangeTimestamp($changeTimestamp)
    {
        $this->changeTimestamp = $changeTimestamp;

        return $this;
    }
}
