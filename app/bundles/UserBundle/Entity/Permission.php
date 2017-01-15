<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;

/**
 * Class Permission.
 */
class Permission
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $bundle;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var Role
     */
    protected $role;

    /**
     * @var int
     */
    protected $bitwise;

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata(ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('permissions')
            ->setCustomRepositoryClass('Mautic\UserBundle\Entity\PermissionRepository')
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
     * @param Role $role
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
}
