<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\UserBundle\Entity\User;

/**
 * Class Notification.
 */
class Notification
{
    /** @var int */
    protected $id;

    /**
     * @var \Mautic\UserBundle\Entity\User
     */
    protected $user;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var string
     */
    protected $header;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var \DateTiem
     */
    protected $dateAdded;

    /**
     * @var string
     */
    protected $iconClass;

    /**
     * @var bool
     */
    protected $isRead = false;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('notifications')
            ->setCustomRepositoryClass('Mautic\CoreBundle\Entity\NotificationRepository')
            ->addIndex(['is_read'], 'notification_read_status')
            ->addIndex(['type'], 'notification_type')
            ->addIndex(['is_read', 'user_id'], 'notification_user_read_status');

        $builder->addId();

        $builder->createManyToOne('user', 'Mautic\UserBundle\Entity\User')
            ->addJoinColumn('user_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->createField('type', 'string')
            ->nullable()
            ->length(25)
            ->build();

        $builder->createField('header', 'string')
            ->nullable()
            ->build();

        $builder->addField('message', 'text');

        $builder->addDateAdded();

        $builder->createField('iconClass', 'string')
            ->columnName('icon_class')
            ->nullable()
            ->build();

        $builder->createField('isRead', 'boolean')
            ->columnName('is_read')
            ->build();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message)
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;
    }

    /**
     * @return mixed
     */
    public function getIconClass()
    {
        return $this->iconClass;
    }

    /**
     * @param mixed $iconClass
     */
    public function setIconClass($iconClass)
    {
        $this->iconClass = $iconClass;
    }

    /**
     * @return mixed
     */
    public function getIsRead()
    {
        return $this->isRead;
    }

    /**
     * @param mixed $isRead
     */
    public function setIsRead($isRead)
    {
        $this->isRead = (bool) $isRead;
    }

    /**
     * @return mixed
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param mixed $header
     */
    public function setHeader($header)
    {
        $this->header = $header;
    }
}
