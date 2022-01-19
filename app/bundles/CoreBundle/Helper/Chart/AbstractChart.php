<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Helper\Chart;

use Mautic\CoreBundle\Helper\ColorHelper;

abstract class AbstractChart
{
    use DateRangeUnitTrait;

    /**
     * Datasets of the chart.
     *
     * @var array
     */
    protected $datasets = [];

    /**
     * Labels of the time axe.
     *
     * @var array
     */
    protected $labels = [];

    /**
     * Date from.
     *
     * @var \DateTime
     */
    protected $dateFrom;

    /**
     * Date to.
     *
     * @var \DateTime
     */
    protected $dateTo;

    /**
     * Timezone data is requested to be in.
     *
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * Time unit.
     *
     * @var string
     */
    protected $unit;

    /**
     * True if unit is H, i, or s.
     *
     * @var bool
     */
    protected $isTimeUnit = false;

    /**
     * amount of items.
     *
     * @var int
     */
    protected $amount;

    /**
     * Default Mautic colors.
     *
     * @var array
     */
    public $colors = ['#4E5D9D', '#00B49C', '#FD9572', '#FDB933', '#757575', '#9C4E5C', '#694535', '#596935'];

    /**
     * Create a DateInterval time unit.
     *
     * @param string $unit
     *
     * @return \DateInterval
     */
    public function getUnitInterval($unit = null)
    {
        if (!$unit) {
            $unit = $this->unit;
        }

        $isTime = in_array($unit, ['H', 'i', 's']) ? 'T' : '';

        if ('i' == $unit) {
            $unit = 'M';
        }

        return new \DateInterval('P'.$isTime.'1'.strtoupper($unit));
    }

    /**
     * Helper function to shorten/truncate a string.
     *
     * @param string $string
     * @param int    $length
     * @param string $append
     *
     * @return string
     */
    public static function truncate($string, $length = 100, $append = '...')
    {
        $string = trim($string);

        if (strlen($string) > $length) {
            $string = wordwrap($string, $length);
            $string = explode("\n", $string, 2);
            $string = $string[0].$append;
        }

        return $string;
    }

    /**
     * Sets the clones of the date range and validates it.
     */
    public function setDateRange(\DateTime $dateFrom, \DateTime $dateTo)
    {
        $this->timezone = $dateFrom->getTimezone();
        $this->dateFrom = clone $dateFrom;
        $this->dateTo   = clone $dateTo;

        // a diff of two identical dates returns 0, but we expect 24 hours
        if ($dateFrom == $dateTo) {
            $this->dateTo->modify('+1 day');
        }

        // If today, adjust dateTo to be end of today if unit is not time based or to the current hour if it is
        if (!$this->isTimeUnit) {
            $this->dateTo->setTime(23, 59, 59);

            return;
        }

        // If time aware and the to date is today, set the stats to the current hour to avoid empty future hours in graphs
        $now = new \DateTime();
        if ($now->format('Y-m-d') === $this->dateTo->format('Y-m-d')) {
            $this->dateTo = $now;
        }
    }

    /**
     * Modify the date to add one current time unit to it and subtract 1 second.
     * Can be used to get the current day results.
     */
    public function addOneUnitMinusOneSec(\DateTime &$date)
    {
        $date->add($this->getUnitInterval())->modify('-1 sec');
    }

    /**
     * Count amount of time slots of a time unit from a date range.
     *
     * @return int
     */
    public function countAmountFromDateRange()
    {
        switch ($this->unit) {
            case 's':
                $amount = ($this->dateTo->diff($this->dateFrom)->format('%s'));
                ++$amount;
                break;
            case 'i':
                $amount = ($this->dateTo->diff($this->dateFrom)->format('%i'));
                ++$amount;
                break;
            case 'd':
                $amount = ($this->dateTo->diff($this->dateFrom)->format('%a') + 1);
                break;
            case 'W':
                $dayAmount = $this->dateTo->diff($this->dateFrom)->format('%a');
                $amount    = (ceil($dayAmount / 7) + 1);
                break;
            case 'm':
                $amount = $this->dateTo->diff($this->dateFrom)->format('%y') * 12 + $this->dateTo->diff($this->dateFrom)->format('%m');

                // Add 1 month if there are some days left
                if ($this->dateTo->diff($this->dateFrom)->format('%d') > 0) {
                    ++$amount;
                }

                // Add 1 month if count of days are greater or equal than in date to
                if ($this->dateFrom->format('d') >= $this->dateTo->format('d')) {
                    ++$amount;
                }
                break;
            case 'H':
                $dateDiff = $this->dateTo->diff($this->dateFrom);
                $amount   = $dateDiff->h + $dateDiff->days * 24;
                ++$amount;
                break;
            default:
                $amount = ($this->dateTo->diff($this->dateFrom)->format('%'.$this->unit) + 1);
                break;
        }

        return $amount;
    }

    /**
     * Generate unique color for the dataset.
     *
     * @param int $datasetId
     *
     * @return ColorHelper
     */
    public function configureColorHelper($datasetId)
    {
        $colorHelper = new ColorHelper();

        if (isset($this->colors[$datasetId])) {
            $color = $colorHelper->setHex($this->colors[$datasetId]);
        } else {
            $color = $colorHelper->buildRandomColor();
        }

        return $color;
    }
}
