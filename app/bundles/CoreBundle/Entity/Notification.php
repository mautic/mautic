<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\UserBundle\Entity\User;

/**
 * Class Notification
 *
 * @ORM\Table(name="notifications")
 * @ORM\Entity(repositoryClass="Mautic\CoreBundle\Entity\NotificationRepository")
 */
class Notification
{

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Mautic\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @ORM\Column(type="string", length=25, nullable=true)
     */
    protected $type;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $header;

    /**
     * @ORM\Column(type="text")
     */
    protected $message;

    /**
     * @ORM\Column(name="date_added", type="datetime")
     */
    protected $dateAdded;

    /**
     * @ORM\Column(name="icon_class", type="string", nullable=true)
     */
    protected $iconClass;

    /**
     * @ORM\Column(name="is_read", type="boolean")
     * */
    protected $isRead = false;

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
    public function getUser ()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser (User $user)
    {
        $this->user = $user;
    }

    /**
     * @return mixed
     */
    public function getType ()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType ($type)
    {
        $this->type = $type;
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

    /**
     * @return mixed
     */
    public function getDateAdded ()
    {
        return $this->dateAdded;
    }

    /**
     * @param mixed $dateAdded
     */
    public function setDateAdded ($dateAdded)
    {
        $this->dateAdded = $dateAdded;
    }

    /**
     * @return mixed
     */
    public function getIconClass ()
    {
        return $this->iconClass;
    }

    /**
     * @param mixed $iconClass
     */
    public function setIconClass ($iconClass)
    {
        $this->iconClass = $iconClass;
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
        $this->isRead = (bool) $isRead;
    }

    /**
     * @return mixed
     */
    public function getHeader ()
    {
        return $this->header;
    }

    /**
     * @param mixed $header
     */
    public function setHeader ($header)
    {
        $this->header = $header;
    }
}