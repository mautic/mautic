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
     * Date from
     *
     * @var DateTime
     */
    protected $dateFrom;

    /**
     * Date to
     *
     * @var DateTime
     */
    protected $dateTo;

    /**
     * Time unit
     *
     * @var string
     */
    protected $unit;

    /**
     * Colors which can be used in graphs
     *
     * @var array
     */
    public $colors = array(
        array(
            'fillColor' => 'rgba(78, 93, 157, 0.5)',
            'strokeColor' => 'rgba(78, 93, 157, 0.8)',
            'highlightFill' => 'rgba(78, 93, 157, 0.75)',
            'highlightStroke' => 'rgba(78, 93, 157, 1)'),
        array(
            'fillColor' => 'rgba(0, 180, 156, 0.5)',
            'strokeColor' => 'rgba(0, 180, 156, 0.8)',
            'highlightFill' => 'rgba(0, 180, 156, 0.75)',
            'highlightStroke' => 'rgba(0, 180, 156, 1)'),
        array(
            'fillColor' => 'rgba(253, 149, 114, 0.5)',
            'strokeColor' => 'rgba(253, 149, 114, 0.8)',
            'highlightFill' => 'rgba(253, 149, 114, 0.75)',
            'highlightStroke' => 'rgba(253, 149, 114, 1)'),
        array(
            'fillColor' => 'rgba(253, 185, 51, 0.5)',
            'strokeColor' => 'rgba(253, 185, 51, 0.8)',
            'highlightFill' => 'rgba(253, 185, 51, 0.75)',
            'highlightStroke' => 'rgba(253, 185, 51, 1)'),
        array(
            'fillColor' => 'rgba(117, 117, 117, 0.5)',
            'strokeColor' => 'rgba(117, 117, 117, 0.8)',
            'highlightFill' => 'rgba(117, 117, 117, 0.75)',
            'highlightStroke' => 'rgba(117, 117, 117, 1)'),
        array(
            'fillColor' => 'rgba(156, 78, 92, 0.5)',
            'strokeColor' => 'rgba(156, 78, 92, 0.8)',
            'highlightFill' => 'rgba(156, 78, 92, 0.75)',
            'highlightStroke' => 'rgba(156, 78, 92, 1)'),
        array(
            'fillColor' => 'rgba(105, 69, 53, 0.5)',
            'strokeColor' => 'rgba(105, 69, 53, 0.8)',
            'highlightFill' => 'rgba(105, 69, 53, 0.75)',
            'highlightStroke' => 'rgba(105, 69, 53, 1)'),
        array(
            'fillColor' => 'rgba(89, 105, 53, 0.5)',
            'strokeColor' => 'rgba(89, 105, 53, 0.8)',
            'highlightFill' => 'rgba(89, 105, 53, 0.75)',
            'highlightStroke' => 'rgba(89, 105, 53, 1)'),
    );

    /**
     * Create a DateInterval time unit
     *
     * @param  string  $unit
     *
     * @return DateInterval
     */
    public function getUnitObject($unit)
    {
        $isTime  = in_array($unit, array('H', 'i', 's')) ? 'T' : '';
        $toUpper = array('d', 'i');

        if ($unit == 'i') {
            $unit = 'M';
        }

        return new \DateInterval('P' . $isTime . '1' . strtoupper($unit));
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

    /**
     * Sets the clones of the date range and validates it
     *
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     */
    public function setDateRange(\DateTime $dateFrom, \DateTime $dateTo)
    {
        $this->dateFrom = clone $dateFrom;
        $this->dateTo = clone $dateTo;

        // a diff of two identical dates returns 0, but we expect 24 hours
        if ($dateFrom == $dateTo) {
            $this->dateTo->modify('+1 day');
        }
    }

    /**
     * Count amount of time slots of a time unit from a date range
     *
     * @return int
     */
    public function countAmountFromDateRange()
    {
        switch ($this->unit) {
            case 'd':
            case 'W':
                $unit = 'a';
                $amount = ($this->dateTo->diff($this->dateFrom)->format('%' . $unit) + 1);
                $amount = $this->unit == 'W' ? floor($amount / 7) : $amount;
                break;
            case 'm':
                $amount = $this->dateTo->diff($this->dateFrom)->format('%y') * 12 + $this->dateTo->diff($this->dateFrom)->format('%m');
                if ($this->dateTo->diff($this->dateFrom)->format('%d') > 0) $amount++;
                if ($this->dateFrom->format('d') >= $this->dateTo->format('d')) $amount++;
                break;
            case 'H':
                $dateDiff = $this->dateTo->diff($this->dateFrom);
                $amount = $dateDiff->h + $dateDiff->days * 24;
                break;
            default:
                $amount = ($this->dateTo->diff($this->dateFrom)->format('%' . $this->unit) + 1);
                break;
        }

        return $amount;
    }

    /**
     * Returns appropriate time unit from a date range so the line/bar charts won't be too full/empty
     *
     * @return string
     */
    public function getTimeUnitFromDateRange()
    {
        $diff = $this->dateTo->diff($this->dateFrom)->format('%a');
        $unit = 'd';

        if ($diff <= 1) $unit = 'H';
        if ($diff > 31) $unit = 'W';
        if ($diff > 100) $unit = 'm';
        if ($diff > 1000) $unit = 'Y';

        return $unit;
    }

    /**
     * Returns the initiated chart query object
     *
     * @param Connection $connection
     *
     * @return ChartQuery
     */
    public function getChartQuery(Connection $connection)
    {
        return new ChartQuery($connection, $this->dateFrom, $this->dateTo, $this->unit);
    }
}
