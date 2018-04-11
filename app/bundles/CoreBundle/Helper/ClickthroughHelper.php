<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

class ClickthroughHelper
{
    /**
     * Encode an array to append to a URL.
     *
     * @param array $array
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
     * @return mixed
     */
    public static function decodeArrayFromUrl($string, $urlDecode = true)
    {
        $raw     = $urlDecode ? urldecode($string) : $string;
        $decoded = base64_decode($raw);

        if (strpos(strtolower($decoded), 'a') !== 0) {
            throw new \InvalidArgumentException(sprintf('The string %s is not a serialized array.', $decoded));
        }

        return unserialize($decoded);
    }
}
