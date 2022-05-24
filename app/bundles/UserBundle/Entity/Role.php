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
     * @var string
     */
    private $description;

    /**
     * @var bool
     */
    private $isAdmin = false;

    /**
     * @var ArrayCollection
     */
    private $permissions;

    /**
     * @var array
     */
    private $rawPermissions;

    /**
     * @var ArrayCollection
     */
    private $users;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->users       = new ArrayCollection();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('roles')
            ->setCustomRepositoryClass('Mautic\UserBundle\Entity\RoleRepository');

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

    public static function loadValidatorMetadata(ClassMetadata $metadata)
    {
        $metadata->addPropertyConstraint('name', new Assert\NotBlank(
            ['message' => 'mautic.core.name.required']
        ));
    }

    /**
     * Prepares the metadata for API usage.
     *
     * @param $metadata
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata)
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
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Role
     */
    public function addPermission(Permission $permissions)
    {
        $permissions->setRole($this);

        $this->permissions[] = $permissions;

        return $this;
    }

    public function removePermission(Permission $permissions)
    {
        $this->permissions->removeElement($permissions);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPermissions()
    {
        return $this->permissions;
    }

    /**
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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
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
     * @return bool
     */
    public function getIsAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->getIsAdmin();
    }

    /**
     * Simply used to store a readable format of permissions for the changelog.
     */
    public function setRawPermissions(array $permissions)
    {
        $this->isChanged('rawPermissions', $permissions);
        $this->rawPermissions = $permissions;
    }

    /**
     * @return array
     */
    public function getRawPermissions()
    {
        return $this->rawPermissions;
    }

    /**
     * @return Role
     */
    public function addUser(User $users)
    {
        $this->users[] = $users;

        return $this;
    }

    public function removeUser(User $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    public function getNameAndId(): string
    {
        return $this->getName().' ('.$this->getId().')';
    }
}
