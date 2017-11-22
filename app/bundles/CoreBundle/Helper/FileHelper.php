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

    public static function getMaxUploadSizeInBytes()
    {
        $maxPostSize   = self::convertPHPSizeToBytes(ini_get('post_max_size'));
        $maxUploadSize = self::convertPHPSizeToBytes(ini_get('upload_max_filesize'));
        $memoryLimit   = self::convertPHPSizeToBytes(ini_get('memory_limit'));

        return min($maxPostSize, $maxUploadSize, $memoryLimit);
    }

    public static function getMaxUploadSizeInMegabytes()
    {
        $maxUploadSizeInBytes = self::getMaxUploadSizeInBytes();

        return self::convertBytesToMegabytes($maxUploadSizeInBytes);
    }

    public static function convertPHPSizeToBytes($sSize)
    {
        if (is_numeric($sSize)) {
            return $sSize;
        }
        $sSuffix = substr($sSize, -1);
        $iValue  = substr($sSize, 0, -1);

        //missing breaks are important
        switch (strtoupper($sSuffix)) {
            case 'P':
                $iValue *= 1024;
            case 'T':
                $iValue *= 1024;
            case 'G':
                $iValue *= 1024;
            case 'M':
                $iValue *= 1024;
            case 'K':
                $iValue *= 1024;
                break;
        }

        return $iValue;
    }
}
