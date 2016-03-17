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
     * Count amount of time slots of a time unit from a date range
     *
     * @param string   $timeUnit
     * @param DateTime $dateFrom
     * @param DateTime $dateTo
     *
     * @return int
     */
    public function countAmountFromDateRange($timeUnit, \DateTime $dateFrom, \DateTime $dateTo)
    {
        switch ($timeUnit) {
            case 'd':
            case 'W':
                $unit = 'a';
                $amount = ($dateTo->diff($dateFrom)->format('%' . $unit) + 1);
                $amount = $timeUnit == 'W' ? floor($amount / 7) : $amount;
                break;
            case 'm':
                $amount = $dateTo->diff($dateFrom)->format('%y') * 12 + $dateTo->diff($dateFrom)->format('%m');
                if ($dateTo->diff($dateFrom)->format('%d') > 0) $amount++;
                if ($dateFrom->format('d') >= $dateTo->format('d')) $amount++;
                break;
            case 'H':
                if ($dateFrom == $dateTo) {
                    // a diff of two identical dates returns 0, but we expect 24 hours
                    $dateTo->modify('+1 day');
                    $toClone = clone $dateTo;
                    $params['dateTo'] = $toClone->modify('-1 second');
                }
                $dateDiff = $dateTo->diff($dateFrom);
                $amount = $dateDiff->h + $dateDiff->days * 24;
            default:
                $amount = ($dateTo->diff($dateFrom)->format('%' . $timeUnit) + 1);
        }

        return $amount;
    }
}
