<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Helper;

class Cleaner
{
    const FIELD_TYPE_STRING   = 'string';
    const FIELD_TYPE_BOOL     = 'boolean';
    const FIELD_TYPE_NUMBER   = 'number';
    const FIELD_TYPE_DATETIME = 'datetime';
    const FIELD_TYPE_DATE     = 'date';

    /**
     * @param $value
     * @param $fieldType
     *
     * @return bool|float|string
     */
    public static function clean($value, $fieldType = self::FIELD_TYPE_STRING)
    {
        $clean = strip_tags(html_entity_decode($value, ENT_QUOTES));
        switch ($fieldType) {
            case self::FIELD_TYPE_BOOL:
                return (bool) $clean;
            case self::FIELD_TYPE_NUMBER:
                return (float) $clean;
            case self::FIELD_TYPE_DATETIME:
                $dateTimeValue = new \DateTime($value);

                return (!empty($clean)) ? $dateTimeValue->format('c') : '';
            case self::FIELD_TYPE_DATE:
                $dateTimeValue = new \DateTime($value);

                return (!empty($clean)) ? $dateTimeValue->format('Y-m-d') : '';
            default:
                return $clean;
        }
    }
}
