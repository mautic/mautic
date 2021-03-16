<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Entity;

/**
 * Entities implementing this interface can set for specific use cases that they do not want to
 * set dateModified and modifiedBy[User] properties on safe.
 */
interface SkipModifiedInterface
{
    public function shouldSkipSettingModifiedProperties(): bool;
}
