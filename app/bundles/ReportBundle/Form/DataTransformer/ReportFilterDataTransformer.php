<?php

namespace Mautic\ReportBundle\Form\DataTransformer;

use Mautic\CoreBundle\Helper\DateTimeHelper;
use Mautic\ReportBundle\Helper\RelativeDateHelper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

/**
 * @implements DataTransformerInterface<array<mixed>, array<mixed>>
 */
final class ReportFilterDataTransformer implements DataTransformerInterface
{
    /**
     * @param array $columns
     */
    public function __construct(
        private $columns,
    ) {
    }

    /**
     * @return array
     */
    public function transform(mixed $value): mixed
    {
        $filters = $value;
        if (!is_array($filters)) {
            return [];
        }

        foreach ($filters as &$f) {
            if (!isset($this->columns[$f['column']])) {
                // Likely being called by form.pre_set_data after post
                return $filters;
            }
            $type = $this->columns[$f['column']]['type'];
            if (in_array($type, ['datetime', 'time', DateTimeType::class, DateType::class, TimeType::class])) {
                // Skip datetime parsing for string-like conditions
                if (isset($f['condition']) && in_array($f['condition'], ['like', 'notLike', 'startsWith', 'endsWith', 'contains'])) {
                    continue;
                }
                if (in_array($type, ['datetime', 'date', DateTimeType::class, DateType::class], true) && RelativeDateHelper::isRelative($f['value'])) {
                    continue;
                }
                $dt         = new DateTimeHelper($f['value'], null, 'utc');

                if (in_array($type, ['date', DateType::class])) {
                    // Pass the specific format for a date
                    $f['value'] = $dt->toLocalString('Y-m-d');
                } elseif (in_array($type, ['time', TimeType::class])) {
                    // Pass the specific format for a time
                    $f['value'] = $dt->toLocalString('H:i:s');
                } else {
                    // Call without arguments for the default datetime format
                    $f['value'] = $dt->toLocalString();
                }
            }
        }

        return $filters;
    }

    /**
     * @return array
     */
    public function reverseTransform(mixed $value): mixed
    {
        $filters = $value;
        if (!is_array($filters)) {
            return [];
        }

        foreach ($filters as &$f) {
            if (!isset($this->columns[$f['column']])) {
                // Likely being called by form.pre_set_data after post
                return $filters;
            }
            $type = $this->columns[$f['column']]['type'];
            if (in_array($type, ['datetime', 'time'])) {
                // Skip datetime parsing for string-like conditions
                if (isset($f['condition']) && in_array($f['condition'], ['like', 'notLike', 'startsWith', 'endsWith', 'contains'])) {
                    continue;
                }
                if (RelativeDateHelper::isRelative($f['value'])) {
                    continue;
                }
                $dt         = new DateTimeHelper($f['value'], null, 'local');
                $f['value'] = $dt->toUtcString();
            }
        }

        return $filters;
    }
}
