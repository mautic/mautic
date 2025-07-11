<?php

namespace Mautic\UserBundle\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\CacheInvalidateInterface;
use Mautic\CoreBundle\Entity\UuidInterface;
use Mautic\CoreBundle\Entity\UuidTrait;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 *   attributes={
 *     "security"="false",
 *     "normalization_context"={
 *       "groups"={
 *         "permission:read"
 *        },
 *       "swagger_definition_name"="Read",
 *     },
 *     "denormalization_context"={
 *       "groups"={
 *         "permission:write"
 *       },
 *       "swagger_definition_name"="Write"
 *     }
 *   }
 * )
 */
class Permission implements CacheInvalidateInterface, UuidInterface
{
    use UuidTrait;

    public const CACHE_NAMESPACE = 'Permission';

    /**
     * @var int
     *
     * @Groups({"permission:read", "role:read"})
     */
    protected $id;

    /**
     * @var string
     *
     * @Groups({"permission:read", "permission:write", "role:read"})
     */
    protected $bundle;

    /**
     * @var string
     *
     * @Groups({"permission:read", "permission:write", "role:read"})
     */
    protected $name;

    /**
     * @var Role
     *
     * @Groups({"permission:read", "permission:write", "role:read"})
     */
    protected $role;

    /**
     * @var int
     *
     * @Groups({"permission:read", "permission:write", "role:read"})
     */
    protected $bitwise;

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('permissions')
            ->setCustomRepositoryClass(PermissionRepository::class)
            ->addUniqueConstraint(['bundle', 'name', 'role_id'], 'unique_perm');

        $builder->addId();

        $builder->createField('bundle', 'string')
            ->length(50)
            ->build();

        $builder->createField('name', 'string')
            ->length(50)
            ->build();

        $builder->createManyToOne('role', 'Role')
            ->inversedBy('permissions')
            ->addJoinColumn('role_id', 'id', false, false, 'CASCADE')
            ->build();

        $builder->addField('bitwise', 'integer');

        static::addUuidField($builder);
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
     * Set bundle.
     *
     * @param string $bundle
     *
     * @return Permission
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Get bundle.
     *
     * @return string
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Set bitwise.
     *
     * @param int $bitwise
     *
     * @return Permission
     */
    public function setBitwise($bitwise)
    {
        $this->bitwise = $bitwise;

        return $this;
    }

    /**
     * Get bitwise.
     *
     * @return int
     */
    public function getBitwise()
    {
        return $this->bitwise;
    }

    /**
     * Set role.
     *
     * @return Permission
     */
    public function setRole(Role $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role.
     *
     * @return Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Permission
     */
    public function setName($name)
    {
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

    public function getCacheNamespacesToDelete(): array
    {
        return [self::CACHE_NAMESPACE];
    }
}
