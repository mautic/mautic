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
     * Strips tags and trims value
     *
     * @param $value
     * @return string
     */
    static public function clean($value)
    {
        if (is_array($value)) {
            foreach ($value as &$v) {
                self::clean($v);
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
}