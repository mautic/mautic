<?php

namespace Mautic\ReportBundle\Scheduler\Builder;

use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Scheduler\Exception\NotSupportedScheduleTypeException;
use Mautic\ReportBundle\Scheduler\Factory\SchedulerTemplateFactory;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;
use Recurr\Exception\InvalidWeekday;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;

class SchedulerBuilder
{
    public function __construct(
        private SchedulerTemplateFactory $schedulerTemplateFactory
    ) {
    }

    /**
     * @return \Recurr\Recurrence[]|\Recurr\RecurrenceCollection
     *
     * @throws InvalidSchedulerException
     * @throws NotSupportedScheduleTypeException
     */
    public function getNextEvent(SchedulerInterface $scheduler)
    {
        return $this->getNextEvents($scheduler, 1);
    }

    /**
     * @param int $count
     *
     * @return \Recurr\RecurrenceCollection
     *
     * @throws InvalidSchedulerException
     * @throws NotSupportedScheduleTypeException
     */
    public function getNextEvents(SchedulerInterface $scheduler, $count)
    {
        if (!$scheduler->isScheduled()) {
            throw new InvalidSchedulerException();
        }

        $builder   = $this->schedulerTemplateFactory->getBuilder($scheduler);
        $startDate = new \DateTime();
        $rule      = new Rule();

        if (!$scheduler->isScheduledNow()) {
            $startDate->setTime(0, 0)->modify('+1 day');
        }

        $rule->setStartDate($startDate)->setCount($count);

        try {
            $finalScheduler = $builder->build($rule, $scheduler);
            $transformer    = new ArrayTransformer();

            return $transformer->transform($finalScheduler);
        } catch (InvalidWeekday) {
            throw new InvalidSchedulerException();
        }
    }
}
