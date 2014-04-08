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
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\Role\RoleInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Class Role
 * @ORM\Table(name="roles")
 * @ORM\Entity(repositoryClass="Mautic\UserBundle\Entity\RoleRepository")
 */
class Role implements RoleInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $description;

    /**
     * @ORM\Column(name="is_admin", type="boolean")
     */
    protected $isAdmin = false;

    /**
     * @ORM\Column(name="date_added", type="datetime")
     */
    protected $dateAdded;

    /**
     * @ORM\OneToMany(targetEntity="Permission", mappedBy="role", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    protected $permissions;

    /**
     * @ORM\OneToMany(targetEntity="User", mappedBy="role")
     */
    protected $users;

    /**
     * @param ClassMetadata $metadata
     */
    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(
            array('message' => 'mautic.user.role.name.notblank')
        ));
    }

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    /**
     * @param $value
     * @return string
     */
    public function cleanData($value) {
        return trim(strip_tags($value));
    }

    /**
     * @see RoleInterface
     */
    public function getRole()
    {
        return $this->role;
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
        $this->name = $this->cleanData($name);

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
        $this->description = $this->cleanData($description);

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
     * Set dateAdded
     *
     * @param \DateTime $dateAdded
     * @return Role
     */
    public function setDateAdded($dateAdded)
    {
        $this->dateAdded = $dateAdded;

        return $this;
    }

    /**
     * Get dateAdded
     *
     * @return \DateTime
     */
    public function getDateAdded()
    {
        return $this->dateAdded;
    }

    /**
     * Set isAdmin
     *
     * @param boolean $isAdmin
     * @return Role
     */
    public function setIsAdmin($isAdmin)
    {
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
}
