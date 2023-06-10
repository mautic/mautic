<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\User;

class Notification
{
    /**
     * @var int|null
     */
    protected $id;

    /**
     * @var User|null
     */
    protected $user;

    /**
     * @var string|null
     */
    protected $type;

    /**
     * @var string|null
     */
    protected $header;

    /**
     * @var string|null
     */
    protected $message;

    /**
     * @var \DateTimeInterface|null
     */
    protected $dateAdded;

    /**
     * @var string|null
     */
    protected $iconClass;

    /**
     * @var bool
     */
    protected $isRead = false;

    /**
     * @var string|null
     */
    protected $deduplicate;

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('notifications')
            ->setCustomRepositoryClass(NotificationRepository::class)
            ->addIndex(['is_read'], 'notification_read_status')
            ->addIndex(['type'], 'notification_type')
            ->addIndex(['is_read', 'user_id'], 'notification_user_read_status')
            ->addIndex(['deduplicate', 'date_added'], 'deduplicate_date_added');

        $builder->addId();

        $builder->createManyToOne('user', User::class)
            ->addJoinColumn('user_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('type', Types::STRING)
            ->nullable()
            ->length(25)
            ->build();

        $builder->createField('header', Types::STRING)
            ->nullable()
            ->length(512)
            ->build();

        $builder->addField('message', Types::TEXT);

        $builder->addDateAdded();

        $builder->createField('iconClass', Types::STRING)
            ->columnName('icon_class')
            ->nullable()
            ->build();

        $builder->createField('isRead', Types::BOOLEAN)
            ->columnName('is_read')
            ->build();

        $builder->createField('deduplicate', 'string')
            ->nullable()
            ->length(32)
            ->build();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type)
    {
        $this->type = $type;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(?string $message)
    {
        $this->message = $message;
    }

    public function getDateAdded(): ?\DateTimeInterface
    {
        return $this->dateAdded;
    }

    public function setDateAdded(?\DateTime $dateAdded)
    {
        $this->dateAdded = $dateAdded;
    }

    public function getIconClass(): ?string
    {
        return $this->iconClass;
    }

    public function setIconClass(?string $iconClass)
    {
        $this->iconClass = $iconClass;
    }

    public function getIsRead(): ?bool
    {
        return $this->isRead;
    }

    public function setIsRead(?bool $isRead)
    {
        $this->isRead = (bool) $isRead;
    }

    public function getHeader(): ?string
    {
        return $this->header;
    }

    public function setHeader(?string $header)
    {
        $this->header = $header;
    }

    public function setDeduplicate(?string $deduplicate): void
    {
        $this->deduplicate = $deduplicate;
    }
}
