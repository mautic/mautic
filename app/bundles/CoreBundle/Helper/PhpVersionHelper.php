<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

/**
 * Provides functions to get the PHP version number.
 */
class PhpVersionHelper
{
    /**
     * For example, if the PHP version is 7.2.34-9+0\~20210112.53+debian10\~1.gbpfdd1e6,
     * this function will return 7.2.34. This is the semver MAJOR.MINOR.PATCH format.
     */
    public static function getCurrentSemver(): string
    {
        return PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'.'.PHP_RELEASE_VERSION;
    }
}
