<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticAddon\MauticChatBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Channel
 * @ORM\Table(name="chat_channels")
 * @ORM\Entity(repositoryClass="MauticAddon\MauticChatBundle\Entity\ChannelRepository")
 * @Serializer\ExclusionPolicy("all")
 */

class Channel extends FormEntity
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=150, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(name="is_private", type="boolean")
     */
    private $isPrivate = false;

    /**
     * @ORM\ManyToMany(targetEntity="Mautic\UserBundle\Entity\User", fetch="EXTRA_LAZY", indexBy="id")
     * @ORM\JoinTable(name="chat_channel_users")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     */
    protected $privateUsers;

    /**
     * @ORM\OneToMany(targetEntity="ChannelStat", mappedBy="channel", fetch="EXTRA_LAZY")
     */
    protected $stats;

    /**
     * @ORM\OneToMany(targetEntity="Chat", mappedBy="channel", fetch="EXTRA_LAZY")
     */
    protected $chats;

    public function __construct()
    {
        $this->privateUsers = new ArrayCollection();
    }

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new NotBlank(
            array('message' => 'mautic.chat.channel.name.notblank')
        ));

        $metadata->addConstraint(new UniqueEntity(array(
            'fields'  => array('name'),
            'message' => 'mautic.chat.channel.name.unique'
        )));
    }

    /**
     * @return mixed
     */
    public function getDescription ()
    {
        return $this->description;
    }

    /**
     * @param mixed $description
     */
    public function setDescription ($description)
    {
        $this->description = $description;
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
    public function getIsPrivate ()
    {
        return $this->isPrivate;
    }

    /**
     * @param mixed $isPrivate
     */
    public function setIsPrivate ($isPrivate)
    {
        $this->isPrivate = $isPrivate;
    }

    /**
     * @return mixed
     */
    public function getName ()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName ($name)
    {
        $this->name = $name;
    }

    /**
     * Add users
     *
     * @param \Mautic\UserBundle\Entity\User $users
     * @return Channel
     */
    public function addPrivateUser(\Mautic\UserBundle\Entity\User $users)
    {
        $this->privateUsers[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param \Mautic\UserBundle\Entity\User $users
     */
    public function removePrivateUser(\Mautic\UserBundle\Entity\User $users)
    {
        $this->privateUsers->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPrivateUsers()
    {
        return $this->privateUsers;
    }
}



