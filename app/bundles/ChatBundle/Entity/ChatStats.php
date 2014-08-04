<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ChatBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Entity\FormEntity;

/**
 * Class ChatStats
 * @ORM\Table(name="chat_stats")
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class ChatStats
{

    /**
     * @ORM\Column(name="is_read", type="boolean")
     */
    protected $isRead = true;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Chat", inversedBy="readBy")
     * @ORM\JoinColumn(name="chat_id", referencedColumnName="id", nullable=false)
     */
    protected $chat;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Mautic\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @return mixed
     */
    public function getChat ()
    {
        return $this->chat;
    }

    /**
     * @param mixed $chat
     */
    public function setChat ($chat)
    {
        $this->chat = $chat;
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
    public function getUser ()
    {
        return $this->user;
    }

    /**
     * @param mixed $user
     */
    public function setUser ($user)
    {
        $this->user = $user;
    }
}



