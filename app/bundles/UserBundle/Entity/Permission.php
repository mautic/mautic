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
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CacheInvalidateInterface;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Symfony\Component\Serializer\Attribute\Groups;

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
        'groups'                  => ['permission:read'],
        'swagger_definition_name' => 'Read',
    ],
    denormalizationContext: [
        'groups'                  => ['permission:write'],
        'swagger_definition_name' => 'Write',
    ]
)]
#[ORM\Entity(repositoryClass: PermissionRepository::class)]
#[ORM\Table(name: 'permissions')]
#[ORM\UniqueConstraint(name: 'unique_perm', columns: ['bundle', 'name', 'role_id'])]
#[ORM\ChangeTrackingPolicy('DEFERRED_EXPLICIT')]
class Permission implements CacheInvalidateInterface, UuidInterface
{
    use UuidTrait;

    public const CACHE_NAMESPACE = 'Permission';

    /**
     * @var int
     */
    #[Groups(['permission:read', 'role:read'])]
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue]
    protected $id;

    /**
     * @var string
     */
    #[Groups(['permission:read', 'permission:write', 'role:read'])]
    #[ORM\Column(type: 'string', length: 50)]
    protected $bundle;

    /**
     * @var string
     */
    #[Groups(['permission:read', 'permission:write', 'role:read'])]
    #[ORM\Column(type: 'string', length: 50)]
    protected $name;

    /**
     * @var Role
     */
    #[Groups(['permission:read', 'permission:write', 'role:read'])]
    protected $role;

    /**
     * @var int
     */
    #[Groups(['permission:read', 'permission:write', 'role:read'])]
    #[ORM\Column(type: 'integer')]
    protected $bitwise;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->createManyToOne('role', 'Role')
            ->inversedBy('permissions')
            ->addJoinColumn('role_id', 'id', false, false, 'CASCADE')
            ->isOwnershipParent()
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
     * @param string $bundle
     */
    public function setBundle($bundle): static
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * @param int $bitwise
     */
    public function setBitwise($bitwise): static
    {
        $this->bitwise = $bitwise;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getBitwise()
    {
        return $this->bitwise;
    }

    public function setRole(?Role $role = null): static
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return Role|null
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param string $name
     */
    public function setName($name): static
    {
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

    public function getCacheNamespacesToDelete(): array
    {
        return [self::CACHE_NAMESPACE];
    }

    public function getPermissionUser(): mixed
    {
        return $this->role->getCreatedBy();
    }
}
