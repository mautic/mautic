<?php

declare(strict_types=1);

namespace Mautic\UserBundle\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Entity\CacheInvalidateInterface;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new GetCollection(security: "is_granted('user:roles:viewown')"),
        new Post(security: "is_granted('user:roles:create')"),
        new Get(security: "is_granted('user:roles:viewown', object)"),
        new Put(security: "is_granted('user:roles:editown', object)"),
        new Patch(security: "is_granted('user:roles:editother', object)"),
        new Delete(security: "is_granted('user:roles:deleteown', object)"),
    ],
    normalizationContext: [
        'groups'                  => ['role:read'],
        'swagger_definition_name' => 'Read',
        'api_included'            => ['permissions'],
    ],
    denormalizationContext: [
        'groups'                  => ['role:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: 'roles')]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Role extends FormEntity implements CacheInvalidateInterface, UuidInterface
{
    use UuidTrait;

    public const CACHE_NAMESPACE = 'Role';

    /**
     * @var int
     */
    #[Groups(['role:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    private $id;

    /**
     * @var string
     */
    #[Groups(['role:read', 'role:write'])]
    #[Assert\NotBlank(message: 'mautic.core.name.required')]
    #[ORM\Column(type: 'string', length: 191)]
    private $name;

    /**
     * @var string|null
     */
    #[Groups(['role:read', 'role:write'])]
    #[ORM\Column(type: 'text', nullable: true)]
    private $description;

    /**
     * @var bool
     */
    #[Groups(['role:read', 'role:write'])]
    #[ORM\Column(name: 'is_admin', type: 'boolean')]
    private $isAdmin = false;

    /**
     * @var ArrayCollection<int, Permission>
     */
    #[Groups(['role:read', 'role:write'])]
    #[ORM\OneToMany(mappedBy: 'role', targetEntity: Permission::class, cascade: ['persist', 'remove'], fetch: 'EXTRA_LAZY', orphanRemoval: true)]
    private \Doctrine\Common\Collections\Collection $permissions;

    #[Groups(['role:read', 'role:write'])]
    #[ORM\Column(name: 'readable_permissions', type: 'array')]
    private ?array $rawPermissions = null;

    /**
     * @var ArrayCollection<int, User>
     */
    #[ORM\OneToMany(mappedBy: 'role', targetEntity: User::class, fetch: 'EXTRA_LAZY')]
    private \Doctrine\Common\Collections\Collection $users;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->users       = new ArrayCollection();
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
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name): static
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add permissions.
     */
    public function addPermission(Permission $permissions): static
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
     * @return ArrayCollection<int, Permission>
     */
    public function getPermissions(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->permissions;
    }

    /**
     * @param string $description
     */
    public function setDescription($description): static
    {
        $this->isChanged('description', $description);
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param bool $isAdmin
     */
    public function setIsAdmin($isAdmin): static
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
     * Get isAdmin.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->isAdmin;
    }

    /**
     * Simply used to store a readable format of permissions for the changelog.
     */
    public function setRawPermissions(array $permissions): void
    {
        $this->isChanged('rawPermissions', $permissions);
        $this->rawPermissions = $permissions;
    }

    public function getRawPermissions(): ?array
    {
        return $this->rawPermissions;
    }

    /**
     * Add users.
     */
    public function addUser(User $users): static
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
     * @return ArrayCollection<int, User>
     */
    public function getUsers(): \Doctrine\Common\Collections\Collection|array
    {
        return $this->users;
    }

    public function getCacheNamespacesToDelete(): array
    {
        return [
            self::CACHE_NAMESPACE,
            User::CACHE_NAMESPACE,
        ];
    }
}
