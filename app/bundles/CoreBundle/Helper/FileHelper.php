<?php

namespace Mautic\CoreBundle\Helper;

class FileHelper
{
    public const BYTES_TO_MEGABYTES_RATIO = 1_048_576;

    public static function convertBytesToMegabytes($b): float
    {
        return round($b / self::BYTES_TO_MEGABYTES_RATIO, 2);
    }

    public static function convertMegabytesToBytes($mb)
    {
        return $mb * self::BYTES_TO_MEGABYTES_RATIO;
    }

    public static function getMaxUploadSizeInBytes(): int
    {
        $maxPostSize   = self::convertPHPSizeToBytes(ini_get('post_max_size'));
        $maxUploadSize = self::convertPHPSizeToBytes(ini_get('upload_max_filesize'));
        $memoryLimit   = self::convertPHPSizeToBytes(ini_get('memory_limit'));

        return min($maxPostSize, $maxUploadSize, $memoryLimit);
    }

    public static function getMaxUploadSizeInMegabytes(): float
    {
        $maxUploadSizeInBytes = self::getMaxUploadSizeInBytes();

        return self::convertBytesToMegabytes($maxUploadSizeInBytes);
    }

    /**
     * @param string $sSize
     */
    public static function convertPHPSizeToBytes($sSize): int
    {
        $sSize = trim($sSize);

        if (is_numeric($sSize)) {
            return (int) $sSize;
        }

        $sSuffix = substr($sSize, -1);
        $iValue  = (int) substr($sSize, 0, -1);

        // missing breaks are important
        switch (strtoupper($sSuffix)) {
            case 'P':
                $iValue *= 1024;
                // no break
            case 'T':
                $iValue *= 1024;
                // no break
            case 'G':
                $iValue *= 1024;
                // no break
            case 'M':
                $iValue *= 1024;
                // no break
            case 'K':
                $iValue *= 1024;
                break;
        }

        return $iValue;
    }
}
