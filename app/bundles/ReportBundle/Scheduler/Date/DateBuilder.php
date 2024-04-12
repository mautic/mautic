<?php

namespace Mautic\ReportBundle\Scheduler\Date;

use Mautic\ReportBundle\Scheduler\Builder\SchedulerBuilder;
use Mautic\ReportBundle\Scheduler\Entity\SchedulerEntity;
use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Scheduler\Exception\NoScheduleException;
use Mautic\ReportBundle\Scheduler\Exception\NotSupportedScheduleTypeException;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;

class DateBuilder
{
    public function __construct(
        private SchedulerBuilder $schedulerBuilder
    ) {
    }

    /**
     * @param bool   $isScheduled
     * @param string $scheduleUnit
     * @param string $scheduleDay
     * @param string $scheduleMonthFrequency
     */
    public function getPreviewDays($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency): array
    {
        $entity = new SchedulerEntity($isScheduled, $scheduleUnit, $scheduleDay, $scheduleMonthFrequency);
        $count  = $entity->isScheduledNow() ? 1 : 10;

        try {
            $recurrences = $this->schedulerBuilder->getNextEvents($entity, $count);
        } catch (InvalidSchedulerException|NotSupportedScheduleTypeException) {
            return [];
        }

        $dates = [];
        foreach ($recurrences as $recurrence) {
            $dates[] = $recurrence->getStart();
        }

        return $dates;
    }

    /**
     * @return \DateTimeInterface
     *
     * @throws NoScheduleException
     */
    public function getNextEvent(SchedulerInterface $scheduler)
    {
        try {
            $recurrences = $this->schedulerBuilder->getNextEvent($scheduler);
        } catch (InvalidSchedulerException|NotSupportedScheduleTypeException) {
            throw new NoScheduleException();
        }

        if (empty($recurrences[0])) {
            throw new NoScheduleException();
        }

        return $recurrences[0]->getStart();
    }
}
