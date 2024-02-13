<?php

namespace Mautic\CoreBundle\Doctrine\QueryFormatter;

/**
 * Help generate SQL statements to format column data.
 */
class MysqlFormatter extends AbstractFormatter
{
    /**
     * Format field to datetime.
     *
     * @param string $format
     */
    public function toDateTime($field, $format = '%Y-%m-%d %k:%i:%s'): string
    {
        return "STR_TO_DATE($field, '$format')";
    }

    /**
     * Format field to date.
     *
     * @param string $format
     */
    public function toDate($field, $format = '%Y-%m-%d'): string
    {
        return "STR_TO_DATE($field, '$format')";
    }

    /**
     * Format field to time.
     *
     * @param string $format
     */
    public function toTime($field, $format = '%k:%i:%s'): string
    {
        return "STR_TO_DATE($field, '$format')";
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
