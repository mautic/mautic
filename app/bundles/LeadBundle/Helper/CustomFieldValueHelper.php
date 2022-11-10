<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\ArrayHelper;
use Mautic\CoreBundle\Helper\Serializer;

/**
 * Helper class custom field operations.
 */
class CustomFieldValueHelper
{
    const TYPE_BOOLEAN     = 'boolean';

    const TYPE_SELECT      = 'select';

    const TYPE_MULTISELECT = 'multiselect';

    /**
     * @return array
     */
    public static function normalizeValues(array $customFields)
    {
        if (isset($customFields['core'])) {
            foreach ($customFields as $group => $fields) {
                foreach ($fields as $alias => $field) {
                    if (is_array($field)) {
                        $customFields[$group][$alias]['normalizedValue'] = self::normalizeValue($field);
                    }
                }
            }
        } else {
            foreach ($customFields as $alias => &$field) {
                if (is_array($field)) {
                    $customFields[$alias]['normalizedValue'] = self::normalizeValue($field);
                }
            }
        }

        return $customFields;
    }

    /**
     * @return mixed
     */
    private static function normalizeValue(array $field)
    {
        $value      = ArrayHelper::getValue('value', $field, '');
        $type       = ArrayHelper::getValue('type', $field);
        $properties = ArrayHelper::getValue('properties', $field);
        if ('' !== $value && $type && $properties) {
            if (!is_array($properties)) {
                $properties = Serializer::decode($properties);
            }
            switch ($type) {
                case self::TYPE_BOOLEAN:
                    foreach ($properties as $key => $property) {
                        if ('yes' === $key && !isset($properties[1])) {
                            $properties[1] = $property;
                        } elseif ('no' === $key && !isset($properties[0])) {
                            $properties[0] = $property;
                        }
                    }
                    if (isset($properties[$value])) {
                        $value = $properties[$value];
                    }
                    break;
                case self::TYPE_SELECT:
                    $value = self::setValueFromPropertiesList($properties, $value);
                    break;
                case self::TYPE_MULTISELECT:
                    $values = explode('|', $value);
                    foreach ($values as &$val) {
                        $val = self::setValueFromPropertiesList($properties, $val);
                    }
                    $value = implode('|', $values);
                    break;
            }
        }

        return $value;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public static function setValueFromPropertiesList(array $properties, $value)
    {
        if (isset($properties['list']) && is_array($properties['list'])) {
            $list = $properties['list'];
            if (!is_array($list)) {
                return $value;
            }
            foreach ($list as $property) {
                if (isset($property[$value])) {
                    return $property[$value];
                } elseif (isset($property['value']) && $property['value'] == $value) {
                    return $property['label'];
                }
            }
        }

        return $value;
    }
}
