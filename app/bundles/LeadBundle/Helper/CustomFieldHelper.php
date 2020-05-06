<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

/**
 * Helper class custom field operations.
 */
class CustomFieldHelper
{
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_NUMBER  = 'number';
    const TYPE_SELECT  = 'select';

    /**
     * Fixes value type for specific field types.
     *
     * @param string $type
     * @param mixed  $value
     *
     * @return mixed
     */
    public static function fixValueType($type, $value)
    {
        if (!is_null($value)) {
            switch ($type) {
                case self::TYPE_NUMBER:
                    $value = (float) $value;
                    break;
                case self::TYPE_BOOLEAN:
                    $value = (bool) $value;
                    break;
                case self::TYPE_SELECT:
                    $value = (string) $value;
                    break;
            }
        }

        return $value;
    }

    /**
     * Sort array by key field 
     * 
     * @param array $array 
     * @param string $key 
     * @static
     * @access public
     *
     * @return array
     */
    public static function orderFieldsKey($array, $key)
    {
        $tmp = array();
        foreach($array as $akey => $array2) {
            $tmp[$akey] = $array2[$key];
        }

        asort($tmp , SORT_NUMERIC);

        $tmp2 = array();
        foreach($tmp as $key => $value){
            $tmp2[$key] = $array[$key];
        }

        return $tmp2;
    }
}
