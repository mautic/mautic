<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Mapping;

/**
 * Adds Mautic-specific association options on top of Doctrine's builder:
 * allowing a many-to-one to be the primary key, and marking an association
 * as the ownership parent.
 */
final class AssociationBuilder extends \Doctrine\ORM\Mapping\Builder\AssociationBuilder
{
    /**
     * Allow a many-to-one to be the ID.
     */
    public function isPrimaryKey(): static
    {
        $this->mapping['id'] = true;

        return $this;
    }

    /**
     * Mark this association as the parent for ownership/permission checks.
     * Used by entities that implement getPermissionUser() to delegate to a parent entity.
     */
    public function isOwnershipParent(): static
    {
        $this->mapping['isOwnershipParent'] = true;

        return $this;
    }
}
