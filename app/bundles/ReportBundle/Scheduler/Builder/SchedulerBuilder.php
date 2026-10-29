<?php

namespace Mautic\ReportBundle\Scheduler\Builder;

use Recurr\Recurrence;
use Recurr\RecurrenceCollection;
use Mautic\ReportBundle\Scheduler\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Scheduler\Exception\NotSupportedScheduleTypeException;
use Mautic\ReportBundle\Scheduler\Factory\SchedulerTemplateFactory;
use Mautic\ReportBundle\Scheduler\SchedulerInterface;
use Recurr\Exception\InvalidWeekday;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;

final readonly class SchedulerBuilder
{
    public function __construct(
        private SchedulerTemplateFactory $schedulerTemplateFactory,
    ) {
    }

    /**
     * @return Recurrence[]|RecurrenceCollection
     *
     * @throws InvalidSchedulerException
     * @throws NotSupportedScheduleTypeException
     */
    public function getNextEvent(SchedulerInterface $scheduler)
    {
        return $this->getNextEvents($scheduler, 1);
    }

    /**
     * @return RecurrenceCollection
     *
     * @throws InvalidSchedulerException
     * @throws NotSupportedScheduleTypeException
     */
    public function getNextEvents(SchedulerInterface $scheduler, int $count)
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
