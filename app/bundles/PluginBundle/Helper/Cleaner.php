<?php

namespace Mautic\PluginBundle\Helper;

class Cleaner
{
    public const FIELD_TYPE_STRING   = 'string';

    public const FIELD_TYPE_BOOL     = 'boolean';

    public const FIELD_TYPE_NUMBER   = 'number';

    public const FIELD_TYPE_DATETIME = 'datetime';

    public const FIELD_TYPE_DATE     = 'date';

    /**
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
