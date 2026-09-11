<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity\Attribute;

/**
 * Marks the association that carries ownership for an entity which has no owner
 * or createdBy field of its own.
 *
 * The API's ownership scoping joins this association and filters on the target's
 * owner/createdBy, mirroring what the entity's own getPermissionUser() returns.
 *
 * This used to be recorded in the Doctrine association mapping via
 * ClassMetadataBuilder's isOwnershipParent(). ORM 3 maps associations onto typed
 * AssociationMapping objects that reject unknown keys, so the marker lives on the
 * entity itself now.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class OwnershipParent
{
    /**
     * @param string $association name of the association whose target holds the owner/createdBy
     */
    public function __construct(
        public readonly string $association,
    ) {
    }
}
