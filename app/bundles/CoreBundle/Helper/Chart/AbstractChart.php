<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\Chart;

use Doctrine\DBAL\Connection;

/**
 * Class AbstractChart
 */
abstract class AbstractChart
{
    /**
     * Datasets of the chart
     *
     * @var array
     */
    protected $datasets = array();

    /**
     * Labels of the time axe
     *
     * @var array
     */
    protected $labels = array();

    /**
     * Match date/time unit to a humanly readable label
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     *
     * @var array
     */
    protected $labelFormats = array(
        's' => 'H:i:s',
        'i' => 'H:i',
        'H' => 'l ga',
        'd' => 'jS F',
        'W' => 'W',
        'M' => 'F y',
        'Y' => 'Y',
    );

    /**
     * Match date/time unit to a SQL datetime format
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     *
     * @var array
     */
    protected $sqlFormats = array(
        's' => 'Y-m-d H:i:s',
        'i' => 'Y-m-d H:i:00',
        'H' => 'Y-m-d H:00:00',
        'd' => 'Y-m-d 00:00:00',
        'W' => 'Y-m-d 00:00:00',
        'M' => 'Y-m-00 00:00:00',
        'Y' => 'Y-00-00 00:00:00',
    );

    /**
     * Match date/time unit to a PostgreSQL datetime format
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * {@link www.postgresql.org/docs/9.1/static/functions-datetime.html}
     *
     * @var array
     */
    protected $postgresTimeUnits = array(
        's' => 'second',
        'i' => 'minute',
        'H' => 'hour',
        'd' => 'day',
        'W' => 'week',
        'm' => 'month',
        'Y' => 'year'
    );

    /**
     * Match date/time unit to a MySql datetime format
     * {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     * {@link dev.mysql.com/doc/refman/5.5/en/date-and-time-functions.html#function_date-format}
     *
     * @var array
     */
    protected $mysqlTimeUnits = array(
        's' => '%Y-%m-%d %H:%i:%s',
        'i' => '%Y-%m-%d %H:%i',
        'H' => '%Y-%m-%d %H',
        'd' => '%Y-%m-%d',
        'W' => '%Y-%U',
        'm' => '%Y-%m',
        'Y' => '%Y'
    );

    /**
     * Colors which can be used in graphs
     *
     * @var array
     */
    public $colors = array(
        array(
            'color' => '#4E5D9D',
            'fillColor' => 'rgba(78, 93, 157, 0.5)',
            'strokeColor' => 'rgba(78, 93, 157, 0.8)',
            'highlightFill' => 'rgba(78, 93, 157, 0.75)',
            'highlightStroke' => 'rgba(78, 93, 157, 1)'),
        array(
            'color' => '#00b49c',
            'fillColor' => 'rgba(0, 180, 156, 0.5)',
            'strokeColor' => 'rgba(0, 180, 156, 0.8)',
            'highlightFill' => 'rgba(0, 180, 156, 0.75)',
            'highlightStroke' => 'rgba(0, 180, 156, 1)'),
        array(
            'color' => '#fd9572',
            'fillColor' => 'rgba(253, 149, 114, 0.5)',
            'strokeColor' => 'rgba(253, 149, 114, 0.8)',
            'highlightFill' => 'rgba(253, 149, 114, 0.75)',
            'highlightStroke' => 'rgba(253, 149, 114, 1)'),
        array(
            'color' => '#fdb933',
            'fillColor' => 'rgba(253, 185, 51, 0.5)',
            'strokeColor' => 'rgba(253, 185, 51, 0.8)',
            'highlightFill' => 'rgba(253, 185, 51, 0.75)',
            'highlightStroke' => 'rgba(253, 185, 51, 1)'),
        array(
            'color' => '#757575',
            'fillColor' => 'rgba(117, 117, 117, 0.5)',
            'strokeColor' => 'rgba(117, 117, 117, 0.8)',
            'highlightFill' => 'rgba(117, 117, 117, 0.75)',
            'highlightStroke' => 'rgba(117, 117, 117, 1)'),
        array(
            'color' => '#9C4E5C',
            'fillColor' => 'rgba(156, 78, 92, 0.5)',
            'strokeColor' => 'rgba(156, 78, 92, 0.8)',
            'highlightFill' => 'rgba(156, 78, 92, 0.75)',
            'highlightStroke' => 'rgba(156, 78, 92, 1)'),
        array(
            'color' => '#694535',
            'fillColor' => 'rgba(105, 69, 53, 0.5)',
            'strokeColor' => 'rgba(105, 69, 53, 0.8)',
            'highlightFill' => 'rgba(105, 69, 53, 0.75)',
            'highlightStroke' => 'rgba(105, 69, 53, 1)'),
        array(
            'color' => '#596935',
            'fillColor' => 'rgba(89, 105, 53, 0.5)',
            'strokeColor' => 'rgba(89, 105, 53, 0.8)',
            'highlightFill' => 'rgba(89, 105, 53, 0.75)',
            'highlightStroke' => 'rgba(89, 105, 53, 1)'),
    );

    /**
     * Define a dataset by name and data. Method will add the rest.
     *
     * @param  string $label
     * @param  array  $data
     *
     * @return $this
     */
    public function setDataset($label = null, array $data)
    {
        $this->datasets[] = array(
            'label' => $label,
            'data'  => $data
        );

        return $this;
    }

    /**
     * Generate array of labels from the form data
     *
     * @param  string  $unit
     * @param  integer $limit
     * @param  string  $startDate
     * @param  string  $order
     */
    public function generateTimeLabels($unit, $limit, $startDate = null, $order = 'DESC')
    {
        if (!isset($this->labelFormats[$unit])) {
            throw new \UnexpectedValueException('Date/Time unit "' . $unit . '" is not available for a label.');
        }

        $date    = new \DateTime($startDate);
        $oneUnit = $this->getUnitObject($unit);

        for ($i = 0; $i < $limit; $i++) {
            $this->labels[] = $date->format($this->labelFormats[$unit]);
            if ($order == 'DESC') {
                $date->sub($oneUnit);
            } else {
                $date->add($oneUnit);
            }
        }
        $this->labels = array_reverse($this->labels);
    }

    /**
     * Generate array of labels from the form data
     *
     * @param  string  $unit
     *
     * @return DateInterval
     */
    public function getUnitObject($unit)
    {
        $isTime  = in_array($unit, array('H', 'i', 's')) ? 'T' : '';
        return new \DateInterval('P' . $isTime . '1' . strtoupper($unit));
    }

    /**
     * Check if the DB connection is to PostgreSQL database
     *
     * @param  Connection $connection
     *
     * @return boolean
     */
    public function isPostgres(Connection $connection)
    {
        $platform = $connection->getDatabasePlatform();
        return $platform instanceof \doctrine\DBAL\Platforms\PostgreSqlPlatform;
    }

    /**
     * Check if the DB connection is to MySql database
     *
     * @param  Connection $connection
     *
     * @return boolean
     */
    public function isMysql(Connection $connection)
    {
        $platform = $connection->getDatabasePlatform();
        return $platform instanceof \doctrine\DBAL\Platforms\MySqlPlatform;
    }

    /**
     * Get the right unit for current database platform
     *
     * @param  Connection $connection
     * @param  string     $unit {@link php.net/manual/en/function.date.php#refsect1-function.date-parameters}
     *
     * @return string
     */
    public function translateTimeUnit(Connection $connection, $unit)
    {
        if ($this->isPostgres($connection)) {
            if (!isset($this->postgresTimeUnits[$unit])) {
                throw new \UnexpectedValueException('Date/Time unit "' . $unit . '" is not available for Postgres.');
            }

            return $this->postgresTimeUnits[$unit];
        } elseif ($this->isMySql($connection)) {
            if (!isset($this->mysqlTimeUnits[$unit])) {
                throw new \UnexpectedValueException('Date/Time unit "' . $unit . '" is not available for MySql.');
            }

            return $this->mysqlTimeUnits[$unit];
        }

        return $unit;
    }

    /**
     * Helper function to shorten/truncate a string
     *
     * @param string  $string
     * @param integer $length
     * @param string  $append
     *
     * @return string
     */
    public static function truncate($string, $length = 100, $append = "...")
    {
        $string = trim($string);

        if (strlen($string) > $length) {
            $string = wordwrap($string, $length);
            $string = explode("\n", $string, 2);
            $string = $string[0] . $append;
        }

        return $string;
    }
}
