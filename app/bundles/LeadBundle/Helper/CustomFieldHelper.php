<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\LeadBundle\Segment\OperatorOptions;

/**
 * Helper class custom field operations.
 */
final class CustomFieldHelper
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
     * @deprecated use the correctly spelled `fieldValueTransformer` instead
     *
     * @param mixed $value This value can be at least array, string, null and maybe others
     *
     * @return mixed|string|null
     */
    public static function fieldValueTransfomer(array $field, $value, ?DateTimeHelper $dateTimeHelper = null): mixed
    {
        return self::fieldValueTransformer($field, $value, null, $dateTimeHelper);
    }

    /**
     * @param array<string, mixed> $field
     * @param mixed                $value This value can be at least array, string, null and maybe others
     *
     * @return mixed|string|null
     */
    public static function fieldValueTransformer(array $field, mixed $value, ?string $operator = null, ?DateTimeHelper $dateTimeHelper = null): mixed
    {
        $type = $field['type'];

        if (null === $value || is_array($value) || !in_array($type, ['datetime', 'date', 'time'])) {
            // Do not transform null, array, and non-date type values
            return $value;
        }

        // Not sure if this happens anywhere but just in case do not transform empty strings
        if ('' === $value) {
            return null;
        }

        if (OperatorOptions::IN_NEXT === $operator) {
            $type = OperatorOptions::IN_NEXT;
        } elseif (OperatorOptions::IN_LAST === $operator) {
            $type = OperatorOptions::IN_LAST;
        }

        $dtHelper = $dateTimeHelper ?: new DateTimeHelper($value, null, 'local');
        $dtHelper->setDateTime($value);

        return match ($type) {
            'datetime'               => $dtHelper->toUtcString('Y-m-d H:i:s'),
            'date'                   => $dtHelper->toUtcString('Y-m-d'),
            'time'                   => $dtHelper->toUtcString('H:i:s'),
            OperatorOptions::IN_NEXT => $dtHelper->toUtcString('Y-m-d').' 23:59:59',
            OperatorOptions::IN_LAST => $dtHelper->toUtcString('Y-m-d').' 00:00:00',
            default                  => $value,
        };
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
