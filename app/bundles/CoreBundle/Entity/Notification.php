<?php

namespace Mautic\CoreBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Mautic\UserBundle\Entity\User;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
#[ORM\Table(name: 'notifications')]
#[ORM\Index(columns: ['is_read'], name: 'notification_read_status')]
#[ORM\Index(columns: ['type'], name: 'notification_type')]
#[ORM\Index(columns: ['is_read', 'user_id'], name: 'notification_user_read_status')]
#[ORM\Index(columns: ['deduplicate', 'date_added'], name: 'deduplicate_date_added')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Notification
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    /**
     * @var User|null
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    protected $user;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 25, nullable: true)]
    protected $type;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::STRING, length: 512, nullable: true)]
    protected $header;

    /**
     * @var string|null
     */
    #[ORM\Column(type: Types::TEXT)]
    protected $message;

    /**
     * @var \DateTimeInterface|null
     */
    #[ORM\Column(name: 'date_added', type: 'datetime')]
    protected $dateAdded;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'icon_class', type: Types::STRING, length: 191, nullable: true)]
    protected $iconClass;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_read', type: Types::BOOLEAN)]
    protected $isRead = false;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    protected $deduplicate;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string|null $message
     */
    public function setMessage($message): void
    {
        $this->message = $message;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param \DateTime|null $dateAdded
     */
    public function setDateAdded($dateAdded): void
    {
        $this->dateAdded = $dateAdded;
    }

    /**
     * @return string|null
     */
    public function getIconClass()
    {
        return $this->iconClass;
    }

    /**
     * @param string|null $iconClass
     */
    public function setIconClass($iconClass): void
    {
        $this->iconClass = $iconClass;
    }

    /**
     * @return bool
     */
    public function getIsRead()
    {
        return $this->isRead;
    }

    /**
     * @param bool|null $isRead
     */
    public function setIsRead($isRead): void
    {
        $this->isRead = (bool) $isRead;
    }

    /**
     * @return string|null
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param string|null $header
     */
    public function setHeader($header): void
    {
        $this->header = $header;
    }

    public function getDeduplicate(): ?string
    {
        return $this->deduplicate;
    }

    public function setDeduplicate(?string $deduplicate): void
    {
        $this->deduplicate = $deduplicate;
    }
}
