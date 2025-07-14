<?php

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\DateTimeHelper;

/**
 * Helper class custom field operations.
 */
class CustomFieldHelper
{
    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_NUMBER  = 'number';

    public const TYPE_SELECT  = 'select';

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
        if (null === $value) {
            // do not transform null values
            return null;
        }

        return match ($type) {
            self::TYPE_NUMBER  => is_numeric($value) || '' === $value ? (float) $value : $value,
            self::TYPE_BOOLEAN => (bool) $value,
            self::TYPE_SELECT  => is_scalar($value) ? (string) $value : $value,
            default            => $value,
        };
    }

    /**
     * @param mixed $value This value can be at least array, string, null and maybe others
     *
     * @return mixed|string|null
     */
    public static function fieldValueTransfomer(array $field, $value, ?DateTimeHelper $dateTimeHelper = null)
    {
        if (null === $value) {
            // do not transform null values
            return null;
        }

        $type = $field['type'];
        switch ($type) {
            case 'datetime':
            case 'date':
            case 'time':
                // Not sure if this happens anywhere but just in case do not transform empty strings
                if ('' === $value) {
                    return null;
                }

                if (!($value instanceof \DateTimeInterface) && !is_string($value)) {
                    throw new \InvalidArgumentException('Wrong type given. String or DateTimeInterface expected.');
                }

                $dtHelper = $dateTimeHelper ?: new DateTimeHelper($value, null, 'local');
                $dtHelper->setDateTime($value);

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
     *
     * @param mixed[] $fields
     * @param mixed[] $values
     *
     * @return mixed[]
     */
    public static function fieldsValuesTransformer(array $fields, array $values, ?DateTimeHelper $dateTimeHelper = null): array
    {
        foreach ($values as $alias => &$value) {
            if (!empty($fields[$alias]) && is_array($fields[$alias])) {
                $value = self::fieldValueTransfomer($fields[$alias], $value, $dateTimeHelper);
            }
        }

        return $values;
    }
}
