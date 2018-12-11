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

use Mautic\StatsBundle\Aggregate\Calculator;
use Mautic\StatsBundle\Aggregate\Collection\DAO\StatsDAO;

class StatCollection
{
    /**
     * @var string
     */
    private $statName;

    /**
     * @var int|null
     */
    private $itemId;

    /**
     * @var StatsDAO
     */
    private $stats;

    /**
     * @var Calculator
     */
    private $calculator;

    /**
     * StatCollection constructor.
     *
     * @param string   $statName
     * @param int|null $itemId
     */
    public function __construct($statName, $itemId = null)
    {
        $this->statName = $statName;
        $this->itemId   = $itemId;
        $this->stats    = new StatsDAO();
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
     * @throws \Exception
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
     * @param \DateTime $dateTime
     * @param int       $count
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function addStatByDateTime(\DateTime $dateTime, $count)
    {
        $dateTime->setTimezone(new \DateTimeZone('UTC'));

        $this->addStat(
            $dateTime->format('Y'),
            $dateTime->format('n'),
            $dateTime->format('j'),
            $dateTime->format('h'),
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
     * @throws \Exception
     */
    public function addStatByDateTimeStringInUTC($dateTimeInUTC, $count)
    {
        $this->addStatByDateTime(new \DateTime($dateTimeInUTC, new \DateTimeZone('UTC')), $count);

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
     * @param \DateTime $fromDateTime
     * @param \DateTime $toDateTime
     *
     * @return Calculator
     */
    public function getCalculator(\DateTime $fromDateTime, \DateTime $toDateTime)
    {
        if (null !== $this->calculator) {
            return $this->calculator;
        }

        $this->calculator = new Calculator($this->stats, $fromDateTime, $toDateTime);

        return $this->calculator;
    }
}
