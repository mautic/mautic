<?php

namespace Mautic\CoreBundle\Doctrine\QueryFormatter;

/**
 * Help generate SQL statements to format column data.
 *
 * Class AbstractFormat
 */
class MysqlFormatter extends AbstractFormatter
{
    /**
     * Format field to datetime.
     *
     * @param string $format
     *
     * @return mixed
     */
    public function toDateTime($field, $format = '%Y-%m-%d %k:%i:%s')
    {
        return "STR_TO_DATE($field, '$format')";
    }

    /**
     * Format field to date.
     *
     * @param string $format
     *
     * @return mixed
     */
    public function toDate($field, $format = '%Y-%m-%d')
    {
        return "STR_TO_DATE($field, '$format')";
    }

    /**
     * Format field to time.
     *
     * @param string $format
     *
     * @return mixed
     */
    public function toTime($field, $format = '%k:%i:%s')
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
