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

use Mautic\ReportBundle\Exception\InvalidSchedulerException;
use Mautic\ReportBundle\Exception\NotSupportedScheduleTypeException;
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
     * @param SchedulerInterface $scheduler
     *
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
     * @param SchedulerInterface $scheduler
     * @param int                $count
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

        $startDate = (new \DateTime())->setTime(0, 0);
        $rule      = new Rule();
        $rule->setStartDate($startDate)
            ->setCount($count);

        $builder = $this->schedulerTemplateFactory->getBuilder($scheduler);

        try {
            $finalScheduler = $builder->build($rule, $scheduler);
            $transformer    = new ArrayTransformer();

            return $transformer->transform($finalScheduler);
        } catch (InvalidWeekday $e) {
            throw new InvalidSchedulerException();
        }
    }
}
