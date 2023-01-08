<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\InvalidDecodedStringException;

class ClickthroughHelper
{
    /**
     * Encode an array to append to a URL.
     *
     * @return string
     */
    public static function encodeArrayForUrl(array $array)
    {
        return urlencode(base64_encode(serialize($array)));
    }

    /**
     * Decode a string appended to URL into an array.
     *
     * @param      $string
     * @param bool $urlDecode
     *
     * @return array
     */
    public static function decodeArrayFromUrl($string, $urlDecode = true)
    {
        $raw     = $urlDecode ? urldecode($string) : $string;
        $decoded = base64_decode($raw);

        if (empty($decoded)) {
            return [];
        }

        if (0 !== stripos($decoded, 'a')) {
            throw new InvalidDecodedStringException($decoded);
        }

        return Serializer::decode($decoded);
    }
}
