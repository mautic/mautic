<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper;

/**
 * Class InputHelper
 *
 * @package Mautic\CoreBundle\Helper
 */
class InputHelper
{

    /**
     * Wrapper function to clean inputs.  $mask can be an array of keys as the field names and values as the cleaning
     * function to be used for the specific field.
     *
     * @param mixed $value
     * @param mixed $mask
     * @return mixed
     */
    static function _($value, $mask = 'clean') {
        if (is_array($value) && is_array($mask)) {
            foreach ($value as $k => &$v) {
                if (array_key_exists($k, $mask) && method_exists('Mautic\CoreBundle\Helper\InputHelper', $mask[$k])) {
                    $v = self::$mask[$k]($v);
                } else {
                    $v = self::clean($v);
                }
            }
            return $value;
        } elseif (is_string($mask) && method_exists('Mautic\CoreBundle\Helper\InputHelper', $mask)) {
            return self::$mask($value);
        } else {
            return self::clean($value);
        }
    }


    /**
     * Cleans value by HTML-escaping '"<>& and characters with ASCII value less than 32
     *
     * @param $value
     * @return string
     */
    static public function clean($value)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                $v = self::clean($v);
            }
            return $value;
        } else {
            return filter_var($value, FILTER_SANITIZE_SPECIAL_CHARS);
        }
    }

    /**
     * Strips tags
     *
     * @param $value
     * @return mixed
     */
    static public function string($value)
    {
        return filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
    }

    /**
     * Strips non-alphanumeric characters
     *
     * @param $value
     * @return string
     */
    static public function alphanum($value)
    {
        return trim(preg_replace("/[^0-9a-z]+/i", "", $value));
    }

    /**
     * Returns raw value
     *
     * @param $value
     * @return mixed
     */
    static public function raw($value)
    {
        return $value;
    }

    /**
     * Returns float value
     *
     * @param $value
     * @return float
     */
    static public function float($value)
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND);
    }

    /**
     * Returns int value
     *
     * @param $value
     * @return int
     */
    static public function int($value)
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Returns boolean value
     *
     * @param $value
     * @return bool
     */
    static public function boolean($value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Removes all characters except those allowed in URLs
     *
     * @param $value
     * @param $allowedProtocols
     * @return mixed
     */
    static public function url($value, $allowedProtocols = null, $defaultProtocol = null, $removeQuery = array())
    {

        if (empty($allowedProtocols)) {
            $allowedProtocols = array('https', 'http', 'ftp');
        }
        if (empty($defaultProtocol)) {
            $defaultProtocol = 'http';
        }

        $value = filter_var($value, FILTER_SANITIZE_URL);
        $parts = parse_url($value);

        if ($parts) {
            if (isset($parts['scheme'])) {
                if (!in_array($parts['scheme'], $allowedProtocols)) {
                    $parts['scheme'] = $defaultProtocol;
                }
            } else {
                $parts['scheme'] = $defaultProtocol;
            }

            if (!empty($removeQuery) && !empty($parts['query'])) {
                parse_str($parts['query'], $query);
                foreach ($removeQuery as $q) {
                    if (isset($query[$q]))
                        unset($query[$q]);
                }
                $parts['query'] = http_build_query($query);
            }

            $value =
                (!empty($parts["scheme"])   ? $parts["scheme"]."://" :"") .
                (!empty($parts["user"])     ? $parts["user"].":"     :"") .
                (!empty($parts["pass"])     ? $parts["pass"]."@"     :"") .
                (!empty($parts["host"])     ? $parts["host"]         :"") .
                (!empty($parts["port"])     ? ":".$parts["port"]     :"") .
                (!empty($parts["path"])     ? $parts["path"]         :"") .
                (!empty($parts["query"])    ? "?".$parts["query"]    :"") .
                (!empty($parts["fragment"]) ? "#".$parts["fragment"] :"");
        } else {
            //must have a really bad URL since parse_url returned false so let's just clean it
            $value = self::clean($value);
        }

        //since a URL allows <>, let's add a safety step to remove <script> tags
        $value = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $value);

        return $value;
    }

    /**
     * Removes all characters except those allowed in emails
     *
     * @param $value
     * @return mixed
     */

    static public function email($value)
    {
        $value = substr($value, 0, 254);
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * Returns a clean array
     *
     * @param $value
     * @return array|string
     */
    static public function cleanArray($value)
    {
        $value = self::clean($value);
        if (!is_array($value)) {
            $value = array($value);
        }
        return $value;
    }
}