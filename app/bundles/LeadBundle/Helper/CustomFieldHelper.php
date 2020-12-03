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

use Mautic\CoreBundle\Helper\DateTimeHelper;

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
     * Transform field value based on type.
     */
    public static function fieldValueTransfomer(array $field, ?string $value)
    {
        $type = $field['type'];
        switch ($type) {
            case 'datetime':
            case 'date':
            case 'time':
                $dtHelper = new DateTimeHelper($value, null, 'local');
                switch ($type) {
                    case 'datetime':
                        $value = $dtHelper->toLocalString('Y-m-d H:i:s');
                        break;
                    case 'date':
                        $value = $dtHelper->toLocalString('Y-m-d');
                        break;
                    case 'time':
                        $value = $dtHelper->toLocalString('H:i:s');
                        break;
                }
                break;
        }

        return $value;
    }

    /**
     * Transform all fields values.
     */
    public static function fieldsValuesTransformer(array $fields, array $values)
    {
        foreach ($values as $alias => &$value) {
            if (!empty($fields[$alias])) {
                $value = self::fieldValueTransfomer($fields[$alias], $value);
            }
        }

        return $values;
    }
}
