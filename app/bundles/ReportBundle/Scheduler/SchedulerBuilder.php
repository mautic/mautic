<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Scheduler;

use Mautic\ReportBundle\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Exception\NotSupportedScheduleTypeException;
use Recurr\Exception\InvalidWeekday;
use Recurr\Rule;
use Recurr\Transformer\ArrayTransformer;

class SchedulerBuilder
{
    /**
     * @var SchedulerInterface
     */
    private $scheduler;

    /**
     * @param SchedulerInterface $scheduler
     *
     * @throws InvalidSchedulerException
     */
    public function __construct(SchedulerInterface $scheduler)
    {
        if (!$scheduler->isScheduled()) {
            throw new InvalidSchedulerException();
        }
        $this->scheduler = $scheduler;
    }

    /**
     * @return \Recurr\Recurrence[]|\Recurr\RecurrenceCollection
     *
     * @throws InvalidSchedulerException
     * @throws NotSupportedScheduleTypeException
     */
    public function getNextEvent()
    {
        return $this->getNextEvents(1);
    }

    /**
     * @param int $count
     *
     * @return \Recurr\Recurrence[]|\Recurr\RecurrenceCollection
     *
     * @throws InvalidSchedulerException
     * @throws NotSupportedScheduleTypeException
     */
    public function getNextEvents($count)
    {
        $startDate = new \DateTime();
        $rule      = new Rule();
        $rule->setStartDate($startDate)
            ->setCount($count);

        if ($this->scheduler->isScheduledDaily()) {
            $builder = new SchedulerDailyBuilder();
        } elseif ($this->scheduler->isScheduledWeekly()) {
            $builder = new SchedulerWeeklyBuilder();
        } elseif ($this->scheduler->isScheduledMonthly()) {
            $builder = new SchedulerMonthBuilder();
        } else {
            throw new NotSupportedScheduleTypeException();
        }

        try {
            $scheduler   = $builder->build($rule, $this->scheduler);
            $transformer = new ArrayTransformer();

            return $transformer->transform($scheduler);
        } catch (InvalidWeekday $e) {
            throw new InvalidSchedulerException();
        }
    }
}
