<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Extension\Fixture;

use Mautic\CoreBundle\Entity\Attribute\OwnershipParent;

/**
 * Names an association the entity does not have.
 */
#[OwnershipParent('nonexistent')]
class OwnershipParentMissingAssociation
{
}
