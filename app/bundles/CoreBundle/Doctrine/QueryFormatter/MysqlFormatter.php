<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Doctrine\QueryFormatter;

/**
 * Help generate SQL statements to format column data
 *
 * Class AbstractFormat
 */
class MysqlFormatter extends AbstractFormatter
{
    /**
     * Format field to datetime
     *
     * @param        $field
     * @param string $format
     *
     * @return mixed
     */
    public function toDateTime($field, $format = 'Y-m-d H:i:s')
    {
        return "DATETIME($field)";
    }

    /**
     * Format field to date
     *
     * @param        $field
     * @param string $format
     *
     * @return mixed
     */
    public function toDate($field, $format = 'Y-m-d')
    {
        return "DATE($field)";
    }

    /**
     * Format field to time
     *
     * @param        $field
     * @param string $format
     *
     * @return mixed
     */
    public function toTime($field, $format = 'H:i:s')
    {
        return "TIME($field)";
    }

    /**
     * Format field to a numeric
     *
     * @param $field
     *
     * @return mixed
     */
    public function toNumeric($field)
    {
        return $field;
    }
}