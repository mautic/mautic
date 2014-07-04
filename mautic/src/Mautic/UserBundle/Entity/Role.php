<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation as Serializer;

/**
 * Class Role
 * @ORM\Table(name="roles")
 * @ORM\Entity(repositoryClass="Mautic\UserBundle\Entity\RoleRepository")
 * @Serializer\ExclusionPolicy("all")
 */


class Role extends FormEntity
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full", "limited"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full", "limited"})
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full", "limited"})
     */
    private $description;

    /**
     * @ORM\Column(name="is_admin", type="boolean")
     * @Serializer\Expose
     * @Serializer\Since("1.0")
     * @Serializer\Groups({"full"})
     */
    private $isAdmin = false;

    /**
     * @ORM\OneToMany(targetEntity="Permission", mappedBy="role", cascade={"persist","remove"}, orphanRemoval=true, fetch="EXTRA_LAZY")
     */
    private $permissions;

    /**
     * @ORM\Column(name="readable_permissions", type="array")
     */
    private $rawPermissions;

    /**
     * @ORM\OneToMany(targetEntity="User", mappedBy="role", fetch="EXTRA_LAZY")
     */
    private $users;

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(
            array('message' => 'mautic.user.role.name.notblank')
        ));
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Role
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add permissions
     *
     * @param \Mautic\UserBundle\Entity\Permission $permissions
     * @return Role
     */
    public function addPermission(\Mautic\UserBundle\Entity\Permission $permissions)
    {
        $permissions->setRole($this);

        $this->permissions[] = $permissions;

        return $this;
    }

    /**
     * Remove permissions
     *
     * @param \Mautic\UserBundle\Entity\Permission $permissions
     */
    public function removePermission(\Mautic\UserBundle\Entity\Permission $permissions)
    {
        $this->permissions->removeElement($permissions);
    }

    /**
     * Get permissions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Role
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set isAdmin
     *
     * @param boolean $isAdmin
     * @return Role
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isChanged('isAdmin', $isAdmin);
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * Get isAdmin
     *
     * @return boolean
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * @return bool
     */
    public function isAdmin() {
        return $this->getIsAdmin();
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissions = new ArrayCollection();
    }

    /**
     * Simply used to store a readable format of permissions for the changelog
     *
     * @param array $permissions
     */
    public function setRawPermissions(array $permissions)
    {
        $this->isChanged('rawPermissions', $permissions);
        $this->rawPermissions = $permissions;
    }

    /**
     * Get rawPermissions
     *
     * @return array
     */
    public function getRawPermissions()
    {
        return $this->rawPermissions;
    }

    /**
     * Add users
     *
     * @param \Mautic\UserBundle\Entity\User $users
     * @return Role
     */
    public function addUser(\Mautic\UserBundle\Entity\User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param \Mautic\UserBundle\Entity\User $users
     */
    public function removeUser(\Mautic\UserBundle\Entity\User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }
}
