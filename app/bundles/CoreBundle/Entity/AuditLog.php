<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(columns: ['object', 'object_id'], name: 'object_search')]
#[ORM\Index(columns: ['bundle', 'object', 'action', 'object_id'], name: 'timeline_search')]
#[ORM\Index(columns: ['date_added'], name: 'date_added_index')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class AuditLog
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'user_id', type: 'integer')]
    protected $userId;

    /**
     * @var string
     */
    #[ORM\Column(name: 'user_name', type: 'string', length: 191)]
    protected $userName;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 50)]
    protected $bundle;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 50)]
    protected $object;

    /**
     * @var string
     */
    protected $objectId;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 50)]
    protected $action;

    /**
     * @var array
     */
    #[ORM\Column(type: 'array', nullable: true)]
    protected $details = [];

    /**
     * @var \DateTimeInterface
     */
    protected $dateAdded;

    /**
     * @var string
     */
    #[ORM\Column(name: 'ip_address', type: 'string', length: 45)]
    protected $ipAddress;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->addBigIntIdField();

        $builder->addBigIntIdField('objectId', 'object_id', false);

        $builder->addDateAdded();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param string $object
     */
    public function setObject($object): static
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * @param int $objectId
     */
    public function setObjectId($objectId): static
    {
        $this->objectId = (string) $objectId;

        return $this;
    }

    public function getObjectId(): int
    {
        return (int) $this->objectId;
    }

    /**
     * @param string $action
     */
    public function setAction($action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAction()
    {
        return $this->action;
    }

    public function setDetails(array $details): static
    {
        $this->details = $details;

        return $this;
    }

    /**
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * @param \DateTime $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * @param string $bundle
     */
    public function setBundle($bundle): static
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * @param string $userName
     */
    public function setUserName($userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserName()
    {
        return $this->userName;
    }
}
