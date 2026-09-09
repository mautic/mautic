<?php

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Entity]
#[ORM\Table(name: 'saml_id_entry')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class IdEntry
{
    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 191)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    protected $id;

    /**
     * @var string
     */
    #[ORM\Id]
    #[ORM\Column(name: 'entity_id', type: 'string', length: 191)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    protected $entityId;

    /**
     * @var int
     */
    #[ORM\Column(type: 'integer')]
    protected $expiryTimestamp;

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
