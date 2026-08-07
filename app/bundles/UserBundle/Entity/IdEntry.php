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
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->entityId;
    }

    /**
     * @param string $entityId
     */
    public function setEntityId($entityId): static
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

    public function setExpiryTime(\DateTime $expiryTime): static
    {
        $this->expiryTimestamp = $expiryTime->getTimestamp();

        return $this;
    }

    /**
     * @return string|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id): static
    {
        $this->id =  $id;

        return $this;
    }
}
