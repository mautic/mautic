<?php

declare(strict_types=1);

namespace Mautic\ApiBundle\Tests\Extension\Fixture;

use Mautic\CoreBundle\Entity\Attribute\OwnershipParent;

/**
 * Points at a parent that carries no ownership of its own.
 */
#[OwnershipParent('parent')]
class OwnershipParentWithoutOwnership
{
}
