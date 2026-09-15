<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Doctrine\QueryFormatter;

/**
 * Help generate SQL statements to format column data.
 */
final class MysqlFormatter extends AbstractFormatter
{
    /**
     * Format field to datetime.
     */
    public function toDateTime($field, string $format = '%Y-%m-%d %k:%i:%s'): string
    {
        return "STR_TO_DATE({$field}, '{$format}')";
    }

    /**
     * Format field to date.
     */
    public function toDate($field, string $format = '%Y-%m-%d'): string
    {
        return "STR_TO_DATE({$field}, '{$format}')";
    }

    /**
     * Format field to time.
     */
    public function toTime($field, string $format = '%k:%i:%s'): string
    {
        return "STR_TO_DATE({$field}, '{$format}')";
    }

    /**
     * Format field to a numeric.
     *
     * @return mixed
     */
    public function toNumeric($field)
    {
        return $field;
    }
}
