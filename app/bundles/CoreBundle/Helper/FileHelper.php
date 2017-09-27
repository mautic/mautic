<?php

/**
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

class FileHelper
{
    const BYTES_TO_MEGABYTES_RATIO = 1048576;

    public static function convertBytesToMegabytes($b)
    {
        return round($b / self::BYTES_TO_MEGABYTES_RATIO, 2);
    }

    public static function convertMegabytesToBytes($mb)
    {
        return $mb * self::BYTES_TO_MEGABYTES_RATIO;
    }
}
