<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
    /** @var SchedulerTemplateFactory */
    private $schedulerTemplateFactory;

    public function __construct(SchedulerTemplateFactory $schedulerTemplateFactory)
    {
        $this->schedulerTemplateFactory = $schedulerTemplateFactory;
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
     * @return \Recurr\Recurrence[]|\Recurr\RecurrenceCollection
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
        } catch (InvalidWeekday $e) {
            throw new InvalidSchedulerException();
        }
    }
}
