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
     * Strips tags and trims value
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
            return trim(strip_tags($value));
        }
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
}