<?php

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity]
class IdEntry
{
    /**
     * @var string
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

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
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

    public function getExpiryTime(): \DateTime
    {
        $dt = new \DateTime();
        $dt->setTimestamp($this->expiryTimestamp);

        return $dt;
    }

    /**
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
     * @param string $id
     *
     * @return IdEntry
     */
    public function setId($id)
    {
        $this->id =  $id;

        return $this;
    }
}
