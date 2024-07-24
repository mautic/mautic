<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Entity;

/**
 * Entities implementing this interface can set for specific use cases that they do not want to
 * set dateModified and modifiedBy[User] properties on safe.
 */
interface SkipModifiedInterface
{
    public function shouldSkipSettingModifiedProperties(): bool;
}
