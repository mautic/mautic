<?php

namespace  MauticPlugin\MauticCrmBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class PipedriveDeletion
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $objectType;

    /**
     * @var int
     */
    private $integrationEntityId;

    /**
     * @var int
     */
    private $deletedDate;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);
        $builder->setTable('plugin_crm_pipedrive_deletions');

        $builder->addId();
        $builder->addNamedField('objectType', 'string', 'object_type');
        $builder->addNamedField('integrationEntityId', 'integer', 'integration_entity_id');
        $builder->addNamedField('deletedDate', 'datetime', 'deleted_date');

        $builder->addIndex(['deleted_date'], 'deleted_date');
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getObjectType()
    {
        return $this->objectType;
    }

    /**
     * @param string $objectType
     *
     * @return self
     */
    public function setObjectType($objectType)
    {
        $this->objectType = $objectType;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDeletedDate()
    {
        $date = new DateTime();
        return $date->setTimestamp($this->deletedDate);
    }

    /**
     * @return self
     */
    public function setDeletedDate(DateTime $deletedDate)
    {
        $this->deletedDate = $deletedDate->getTimestamp();

        return $this;
    }

    /**
     * @return int
     */
    public function getIntegrationEntityId()
    {
        return $this->integrationEntityId;
    }

    /**
     * @param int $integrationEntityId
     *
     * @return self
     */
    public function setIntegrationEntityId($integrationEntityId)
    {
        $this->integrationEntityId = $integrationEntityId;

        return $this;
    }
}
