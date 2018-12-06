<?php

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\StatsBundle\Aggregate;

use Mautic\StatsBundle\Aggregate\Collection\DAO\StatDAO;
use Mautic\StatsBundle\Aggregate\Collection\StatCollection;

class Calculator
{
    /**
     * @var StatCollection
     */
    private $statCollection;

    /**
     * Calculator constructor.
     *
     * @param StatCollection $statCollection
     */
    public function __construct(StatCollection $statCollection)
    {
        $this->statCollection = $statCollection;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getSumByYear($labelFormat = 'Y')
    {
        $statDAO = new StatDAO();
        foreach ($this->statCollection->getStats()->getYears() as $year => $stats) {
            $label = (new \DateTime($year))->format($labelFormat);

            $statDAO->addStat($label, $stats->getSum());
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getSumByMonth($labelFormat = 'n')
    {
        $statDAO = new StatDAO();
        foreach ($this->statCollection->getStats()->getMonths() as $month => $stats) {
            $label = (new \DateTime($month))->format($labelFormat);

            $statDAO->addStat($label, $stats->getSum());
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getSumByDay($labelFormat = 'd')
    {
        $statDAO = new StatDAO();
        foreach ($this->statCollection->getStats()->getDays() as $day => $stats) {
            $label = (new \DateTime($day))->format($labelFormat);

            $statDAO->addStat($label, $stats->getSum());
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getSumByHour($labelFormat = 'G')
    {
        $statDAO = new StatDAO();
        foreach ($this->statCollection->getStats()->getDays() as $day => $stats) {
            $label = (new \DateTime($day))->format($labelFormat);

            $statDAO->addStat($label, $stats->getSum());
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getAverageByYear($labelFormat = 'Y')
    {
        $statDAO = new StatDAO();
        foreach ($this->statCollection->getStats()->getYears() as $year => $stats) {
            $label = (new \DateTime($year))->format($labelFormat);

            $statDAO->addStat($label, $stats->getAverage());
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getAverageByMonth($labelFormat = 'n')
    {
        $statDAO = new StatDAO();
        foreach ($this->statCollection->getStats()->getMonths() as $month => $stats) {
            $label = (new \DateTime($month))->format($labelFormat);

            $statDAO->addStat($label, $stats->getAverage());
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getAverageByDay($labelFormat = 'd')
    {
        $statDAO = new StatDAO();
        foreach ($this->statCollection->getStats()->getDays() as $day => $stats) {
            $label = (new \DateTime($day))->format($labelFormat);

            $statDAO->addStat($label, $stats->getAverage());
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @return StatDAO
     *
     * @throws \Exception
     */
    public function getAverageByHour($labelFormat = 'G')
    {
        $statDAO = new StatDAO();
        foreach ($this->statCollection->getStats()->getDays() as $day => $stats) {
            $label = (new \DateTime($day))->format($labelFormat);

            $statDAO->addStat($label, $stats->getAverage());
        }

        return $statDAO;
    }
}
