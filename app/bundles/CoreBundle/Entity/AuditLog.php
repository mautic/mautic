<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

class AuditLog
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var int
     */
    protected $userId;

    /**
     * @var string
     */
    protected $userName;

    /**
     * @var string
     */
    protected $bundle;

    /**
     * @var string
     */
    protected $object;

    /**
     * @var string
     */
    protected $objectId;

    /**
     * @var string
     */
    protected $action;

    /**
     * @var array
     */
    protected $details = [];

    /**
     * @var \DateTimeInterface
     */
    protected $dateAdded;

    /**
     * @var string
     */
    protected $ipAddress;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('audit_log')
            ->setCustomRepositoryClass(AuditLogRepository::class)
            ->addIndex(['object', 'object_id'], 'object_search')
            ->addIndex(['bundle', 'object', 'action', 'object_id'], 'timeline_search')
            ->addIndex(['date_added'], 'date_added_index');

        $builder->addBigIntIdField();

        $builder->createField('userId', 'integer')
            ->columnName('user_id')
            ->build();

        $builder->createField('userName', 'string')
            ->columnName('user_name')
            ->build();

        $builder->createField('bundle', 'string')
            ->length(50)
            ->build();

        $builder->createField('object', 'string')
            ->length(50)
            ->build();

        $builder->addBigIntIdField('objectId', 'object_id', false);

        $builder->createField('action', 'string')
            ->length(50)
            ->build();

        $builder->createField('details', 'array')
            ->nullable()
            ->build();

        $builder->addDateAdded();

        $builder->createField('ipAddress', 'string')
            ->columnName('ip_address')
            ->length(45)
            ->build();
    }

    /**
     * Get id.
     */
    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     */
    public function setUserId($userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int|null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * Set object.
     *
     * @param string $object
     */
    public function setObject($object): static
    {
        $this->object = $object;

        return $this;
    }

    /**
     * Get object.
     *
     * @return string|null
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set objectId.
     *
     * @param int $objectId
     */
    public function setObjectId($objectId): static
    {
        $this->objectId = (string) $objectId;

        return $this;
    }

    /**
     * Get objectId.
     */
    public function getObjectId(): int
    {
        return (int) $this->objectId;
    }

    /**
     * Set action.
     *
     * @param string $action
     */
    public function setAction($action): static
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Get action.
     *
     * @return string|null
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Set details.
     */
    public function setDetails(array $details): static
    {
        $this->details = $details;

        return $this;
    }

    /**
     * Get details.
     *
     * @return array
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Set dateAdded.
     *
     * @param \DateTime $dateAdded
     */
    public function setDateAdded($dateAdded): static
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded.
     *
     * @return \DateTimeInterface|null
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set ipAddress.
     *
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress): static
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * Get ipAddress.
     *
     * @return string|null
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    /**
     * Set bundle.
     *
     * @param string $bundle
     */
    public function setBundle($bundle): static
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Get bundle.
     *
     * @return string|null
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Set userName.
     *
     * @param string $userName
     */
    public function setUserName($userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    /**
     * Get userName.
     *
     * @return string|null
     */
    public function getUserName()
    {
        return $this->userName;
    }
}
