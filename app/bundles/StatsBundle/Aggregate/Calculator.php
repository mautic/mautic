<?php

namespace Mautic\StatsBundle\Aggregate;

use Mautic\StatsBundle\Aggregate\Collection\DAO\StatDAO;
use Mautic\StatsBundle\Aggregate\Collection\DAO\StatsDAO;
use Mautic\StatsBundle\Aggregate\Helper\CalculatorHelper;

class Calculator
{
    public function __construct(
        private StatsDAO $statsDAO,
        private ?\DateTimeInterface $fromDateTime = null,
        private ?\DateTimeInterface $toDateTime = null
    ) {
    }

    /**
     * @param string $labelFormat
     *
     * @throws \Exception
     */
    public function getSumsByYear($labelFormat = 'Y'): StatDAO
    {
        $statDAO  = new StatDAO();
        $lastYear = $this->fromDateTime ? $this->fromDateTime->format('Y') : null;

        foreach ($this->statsDAO->getYears() as $thisYear => $stats) {
            CalculatorHelper::fillInMissingYears($statDAO, $lastYear, $thisYear, $labelFormat);

            $statDAO->addStat(
                CalculatorHelper::getYearLabel($thisYear, $labelFormat),
                $stats->getSum()
            );

            $lastYear = $thisYear;
        }

        if ($this->toDateTime) {
            CalculatorHelper::fillInMissingYears($statDAO, $lastYear, $this->toDateTime->format('Y'), $labelFormat);
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @throws \Exception
     */
    public function getSumsByMonth($labelFormat = 'Y-m'): StatDAO
    {
        $statDAO   = new StatDAO();
        $lastMonth = $this->fromDateTime ? $this->fromDateTime->format('Y-m') : null;

        foreach ($this->statsDAO->getMonths() as $thisMonth => $stats) {
            CalculatorHelper::fillInMissingMonths($statDAO, $lastMonth, $thisMonth, $labelFormat);

            $statDAO->addStat(
                CalculatorHelper::getMonthLabel($thisMonth, $labelFormat),
                $stats->getSum()
            );

            $lastMonth = $thisMonth;
        }

        if ($this->toDateTime) {
            CalculatorHelper::fillInMissingMonths($statDAO, $lastMonth, $this->toDateTime->format('Y-m'), $labelFormat);
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @throws \Exception
     */
    public function getSumsByDay($labelFormat = 'Y-m-d'): StatDAO
    {
        $statDAO   = new StatDAO();
        $yesterday = $this->fromDateTime ? $this->fromDateTime->format('Y-m-d') : null;

        foreach ($this->statsDAO->getDays() as $today => $stats) {
            CalculatorHelper::fillInMissingDays($statDAO, $yesterday, $today, $labelFormat);

            $statDAO->addStat(
                CalculatorHelper::getDayLabel($today, $labelFormat),
                $stats->getSum()
            );

            $yesterday = $today;
        }

        if ($this->toDateTime) {
            CalculatorHelper::fillInMissingDays($statDAO, $yesterday, $this->toDateTime->format('Y-m-d'), $labelFormat);
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @throws \Exception
     */
    public function getSumsByWeek($labelFormat = 'Y-W'): StatDAO
    {
        $statDAO   = new StatDAO();
        $yesterday = $this->fromDateTime ? $this->fromDateTime->format('Y-W') : null;

        foreach ($this->statsDAO->getWeeks() as $today => $stats) {
            CalculatorHelper::fillInMissingWeeks($statDAO, $yesterday, $today, $labelFormat);

            $statDAO->addStat(
                $today,
                $stats->getCount()
            );

            $yesterday = $today;
        }

        $yesterday = (new \DateTime(CalculatorHelper::getWeekDateString($yesterday)))->modify('+1 week')->format('Y-W');

        if ($this->toDateTime) {
            /** @var \DateTime $tomorrow */
            $tomorrow = clone $this->toDateTime;
            CalculatorHelper::fillInMissingWeeks($statDAO, $yesterday, $tomorrow->modify('+1 week')->format('Y-W'), $labelFormat);
        }

        return $statDAO;
    }

    /**
     * @param string $labelFormat
     *
     * @throws \Exception
     */
    public function getCountsByHour($labelFormat = 'Y-m-d H'): StatDAO
    {
        $statDAO  = new StatDAO();
        $lastHour = $this->fromDateTime ? $this->fromDateTime->format('Y-m-d H') : null;

        foreach ($this->statsDAO->getHours() as $thisHour => $stats) {
            CalculatorHelper::fillInMissingHours($statDAO, $lastHour, $thisHour, $labelFormat);

            $statDAO->addStat(
                CalculatorHelper::getHourLabel($thisHour, $labelFormat),
                $stats->getCount()
            );

            $lastHour = $thisHour;
        }

        if ($this->toDateTime) {
            CalculatorHelper::fillInMissingHours($statDAO, $lastHour, $this->toDateTime->format('Y-m-d H'), $labelFormat);
        }

        return $statDAO;
    }

    public function getSum(): StatDAO
    {
        $statDAO = new StatDAO();
        $sum     = 0;

        foreach ($this->statsDAO->getYears() as $stats) {
            $sum += $stats->getSum();
        }

        return $statDAO;
    }
}
