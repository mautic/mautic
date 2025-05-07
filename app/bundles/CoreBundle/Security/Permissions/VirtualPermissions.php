<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Security\Permissions;

/**
 * If a permission implements this interface, a user does not need to have
 * any permissions explicitly assigned to them. The `isVirtuallyGranted()` method is invoked right away.
 */
interface VirtualPermissions
{
    public function isVirtuallyGranted(string $name, string $level): bool;
}
