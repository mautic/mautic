<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StatsBundle\Aggregate\Collection;

use DateTime;
use DateTimeZone;
use Exception;
use Mautic\StatsBundle\Aggregate\Calculator;
use Mautic\StatsBundle\Aggregate\Collection\DAO\StatsDAO;
use Mautic\StatsBundle\Aggregate\Helper\CalculatorHelper;

class StatCollection
{
    /**
     * @var StatsDAO
     */
    private $stats;

    /**
     * @var Calculator
     */
    private $calculator;

    public function __construct()
    {
        $this->stats = new StatsDAO();
    }

    /**
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $count
     *
     * @return $this
     *
     * @throws Exception
     */
    public function addStat($year, $month, $day, $hour, $count)
    {
        $this->stats
            ->getYear($year)
            ->getMonth($month)
            ->getDay($day)
            ->getHour($hour)
            ->setCount($count);

        return $this;
    }

    /**
     * @param int $count
     *
     * @return $this
     *
     * @throws Exception
     */
    public function addStatByDateTime(DateTime $dateTime, $count)
    {
        $dateTime->setTimezone(new DateTimeZone('UTC'));

        $this->addStat(
            $dateTime->format('Y'),
            $dateTime->format('n'),
            $dateTime->format('j'),
            $dateTime->format('H'),
            $count
        );

        return $this;
    }

    /**
     * @param $dateTimeInUTC
     * @param $count
     *
     * @return $this
     *
     * @throws Exception
     */
    public function addStatByDateTimeStringInUTC($dateTimeInUTC, $count)
    {
        if (preg_match('/([0-9]{4})\\s([0-9]{2})/', $dateTimeInUTC, $matches)) {    //  Is this a week?
            $dateTimeString = CalculatorHelper::getWeekDateString($matches[1].'-'.$matches[2]);
            $dateTime       = new DateTime($dateTimeString, new DateTimeZone('UTC'));
        } elseif (4 === strlen($dateTimeInUTC) and is_numeric($dateTimeInUTC)) {
            $dateTime = (new DateTime('now', new DateTimeZone('UTC')))
                ->setDate($dateTimeInUTC, 1, 1)
                ->setTime(0, 0);
        } else {
            $dateTime = new DateTime($dateTimeInUTC, new DateTimeZone('UTC'));
        }
        $this->addStatByDateTime($dateTime, $count);

        return $this;
    }

    /**
     * @return StatsDAO
     */
    public function getStats()
    {
        return $this->stats;
    }

    /**
     * @return Calculator
     */
    public function getCalculator(DateTime $fromDateTime, DateTime $toDateTime)
    {
        if (is_null($this->calculator)) {
            $this->calculator = new Calculator($this->stats, $fromDateTime, $toDateTime);
        }

        return $this->calculator;
    }
}
