<?php

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Exception\InvalidDecodedStringException;

class ClickthroughHelper
{
    /**
     * Encode an array to append to a URL.
     */
    public static function encodeArrayForUrl(array $array): string
    {
        return urlencode(base64_encode(serialize($array)));
    }

    /**
     * Decode a string appended to URL into an array.
     *
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

        try {
            $result = Serializer::decode($decoded);

            if (!is_array($result)) {
                throw new InvalidDecodedStringException($decoded);
            }
        } catch (\Throwable $e) {
            if (!$e instanceof InvalidDecodedStringException) {
                throw new InvalidDecodedStringException($decoded, 0, $e);
            }

            throw $e;
        }

        return $result;
    }
}
