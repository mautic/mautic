<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChatBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Chat
 * @ORM\Table(name="chats")
 * @ORM\Entity(repositoryClass="Mautic\ChatBundle\Entity\ChatRepository")
 * @Serializer\ExclusionPolicy("all")
 */

class Chat
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="from_user", referencedColumnName="id")
     */
    private $fromUser;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="to_user", referencedColumnName="id", nullable=true)
     */
    private $toUser;

    /**
     * @ORM\ManyToOne(targetEntity="Channel")
     * @ORM\JoinColumn(name="channel_id", referencedColumnName="id", nullable=true)
     */
    private $channel;

    /**
     * @ORM\Column(type="text")
     */
    private $message;

    /**
     * @ORM\Column(name="is_read", type="boolean")
     */
    private $isRead = false;

    /**
     * @ORM\Column(name="is_dismissed", type="boolean")
     */
    private $isDismissed = false;

    /**
     * @ORM\Column(name="is_edited", type="boolean")
     */
    private $isEdited = false;

    /**
     * @ORM\Column(name="date_sent", type="datetime", nullable=true)
     */
    private $dateSent;

    /**
     * @ORM\Column(name="date_read", type="datetime", nullable=true)
     */
    private $dateRead;

    /**
     * @ORM\Column(name="date_edited", type="datetime", nullable=true)
     */
    private $dateEdited;


    /**
     * Construct
     */
    public function __construct()
    {
        $this->readBy = new ArrayCollection();
    }

    /**
     * @return mixed
     */
    public function getDateEdited ()
    {
        return $this->dateEdited;
    }

    /**
     * @param mixed $dateEdited
     */
    public function setDateEdited ($dateEdited)
    {
        $this->dateEdited = $dateEdited;
    }

    /**
     * @return mixed
     */
    public function getDateRead ()
    {
        return $this->dateRead;
    }

    /**
     * @param mixed $dateRead
     */
    public function setDateRead ($dateRead)
    {
        $this->dateRead = $dateRead;
    }

    /**
     * @return mixed
     */
    public function getDateSent ()
    {
        return $this->dateSent;
    }

    /**
     * @param mixed $dateSent
     */
    public function setDateSent ($dateSent)
    {
        $this->dateSent = $dateSent;
    }

    /**
     * @return mixed
     */
    public function getFromUser ()
    {
        return $this->fromUser;
    }

    /**
     * @param mixed $fromUser
     */
    public function setFromUser ($fromUser)
    {
        $this->fromUser = $fromUser;
    }

    /**
     * @return mixed
     */
    public function getIsEdited ()
    {
        return $this->isEdited;
    }

    /**
     * @param mixed $isEdited
     */
    public function setIsEdited ($isEdited)
    {
        $this->isEdited = $isEdited;
    }

    /**
     * @return mixed
     */
    public function isEdited()
    {
        return $this->getIsEdited();
    }

    /**
     * @return mixed
     */
    public function getIsRead ()
    {
        return $this->isRead;
    }

    /**
     * @param mixed $isRead
     */
    public function setIsRead ($isRead)
    {
        $this->isRead = $isRead;
    }

    /**
     * @return mixed
     */
    public function isRead()
    {
        return $this->getIsRead();
    }

    /**
     * @param mixed $isDismissed
     */
    public function setIsDismissed ($isDismissed)
    {
        $this->isDismissed = $isDismissed;
    }

    /**
     * @return mixed
     */
    public function isDismissed()
    {
        return $this->getIsDismissed();
    }

    /**
     * @return mixed
     */
    public function getIsDismissed ()
    {
        return $this->isDismissed;
    }

    /**
     * @return mixed
     */
    public function getToUser ()
    {
        return $this->toUser;
    }

    /**
     * @param mixed $toUser
     */
    public function setToUser ($toUser)
    {
        $this->toUser = $toUser;
    }

    /**
     * @return mixed
     */
    public function getId ()
    {
        return $this->id;
    }

    /**
     * @return mixed
     */
    public function getChannel ()
    {
        return $this->channel;
    }

    /**
     * @param mixed $channel
     */
    public function setChannel ($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @param \Mautic\UserBundle\Entity\User $user
     *
     * @return $this
     */
    public function addReadBy(\Mautic\UserBundle\Entity\User $user)
    {
        $this->readBy[] = $user;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getReadBy()
    {
        return $this->readBy;
    }

    /**
     * @return mixed
     */
    public function getMessage ()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage ($message)
    {
        $this->message = $message;
    }
}



