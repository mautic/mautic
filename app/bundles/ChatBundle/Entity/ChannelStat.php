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

/**
 * Class ChannelStat
 * @ORM\Table(name="channel_stats")
 * @ORM\Entity()
 * @Serializer\ExclusionPolicy("all")
 */
class ChannelStat
{

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Channel")
     * @ORM\JoinColumn(name="channel_id", referencedColumnName="id", nullable=false)
     */
    protected $channel;

    /**
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Mautic\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @ORM\Column(name="last_read", type="integer")
     */
    protected $lastRead;

    /**
     * @ORM\Column(name="date_read", type="datetime")
     */
    protected $dateRead;

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
     * @return mixed
     */
    public function getLastRead ()
    {
        return $this->lastRead;
    }

    /**
     * @param mixed $lastRead
     */
    public function setLastRead ($lastRead)
    {
        $this->lastRead = $lastRead;
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
}