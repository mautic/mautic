<?php

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * @ORM\Entity()
 */
class IdEntry
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $entityId;

    /**
     * @var int
     */
    protected $expiryTimestamp;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('saml_id_entry');

        $builder->createField('id', 'string')
             ->makePrimaryKey()
             ->generatedValue('NONE')
             ->build();

        $builder->createField('entityId', 'string')
            ->columnName('entity_id')
            ->makePrimaryKey()
            ->generatedValue('NONE')
            ->build();

        $builder->createField('expiryTimestamp', 'integer')
            ->build();
    }

    /**
     * @return string
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param string $entityId
     *
     * @return IdEntry
     */
    public function setEntityId($entityId)
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpiryTime()
    {
        $dt = new \DateTime();
        $dt->setTimestamp($this->expiryTimestamp);

        return $dt;
    }

    /**
     * @param \DateTime $expiryTime
     *
     * @return IdEntry
     */
    public function setExpiryTime(\DateTime $expiryTime)
    {
        $this->expiryTimestamp = $expiryTime->getTimestamp();

        return $this;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return IdEntry
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }
}
