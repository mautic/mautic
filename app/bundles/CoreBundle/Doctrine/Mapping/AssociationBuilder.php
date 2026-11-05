<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\Mapping;

/**
 * Adds Mautic-specific association options on top of Doctrine's builder:
 * allowing a many-to-one to be the primary key.
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
}
