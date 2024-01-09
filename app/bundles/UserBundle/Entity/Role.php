<?php

namespace Mautic\UserBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Role extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var bool
     */
    private $isAdmin = false;

    /**
     * @var ArrayCollection<int, \Mautic\UserBundle\Entity\Permission>
     */
    private $permissions;

    /**
     * @var array
     */
    private $rawPermissions;

    /**
     * @var ArrayCollection<int, \Mautic\UserBundle\Entity\User>
     */
    private $users;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->users       = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('roles')
            ->setCustomRepositoryClass(\Mautic\UserBundle\Entity\RoleRepository::class);

        $builder->addIdColumns();

        $builder->createField('isAdmin', 'boolean')
            ->columnName('is_admin')
            ->build();

        $builder->createOneToMany('permissions', 'Permission')
            ->orphanRemoval()
            ->mappedBy('role')
            ->cascadePersist()
            ->cascadeRemove()
            ->fetchExtraLazy()
            ->build();

        $builder->createField('rawPermissions', 'array')
            ->columnName('readable_permissions')
            ->build();

        $builder->createOneToMany('users', 'User')
            ->mappedBy('role')
            ->fetchExtraLazy()
            ->build();
    }

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(
            ['message' => 'mautic.core.name.required']
        ));
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata->setGroupPrefix('role')
            ->addListProperties(
                [
                    'id',
                    'name',
                    'description',
                    'isAdmin',
                    'rawPermissions',
                ]
            )
            ->build();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Role
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add permissions.
     *
     * @return Role
     */
    public function addPermission(Permission $permissions)
    {
        $permissions->setRole($this);

        $this->permissions[] = $permissions;

        return $this;
    }

    /**
     * Remove permissions.
     */
    public function removePermission(Permission $permissions): void
    {
        $this->permissions->removeElement($permissions);
    }

    /**
     * Get permissions.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return Role
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set isAdmin.
     *
     * @param bool $isAdmin
     *
     * @return Role
     */
    public function setIsAdmin($isAdmin)
    {
        $this->isChanged('isAdmin', $isAdmin);
        $this->isAdmin = $isAdmin;

        return $this;
    }

    /**
     * Get isAdmin.
     *
     * @return bool
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * Get isAdmin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->getIsAdmin();
    }

    /**
     * Simply used to store a readable format of permissions for the changelog.
     */
    public function setRawPermissions(array $permissions): void
    {
        $this->isChanged('rawPermissions', $permissions);
        $this->rawPermissions = $permissions;
    }

    /**
     * Get rawPermissions.
     *
     * @return array
     */
    public function getRawPermissions()
    {
        return $this->rawPermissions;
    }

    /**
     * Add users.
     *
     * @return Role
     */
    public function addUser(User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users.
     */
    public function removeUser(User $users): void
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }
}
