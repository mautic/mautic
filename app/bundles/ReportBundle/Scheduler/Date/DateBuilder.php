<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler\Date;

use Mautic\ReportBundle\Scheduler\Builder\SchedulerBuilder;
use Mautic\ReportBundle\Scheduler\Entity\SchedulerEntity;
use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Scheduler\Exception\NoScheduleException;
use Mautic\ReportBundle\Scheduler\Exception\NotSupportedScheduleTypeException;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;

class DateBuilder
{
    /**
     * @var SchedulerBuilder
     */
    private $schedulerBuilder;

    public function __construct(SchedulerBuilder $schedulerBuilder)
    {
        $this->schedulerBuilder = $schedulerBuilder;
    }

    /**
     * @param bool   $isScheduled
     * @param string $scheduleUnit
     * @param string $scheduleDay
     * @param string $scheduleMonthFrequency
     *
     * @return array
     */
    public function getPreviewDays($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency)
    {
        $entity = new SchedulerEntity($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency);

        try {
            $recurrences = $this->schedulerBuilder->getNextEvents($entity, 10);
        } catch (InvalidSchedulerException $e) {
            return [];
        } catch (NotSupportedScheduleTypeException $e) {
            return [];
        }

        $dates = [];
        foreach ($recurrences as $recurrence) {
            $dates[] = $recurrence->getStart();
        }

        return $dates;
    }

    /**
     * @param SchedulerInterface $scheduler
     *
     * @return \DateTimeInterface
     *
     * @throws NoScheduleException
     */
    public function getNextEvent(SchedulerInterface $scheduler)
    {
        try {
            $recurrences = $this->schedulerBuilder->getNextEvent($scheduler);
        } catch (InvalidSchedulerException $e) {
            throw new NoScheduleException();
        } catch (NotSupportedScheduleTypeException $e) {
            throw new NoScheduleException();
        }

        if (empty($recurrences[0])) {
            throw new NoScheduleException();
        }

        return $recurrences[0]->getStart();
    }
}
